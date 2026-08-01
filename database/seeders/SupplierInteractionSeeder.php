<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\SupplierInteraction;
use App\Models\User;
use Illuminate\Database\Seeder;

class SupplierInteractionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tenantId = app('currentTenant')->id;

        if (SupplierInteraction::where('tenant_id', $tenantId)->count() > 0) {
            $this->command->info('Supplier interactions already exist for tenant '.app('currentTenant')->name.'. Skipping.');

            return;
        }

        $suppliers = Supplier::where('tenant_id', $tenantId)->get();
        $user = User::where('tenant_id', $tenantId)->first(); // Get the first user for the tenant

        if ($suppliers->isEmpty()) {
            $this->command->info('Skipping SupplierInteractionSeeder for tenant '.app('currentTenant')->name.': No suppliers found. Please run SupplierSeeder first.');

            return;
        }

        if (! $user) {
            $this->command->info('Skipping SupplierInteractionSeeder for tenant '.app('currentTenant')->name.': No users found. Please ensure UserSeeder has been run for this tenant.');

            return;
        }

        foreach ($suppliers as $supplier) {
            // Create 1 to 3 interactions per supplier
            SupplierInteraction::factory()->count(rand(1, 3))->create([
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
            ]);
        }

        $this->command->info('Supplier interactions for tenant '.app('currentTenant')->name.' seeded successfully.');
    }
}
