<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Branches:\n";
$branches = App\Models\Branch::all();
foreach ($branches as $branch) {
    echo "- ID: {$branch->id}, Name: {$branch->name}\n";
}

echo "\nBranch Managers:\n";
$managers = App\Models\User::whereHas('role', function($q) {
    $q->where('name', 'branch_manager');
})->with('branch')->get();

foreach ($managers as $manager) {
    echo "- ID: {$manager->id}, Name: {$manager->name}, Branch: {$manager->branch->name}\n";
}
?>