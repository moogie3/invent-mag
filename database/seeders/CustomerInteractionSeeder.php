<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerInteraction;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerInteractionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tenantId = app('currentTenant')->id;

        if (CustomerInteraction::where('tenant_id', $tenantId)->count() > 0) {
            $this->command->info('Customer interactions already exist for tenant '.app('currentTenant')->name.'. Skipping.');

            return;
        }

        $customers = Customer::where('tenant_id', $tenantId)->get();
        $user = User::where('tenant_id', $tenantId)->first(); // Get the first user for the tenant

        if ($customers->isEmpty()) {
            $this->command->info('Skipping CustomerInteractionSeeder for tenant '.app('currentTenant')->name.': No customers found. Please run CustomerSeeder first.');

            return;
        }

        if (! $user) {
            $this->command->info('Skipping CustomerInteractionSeeder for tenant '.app('currentTenant')->name.': No users found. Please ensure UserSeeder has been run for this tenant.');

            return;
        }

        foreach ($customers as $customer) {
            // Create 1 to 3 interactions per customer
            CustomerInteraction::factory()->count(rand(1, 3))->create([
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
            ]);
        }

        $this->command->info('Customer interactions for tenant '.app('currentTenant')->name.' seeded successfully.');
    }
}
