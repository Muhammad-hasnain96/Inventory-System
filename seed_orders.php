<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use Carbon\Carbon;

$count = 0;
for ($i = 0; $i < 30; $i++) {
    for ($j = 0; $j < 3; $j++) {
        $date = Carbon::now()->subDays($i);
        Order::create([
            'order_number' => 'ORD-' . $date->format('Ymd') . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
            'created_by' => 1,
            'branch_id' => rand(1, 3),
            'status' => 'confirmed',
            'subtotal' => rand(500, 3000),
            'tax_amount' => rand(50, 300),
            'total_amount' => rand(550, 3300),
            'notes' => 'Sample order for dashboard',
            'created_at' => $date->addHours(rand(8, 18)),
            'updated_at' => $date->addHours(rand(8, 18)),
        ]);
        $count++;
    }
}

echo "✓ Created $count sample orders\n";
