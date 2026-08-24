<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderService
{
    private InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Get orders for a branch
     */
    public function getBranchOrders(Branch $branch, int $page = 1, int $perPage = 15)
    {
        return $branch->orders()
            ->with('items.product', 'createdBy')
            ->recent()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get a specific order with all items
     */
    public function getOrder(int $id): ?Order
    {
        return Order::with('items.product', 'branch', 'createdBy')->find($id);
    }

    /**
     * Create order with multiple items
     * 
     * CRITICAL: This method implements race condition prevention using:
     * 1. Database transaction for atomicity
     * 2. Row-level locking (SELECT FOR UPDATE) on inventory records
     * 3. Sequential locking to prevent deadlocks
     * 4. Stock validation before deduction
     * 
     * This ensures:
     * - No overselling even under concurrent requests
     * - Inventory consistency
     * - Complete order atomicity (all items succeed or all fail)
     */
    public function createOrder(Branch $branch, User $user, array $items, string $notes = null): Order
    {
        return DB::transaction(function () use ($branch, $user, $items, $notes) {
            // Validate all items and products exist before starting stock deduction
            $validatedItems = [];
            $subtotal = 0;
            $totalTax = 0;

            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    throw new \Exception("Product {$item['product_id']} not found");
                }

                if ($product->status !== 'active') {
                    throw new \Exception("Product {$product->name} is not active");
                }

                if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                    throw new \Exception("Invalid quantity for product {$product->name}");
                }

                $quantity = (int) $item['quantity'];
                $unitPrice = $product->sale_price;
                $taxPercentage = $product->tax_percentage;
                $lineTotal = $unitPrice * $quantity;
                $lineTax = ($lineTotal * $taxPercentage) / 100;

                $validatedItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_percentage' => $taxPercentage,
                    'line_total' => $lineTotal,
                    'line_tax' => $lineTax,
                ];

                $subtotal += $lineTotal;
                $totalTax += $lineTax;
            }

            // CRITICAL: Lock all inventory records for this branch
            // We lock inventories by product ID and branch to prevent concurrent modifications
            // This is done in a consistent order (by product_id) to prevent deadlocks from occur
            $productIds = collect($validatedItems)->pluck('product.id')->sort()->values()->toArray();
            
            // Verify all required inventories exist and are available
            $inventories = DB::table('inventories')
                ->whereIn('product_id', $productIds)
                ->where('branch_id', $branch->id)
                ->lockForUpdate()
                ->get();

            $inventoryMap = $inventories->keyBy('product_id');

            // Second validation: verify stock after acquiring locks
            foreach ($validatedItems as $item) {
                $inventory = $inventoryMap->get($item['product']->id);
                if (!$inventory) {
                    throw new \Exception("Product {$item['product']->name} not available in this branch");
                }
                if ($inventory->quantity < $item['quantity']) {
                    throw new \Exception(
                        "Insufficient stock for {$item['product']->name}. "
                        . "Available: {$inventory->quantity}, Required: {$item['quantity']}"
                    );
                }
            }

            // Create order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber($branch),
                'branch_id' => $branch->id,
                'created_by' => $user->id,
                'subtotal' => $subtotal,
                'tax_amount' => $totalTax,
                'total_amount' => $subtotal + $totalTax,
                'status' => 'confirmed',
                'notes' => $notes,
            ]);

            // Process each order item and deduct stock atomically
            foreach ($validatedItems as $item) {
                // Create order item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_percentage' => $item['tax_percentage'],
                    'line_total' => $item['line_total'],
                ]);

                // Deduct stock (with locking already in place from above)
                $inventory = $inventoryMap->get($item['product']->id);
                // Refresh the object from database to get fresh model instance
                $inventoryModel = \App\Models\Inventory::find($inventory->id);
                
                $this->inventoryService->deductStockForOrder(
                    $inventoryModel,
                    $item['quantity'],
                    $user,
                    $order
                );
            }

            return $order->refresh()->load('items.product', 'branch', 'createdBy');
        }, attempts: 3); // Retry up to 3 times in case of deadlock
    }

    /**
     * Cancel an order and restore stock
     * This must be called within a transaction
     */
    public function cancelOrder(Order $order, User $user, string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $user, $reason) {
            if ($order->status === 'cancelled') {
                throw new \Exception('Order is already cancelled');
            }

            // Restore stock for all items
            foreach ($order->items as $item) {
                $inventory = $this->inventoryService->getOrCreateInventory($item->product, $order->branch);
                $this->inventoryService->incrementStock(
                    $inventory,
                    $item->quantity,
                    $user,
                    "Order cancellation: {$reason}",
                );
            }

            $order->update(['status' => 'cancelled']);
            return $order->refresh();
        });
    }

    /**
     * Get today's sales by branch
     */
    public function getTodaySales(?Branch $branch): array
    {
        $query = $branch ? $branch->orders() : Order::query();

        return $query
            ->whereDate('created_at', now())
            ->byStatus('confirmed')
            ->get()
            ->groupBy('status')
            ->map(function ($orders) {
                return [
                    'count' => $orders->count(),
                    'total' => $orders->sum('total_amount'),
                ];
            })
            ->toArray();
    }

    /**
     * Get monthly sales
     */
    public function getMonthlySales(?Branch $branch, $year = null, $month = null): array
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;

        $query = Order::query();

        if ($branch) {
            $query->where('branch_id', $branch->id);
        }

        $dailySales = $query
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total_amount) as total')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->byStatus('confirmed')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->mapWithKeys(function ($row) {
                return [
                    $row->date => [
                        'count' => (int) $row->count,
                        'total' => (float) $row->total,
                    ],
                ];
            })
            ->toArray();

        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        if ($year === now()->year && $month === now()->month) {
            $endDate = now();
        }

        $filledSales = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $key = $date->format('Y-m-d');
            $filledSales[$key] = $dailySales[$key] ?? [
                'count' => 0,
                'total' => 0,
            ];
        }

        return $filledSales;
    }

    /**
     * Get top N products by sales
     */
    public function getTopProducts(?Branch $branch, int $limit = 5): array
    {
        $query = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id');

        if ($branch) {
            $query->where('orders.branch_id', $branch->id);
        }

        return $query
            ->where('orders.status', 'confirmed')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.line_total) as total_sales')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc(DB::raw('SUM(order_items.line_total)'))
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get total orders count
     */
    public function getTotalOrdersCount(?Branch $branch): int
    {
        if ($branch) {
            return $branch->orders()->byStatus('confirmed')->count();
        }

        return Order::byStatus('confirmed')->count();
    }
}
