<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdditionalBranchesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branchManagerRole = Role::where('name', 'branch_manager')->first();

        // Create 2 additional branches
        $branch4 = Branch::create([
            'name' => 'East Side Branch',
            'code' => 'ES',
            'address' => '789 East Avenue, East Side',
            'phone' => '(555) 456-7890',
            'email' => 'eastside@inventory.local',
            'status' => 'active',
        ]);

        $branch5 = Branch::create([
            'name' => 'North Mall Branch',
            'code' => 'NM',
            'address' => '321 North Plaza, North District',
            'phone' => '(555) 567-8901',
            'email' => 'northmall@inventory.local',
            'status' => 'active',
        ]);

        // Create branch managers for the new branches
        User::create([
            'name' => 'David Manager (East Side)',
            'email' => 'david@inventory.local',
            'password' => bcrypt('password123'),
            'role_id' => $branchManagerRole->id,
            'branch_id' => $branch4->id,
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Emma Manager (North Mall)',
            'email' => 'emma@inventory.local',
            'password' => bcrypt('password123'),
            'role_id' => $branchManagerRole->id,
            'branch_id' => $branch5->id,
            'status' => 'active',
        ]);
    }
}