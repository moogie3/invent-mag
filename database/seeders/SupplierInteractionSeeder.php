<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\SupplierInteraction;
use Illuminate\Support\Facades\Schema;

class SupplierInteractionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        SupplierInteraction::truncate();
        Schema::enableForeignKeyConstraints();

        $suppliers = Supplier::all();
        $user = User::find(1); // Assuming user with ID 1 exists

        if ($suppliers->isEmpty()) {
            $this->command->info('Skipping SupplierInteractionSeeder: No suppliers found. Please run SupplierSeeder first.');
            return;
        }

        if (!$user) {
            $this->command->info('Skipping SupplierInteractionSeeder: User with ID 1 not found. Please ensure UserSeeder has been run.');
            // Fallback to a random user if user 1 doesn't exist
            $user = User::inRandomOrder()->first();
            if (!$user) {
                $this->command->info('Skipping SupplierInteractionSeeder: No users found at all.');
                return;
            }
        }

        foreach ($suppliers as $supplier) {
            // Create 1 to 3 interactions per supplier
            SupplierInteraction::factory()->count(rand(1, 3))->create([
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
            ]);
        }

        $this->command->info('Supplier interactions seeded successfully.');
    }
}
