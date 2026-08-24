<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductInventorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Laptop',
            'Monitor',
            'Keyboard',
            'Mouse',
            'Headphones',
            'Smartphone',
            'Tablet',
            'Printer',
            'Router',
            'Camera',
            'Speaker',
            'Charger',
            'SSD',
            'Hard Drive',
            'Smartwatch',
            'Power Bank',
            'Projector',
            'Microphone',
            'Webcam',
            'Desk Lamp',
        ];

        $brands = [
            'Nova',
            'Apex',
            'Optima',
            'Pulse',
            'Vertex',
            'Zenith',
            'Quantum',
            'Fusion',
            'Strata',
            'Orbit',
        ];

        $branches = Branch::all();

        foreach (range(1, 492) as $i) {
            $category = $categories[array_rand($categories)];
            $brand = $brands[array_rand($brands)];
            $name = "$brand $category " . rand(100, 999);
            $sku = strtoupper(substr($category, 0, 3)) . '-' . strtoupper(substr($brand, 0, 3)) . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $costPrice = round(rand(8, 900) + rand(0, 99) / 100, 2);
            $salePrice = round($costPrice * (rand(140, 220) / 100), 2);
            $status = rand(1, 10) > 1 ? 'active' : 'inactive';

            $product = Product::create([
                'name' => $name,
                'sku' => $sku,
                'description' => "Premium {$category} unit with modern features for enterprise and retail use.",
                'cost_price' => $costPrice,
                'sale_price' => $salePrice,
                'tax_percentage' => [5, 8, 10, 12, 15][array_rand([5, 8, 10, 12, 15])],
                'status' => $status,
            ]);

            foreach ($branches as $branch) {
                Inventory::create([
                    'product_id' => $product->id,
                    'branch_id' => $branch->id,
                    'quantity' => rand(20, 180),
                    'low_stock_threshold' => rand(8, 25),
                ]);
            }
        }

        // Add 25 additional products with exactly 50 quantity each
        $additionalCategories = [
            'Gaming Mouse',
            'Wireless Keyboard',
            'Bluetooth Speaker',
            'USB Hub',
            'External HDD',
            'Graphics Card',
            'RAM Module',
            'Motherboard',
            'CPU Cooler',
            'Case Fan',
            'Power Supply',
            'Network Cable',
            'HDMI Cable',
            'USB Drive',
            'Memory Card',
            'Phone Case',
            'Screen Protector',
            'Laptop Stand',
            'Desk Organizer',
            'Cable Management',
            'Gaming Chair',
            'Monitor Arm',
            'Docking Station',
            'Webcam Cover',
            'Privacy Screen'
        ];

        foreach (range(493, 517) as $i) {
            $categoryIndex = $i - 493;
            $category = $additionalCategories[$categoryIndex];
            $brand = $brands[array_rand($brands)];
            $name = "$brand $category Pro";
            $sku = 'NEW-' . strtoupper(substr($category, 0, 3)) . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $costPrice = round(rand(15, 500) + rand(0, 99) / 100, 2);
            $salePrice = round($costPrice * (rand(150, 250) / 100), 2);

            $product = Product::create([
                'name' => $name,
                'sku' => $sku,
                'description' => "High-quality {$category} designed for optimal performance and durability.",
                'cost_price' => $costPrice,
                'sale_price' => $salePrice,
                'tax_percentage' => [8, 10, 12][array_rand([8, 10, 12])],
                'status' => 'active',
            ]);

            foreach ($branches as $branch) {
                Inventory::create([
                    'product_id' => $product->id,
                    'branch_id' => $branch->id,
                    'quantity' => 50, // Exactly 50 quantity as requested
                    'low_stock_threshold' => 10,
                ]);
            }
        }
    }
}
