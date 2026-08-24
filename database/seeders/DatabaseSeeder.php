<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles
        $superAdmin = Role::create([
            'name' => 'super_admin',
            'display_name' => 'Super Administrator',
            'description' => 'Full system access',
        ]);

        $branchManager = Role::create([
            'name' => 'branch_manager',
            'display_name' => 'Branch Manager',
            'description' => 'Manage branch inventory and orders',
        ]);

        $salesUser = Role::create([
            'name' => 'sales_user',
            'display_name' => 'Sales User',
            'description' => 'Create orders only',
        ]);

        // Create branches
        $branch1 = Branch::create([
            'name' => 'Downtown Branch',
            'code' => 'DT',
            'address' => '123 Main Street, City Center',
            'phone' => '(555) 123-4567',
            'email' => 'downtown@inventory.local',
            'status' => 'active',
        ]);

        $branch2 = Branch::create([
            'name' => 'Uptown Branch',
            'code' => 'UP',
            'address' => '456 Oak Avenue, North Side',
            'phone' => '(555) 234-5678',
            'email' => 'uptown@inventory.local',
            'status' => 'active',
        ]);

        $branch3 = Branch::create([
            'name' => 'West Mall Branch',
            'code' => 'WM',
            'address' => '789 Shopping Plaza, West Side',
            'phone' => '(555) 345-6789',
            'email' => 'westmall@inventory.local',
            'status' => 'active',
        ]);

        // Create users
        // Super Admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@inventory.local',
            'password' => bcrypt('password123'),
            'role_id' => $superAdmin->id,
            'branch_id' => $branch1->id,
            'status' => 'active',
        ]);

        // Branch Managers
        User::create([
            'name' => 'John Manager (Downtown)',
            'email' => 'john@inventory.local',
            'password' => bcrypt('password123'),
            'role_id' => $branchManager->id,
            'branch_id' => $branch1->id,
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Sarah Manager (Uptown)',
            'email' => 'sarah@inventory.local',
            'password' => bcrypt('password123'),
            'role_id' => $branchManager->id,
            'branch_id' => $branch2->id,
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Mike Manager (West Mall)',
            'email' => 'mike@inventory.local',
            'password' => bcrypt('password123'),
            'role_id' => $branchManager->id,
            'branch_id' => $branch3->id,
            'status' => 'active',
        ]);

        // Sales Users
        User::create([
            'name' => 'Alice Sales (Downtown)',
            'email' => 'alice@inventory.local',
            'password' => bcrypt('password123'),
            'role_id' => $salesUser->id,
            'branch_id' => $branch1->id,
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Bob Sales (Uptown)',
            'email' => 'bob@inventory.local',
            'password' => bcrypt('password123'),
            'role_id' => $salesUser->id,
            'branch_id' => $branch2->id,
            'status' => 'active',
        ]);

        // Create sample products
        $products = [
            [
                'name' => 'Laptop Pro 15',
                'sku' => 'LP-PRO-15',
                'description' => 'High-performance laptop with 15-inch display',
                'cost_price' => 800.00,
                'sale_price' => 1299.99,
                'tax_percentage' => 10,
                'status' => 'active',
            ],
            [
                'name' => 'Wireless Mouse',
                'sku' => 'MOUSE-WL',
                'description' => 'Ergonomic wireless mouse with 2.4GHz connection',
                'cost_price' => 15.00,
                'sale_price' => 29.99,
                'tax_percentage' => 10,
                'status' => 'active',
            ],
            [
                'name' => 'USB-C Cable',
                'sku' => 'CABLE-USB-C',
                'description' => '2-meter USB-C charging and data cable',
                'cost_price' => 5.00,
                'sale_price' => 12.99,
                'tax_percentage' => 10,
                'status' => 'active',
            ],
            [
                'name' => 'Monitor 4K 27\"',
                'sku' => 'MON-4K-27',
                'description' => '4K UHD Monitor with HDR support',
                'cost_price' => 350.00,
                'sale_price' => 599.99,
                'tax_percentage' => 10,
                'status' => 'active',
            ],
            [
                'name' => 'Mechanical Keyboard',
                'sku' => 'KB-MECH',
                'description' => 'RGB Mechanical Gaming Keyboard',
                'cost_price' => 80.00,
                'sale_price' => 149.99,
                'tax_percentage' => 10,
                'status' => 'active',
            ],
            [
                'name' => 'Headphones Wireless',
                'sku' => 'HP-WIRELESS',
                'description' => 'Noise-cancelling wireless headphones',
                'cost_price' => 120.00,
                'sale_price' => 229.99,
                'tax_percentage' => 10,
                'status' => 'active',
            ],
            [
                'name' => 'Webcam HD',
                'sku' => 'WC-HD',
                'description' => '1080p HD Webcam with auto-focus',
                'cost_price' => 40.00,
                'sale_price' => 79.99,
                'tax_percentage' => 10,
                'status' => 'active',
            ],
            [
                'name' => 'USB Hub 3.0',
                'sku' => 'HUB-USB3',
                'description' => '7-port USB 3.0 Hub',
                'cost_price' => 25.00,
                'sale_price' => 49.99,
                'tax_percentage' => 10,
                'status' => 'active',
            ],
        ];

        $createdProducts = [];
        foreach ($products as $product) {
            $createdProducts[] = Product::create($product);
        }

        // Create initial inventory for all branches
        foreach ($createdProducts as $product) {
            // Downtown Branch - more stock
            Inventory::create([
                'product_id' => $product->id,
                'branch_id' => $branch1->id,
                'quantity' => rand(50, 150),
                'low_stock_threshold' => 20,
            ]);

            // Uptown Branch
            Inventory::create([
                'product_id' => $product->id,
                'branch_id' => $branch2->id,
                'quantity' => rand(30, 100),
                'low_stock_threshold' => 15,
            ]);

            // West Mall Branch
            Inventory::create([
                'product_id' => $product->id,
                'branch_id' => $branch3->id,
                'quantity' => rand(10, 60),
                'low_stock_threshold' => 15,
            ]);
        }

        $this->call(ProductInventorySeeder::class);
        $this->call(OrderSeeder::class);
        $this->call(AdditionalBranchesSeeder::class);

        $this->command->info('Database seeded successfully!');
        $this->command->info('');
        $this->command->info('Sample Credentials:');
        $this->command->info('  Super Admin: admin@inventory.local / password123');
        $this->command->info('  Branch Manager (Downtown): john@inventory.local / password123');
        $this->command->info('  Branch Manager (Uptown): sarah@inventory.local / password123');
        $this->command->info('  Sales User (Downtown): alice@inventory.local / password123');
    }
}
