<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();
        $products = Product::active()->get();
        $salesUsers = User::whereHas('role', fn ($q) => $q->whereIn('name', ['sales_user', 'branch_manager']))->get();

        // Create orders for the past 30 days
        $ordersPerDay = 5;
        $today = Carbon::now();

        for ($daysBack = 29; $daysBack >= 0; $daysBack--) {
            $date = $today->copy()->subDays($daysBack);

            foreach (range(1, $ordersPerDay) as $orderIndex) {
                $branch = $branches->random();
                $user = $salesUsers->where('branch_id', $branch->id)->random() ?? $salesUsers->random();
                $itemsCount = rand(2, 5);
                $total = 0;
                $items = [];

                // Create order items
                $selectedProducts = $products->random($itemsCount);
                foreach ($selectedProducts as $product) {
                    $quantity = rand(1, 10);
                    $unitPrice = $product->sale_price;
                    $itemTotal = $quantity * $unitPrice;
                    $total += $itemTotal;

                    $items[] = [
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $itemTotal,
                    ];
                }

                $taxAmount = round($total * 0.10, 2); // 10% tax
                $grandTotal = $total + $taxAmount;

                // Create the order
                $order = Order::create([
                    'order_number' => 'ORD-' . $date->format('Ymd') . '-' . str_pad($orderIndex, 4, '0', STR_PAD_LEFT),
                    'user_id' => $user->id,
                    'branch_id' => $branch->id,
                    'status' => 'confirmed',
                    'subtotal' => round($total, 2),
                    'tax_amount' => $taxAmount,
                    'total_amount' => $grandTotal,
                    'notes' => 'Sample order for dashboard demonstration',
                    'created_at' => $date->copy()->addHours(rand(8, 18))->addMinutes(rand(0, 59)),
                    'updated_at' => $date->copy()->addHours(rand(8, 18))->addMinutes(rand(0, 59)),
                ]);

                // Create order items
                foreach ($items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $item['subtotal'],
                    ]);
                }
            }
        }

        echo "✓ Created orders with sample data for dashboard charts\n";
    }
}
