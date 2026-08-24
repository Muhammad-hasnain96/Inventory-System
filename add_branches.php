<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$branchManagerRole = App\Models\Role::where('name', 'branch_manager')->first();

if (!$branchManagerRole) {
    echo "Branch manager role not found\n";
    exit(1);
}

// Create 2 additional branches
$branch4 = App\Models\Branch::create([
    'name' => 'East Side Branch',
    'code' => 'ES',
    'address' => '789 East Avenue, East Side',
    'phone' => '(555) 456-7890',
    'email' => 'eastside@inventory.local',
    'status' => 'active',
]);

$branch5 = App\Models\Branch::create([
    'name' => 'North Mall Branch',
    'code' => 'NM',
    'address' => '321 North Plaza, North District',
    'phone' => '(555) 567-8901',
    'email' => 'northmall@inventory.local',
    'status' => 'active',
]);

echo "Created branches: {$branch4->name} (ID: {$branch4->id}), {$branch5->name} (ID: {$branch5->id})\n";

// Create branch managers for the new branches
$user1 = App\Models\User::create([
    'name' => 'David Manager (East Side)',
    'email' => 'david@inventory.local',
    'password' => bcrypt('password123'),
    'role_id' => $branchManagerRole->id,
    'branch_id' => $branch4->id,
    'status' => 'active',
]);

$user2 = App\Models\User::create([
    'name' => 'Emma Manager (North Mall)',
    'email' => 'emma@inventory.local',
    'password' => bcrypt('password123'),
    'role_id' => $branchManagerRole->id,
    'branch_id' => $branch5->id,
    'status' => 'active',
]);

echo "Created managers: {$user1->name} (ID: {$user1->id}), {$user2->name} (ID: {$user2->id})\n";
?>