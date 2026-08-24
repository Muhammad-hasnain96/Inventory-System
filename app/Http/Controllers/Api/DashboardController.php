<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private InventoryService $inventoryService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get dashboard data for the current branch or selected branch (admin only)
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $branchId = $request->get('branch_id');
        $branch = null;

        if ($user->isSuperAdmin()) {
            if ($branchId && $branchId !== 'all') {
                $branch = Branch::find($branchId);
                if (!$branch) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Branch not found',
                    ], 404);
                }
            }
        } else {
            $branch = $user->branch;
            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'User branch not assigned',
                ], 400);
            }
        }

        try {
            // Get all dashboard metrics for the selected branch or all branches for super admin
            $todaySales = $this->orderService->getTodaySales($branch);
            $monthlySales = $this->orderService->getMonthlySales($branch);
            $previousMonthSales = $this->orderService->getMonthlySales($branch, now()->subMonth()->year, now()->subMonth()->month);
            $totalOrders = $this->orderService->getTotalOrdersCount($branch);
            $topProducts = $this->orderService->getTopProducts($branch, 8);
            $lowStockItems = $this->inventoryService->getLowStockItems($branch);

            $totalProducts = Product::count();
            $activeProducts = Product::active()->count();
            $totalStock = $branch
                ? Inventory::where('branch_id', $branch->id)->whereHas('product')->sum('quantity')
                : Inventory::whereHas('product')->sum('quantity');

            $monthlyTotal = collect($monthlySales)->sum('total');
            $previousMonthlyTotal = collect($previousMonthSales)->sum('total');
            $salesGrowth = $previousMonthlyTotal > 0
                ? round((($monthlyTotal - $previousMonthlyTotal) / $previousMonthlyTotal) * 100, 1)
                : ($monthlyTotal > 0 ? 100 : 0);

            $monthlySalesChart = collect($monthlySales)
                ->sortKeys()
                ->map(function ($item, $date) use ($monthlyTotal) {
                    $amount = round($item['total'], 2);
                    return [
                        'label' => Carbon::parse($date)->format('j'),
                        'full_date' => Carbon::parse($date)->format('M j'),
                        'value' => $amount,
                    ];
                })
                ->values()
                ->toArray();

            $branchSummary = Branch::active()
                ->withSum(['inventories as stock' => function ($query) {
                    $query->whereHas('product');
                }], 'quantity')
                ->get()
                ->map(function ($branchRow) {
                    return [
                        'name' => $branchRow->name,
                        'stock' => (int) $branchRow->stock,
                        'percent' => 0,
                    ];
                });
            $totalBranchStock = $branchSummary->sum('stock');
            $branchSummary = $branchSummary->map(function ($item) use ($totalBranchStock) {
                return array_merge($item, [
                    'percent' => $totalBranchStock > 0 ? round(($item['stock'] / $totalBranchStock) * 100) : 0,
                ]);
            })->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_products' => $totalProducts,
                    'active_products' => $activeProducts,
                    'total_stock' => $totalStock,
                    'today_orders' => array_sum(array_column($todaySales, 'count')),
                    'today_sales_value' => round(collect($todaySales)->sum('total'), 2),
                    'monthly_total' => round($monthlyTotal, 2),
                    'monthly_sales_chart' => $monthlySalesChart,
                    'sales_growth' => $salesGrowth,
                    'branch_summary' => $branchSummary,
                    'top_products' => $topProducts,
                    'low_stock_count' => count($lowStockItems),
                    'low_stock_items' => array_slice($lowStockItems, 0, 5),
                    'recent_activity' => $this->getRecentActivity($branch),
                    'branch' => $branch
                        ? [
                            'id' => $branch->id,
                            'name' => $branch->name,
                            'code' => $branch->code,
                        ]
                        : [
                            'id' => null,
                            'name' => 'All Branches',
                            'code' => 'ALL',
                        ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sales report data for frontend reports page
     */
    public function salesReport(Request $request): JsonResponse
    {
        $branch = $this->resolveBranch($request);
        if ($branch === false) {
            return response()->json(['success' => false, 'message' => 'Branch not found'], 404);
        }

        $dateRange = $request->get('date_range', 'month');
        $todaySales = $this->orderService->getTodaySales($branch);
        $salesRange = $this->buildSalesRange($branch, $dateRange);

        return response()->json([
            'success' => true,
            'data' => [
                'today' => $todaySales,
                'month' => $salesRange,
            ],
        ]);
    }

    /**
     * Get inventory report data for frontend reports page
     */
    public function inventoryReport(Request $request): JsonResponse
    {
        $branch = $this->resolveBranch($request);
        if ($branch === false) {
            return response()->json(['success' => false, 'message' => 'Branch not found'], 404);
        }

        $inventoryQuery = $branch ? $branch->inventories()->whereHas('product') : Inventory::whereHas('product');

        $uniqueProducts = $inventoryQuery->distinct('product_id')->count('product_id');
        $totalQuantity = $inventoryQuery->sum('quantity');
        $lowStockItems = $this->inventoryService->getLowStockItems($branch);

        return response()->json([
            'success' => true,
            'data' => [
                'unique_products' => $uniqueProducts,
                'total_quantity_in_stock' => $totalQuantity,
                'low_stock_count' => count($lowStockItems),
                'low_stock_items' => $lowStockItems,
            ],
        ]);
    }

    protected function resolveBranch(Request $request)
    {
        $branchId = $request->get('branch_id');

        if (auth()->user()->isSuperAdmin()) {
            if ($branchId && $branchId !== 'all') {
                return Branch::find($branchId) ?: false;
            }

            return null;
        }

        return auth()->user()->branch;
    }

    protected function getRecentActivity(?Branch $branch): array
    {
        $activities = [];

        $inventoryQuery = Inventory::with(['product', 'branch'])
            ->whereHas('product')
            ->when($branch, fn ($query) => $query->where('branch_id', $branch->id));

        $recentInventories = $inventoryQuery->latest('created_at')->limit(2)->get();
        foreach ($recentInventories as $inventory) {
            $activities[] = [
                'id' => 'product-' . $inventory->id,
                'type' => 'product',
                'icon' => '📦',
                'bg_class' => 'bg-blue-100 text-blue-600',
                'title' => 'New product added',
                'description' => $inventory->product->name . ' added to inventory',
                'time' => $inventory->created_at->diffForHumans(),
                'timestamp' => $inventory->created_at->timestamp,
            ];
        }

        $orderQuery = Order::with('branch')
            ->byStatus('confirmed')
            ->when($branch, fn ($query) => $query->where('branch_id', $branch->id));

        $recentOrders = $orderQuery->latest('created_at')->limit(2)->get();
        foreach ($recentOrders as $order) {
            $activities[] = [
                'id' => 'order-' . $order->id,
                'type' => 'order',
                'icon' => '🛒',
                'bg_class' => 'bg-green-100 text-green-600',
                'title' => 'Order completed',
                'description' => "Order #{$order->order_number} processed successfully",
                'time' => $order->created_at->diffForHumans(),
                'timestamp' => $order->created_at->timestamp,
            ];
        }

        $lowStockQuery = Inventory::with(['product', 'branch'])
            ->whereHas('product')
            ->lowStock()
            ->when($branch, fn ($query) => $query->where('branch_id', $branch->id));

        $lowStockItems = $lowStockQuery->orderBy('quantity', 'asc')->limit(2)->get();
        foreach ($lowStockItems as $inventory) {
            $activities[] = [
                'id' => 'lowstock-' . $inventory->id,
                'type' => 'low_stock',
                'icon' => '⚠️',
                'bg_class' => 'bg-amber-100 text-amber-600',
                'title' => 'Low stock alert',
                'description' => $inventory->product->name . ' stock below threshold',
                'time' => $inventory->updated_at->diffForHumans(),
                'timestamp' => $inventory->updated_at->timestamp,
            ];
        }

        $userQuery = User::active()
            ->when($branch, fn ($query) => $query->where('branch_id', $branch->id));

        $recentUsers = $userQuery->latest('created_at')->limit(1)->get();
        foreach ($recentUsers as $user) {
            $activities[] = [
                'id' => 'user-' . $user->id,
                'type' => 'user',
                'icon' => '👤',
                'bg_class' => 'bg-purple-100 text-purple-600',
                'title' => 'New user registered',
                'description' => $user->name . ' joined',
                'time' => $user->created_at->diffForHumans(),
                'timestamp' => $user->created_at->timestamp,
            ];
        }

        usort($activities, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return array_slice($activities, 0, 4);
    }

    protected function buildSalesRange(?Branch $branch, string $dateRange): array
    {
        $query = $branch ? $branch->orders() : Order::query();
        $query = $query->byStatus('confirmed');

        $now = Carbon::now();
        switch ($dateRange) {
            case 'today':
                $start = $now->copy()->startOfDay();
                break;
            case 'week':
                $start = $now->copy()->startOfWeek();
                break;
            case 'quarter':
                $start = $now->copy()->firstOfQuarter();
                break;
            case 'month':
            default:
                $start = $now->copy()->startOfMonth();
                break;
        }

        $sales = $query
            ->whereBetween('created_at', [$start, $now])
            ->get()
            ->groupBy(function ($order) {
                return $order->created_at->format('Y-m-d');
            })
            ->map(function ($orders) {
                return [
                    'count' => $orders->count(),
                    'total' => $orders->sum('total_amount'),
                ];
            })
            ->toArray();

        // Ensure date order is stable
        ksort($sales);

        return $sales;
    }

    public function export(Request $request): StreamedResponse
    {
        $type = $request->get('type', 'sales');
        $dateRange = $request->get('date_range', 'month');
        $branchId = $request->get('branch_id');

        try {
            $branch = $this->resolveBranch($request);
            if ($branch === false) {
                return response()->json(['success' => false, 'message' => 'Branch not found'], 404);
            }

            $filename = $type . '_report_' . $dateRange . '_' . now()->format('Y-m-d') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ];

            $callback = function () use ($type, $dateRange, $branch) {
                $file = fopen('php://output', 'w');
                fwrite($file, "\xEF\xBB\xBF");

                if ($type === 'sales') {
                    // Sales report CSV
                    fputcsv($file, ['Date', 'Orders Count', 'Total Revenue']);

                    $salesData = $this->buildSalesRange($branch, $dateRange);
                    foreach ($salesData as $date => $data) {
                        fputcsv($file, [
                            $date,
                            $data['count'] ?? 0,
                            number_format($data['total'] ?? 0, 2, '.', '')
                        ]);
                    }
                } else {
                    // Inventory report CSV
                    fputcsv($file, ['Product Name', 'SKU', 'Current Stock', 'Low Stock Threshold', 'Status']);

                    $inventoryData = $this->inventoryService->getBranchInventory($branch, 1, 10000);
                    foreach ($inventoryData->items() as $item) {
                        $status = $item['quantity'] <= $item['low_stock_threshold'] ? 'Low Stock' : 'OK';
                        fputcsv($file, [
                            $item['product']['name'] ?? '',
                            $item['product']['sku'] ?? '',
                            $item['quantity'],
                            $item['low_stock_threshold'],
                            $status
                        ]);
                    }
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
