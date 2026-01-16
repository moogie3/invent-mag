<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\User;
use App\Models\CustomerInteraction;
use Illuminate\Support\Facades\Schema;

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

        Schema::disableForeignKeyConstraints();
        CustomerInteraction::where('tenant_id', $tenantId)->delete();
        Schema::enableForeignKeyConstraints();

        $customers = Customer::where('tenant_id', $tenantId)->get();
        $user = User::where('tenant_id', $tenantId)->first(); // Get the first user for the tenant

        if ($customers->isEmpty()) {
            $this->command->info('Skipping CustomerInteractionSeeder for tenant ' . app('currentTenant')->name . ': No customers found. Please run CustomerSeeder first.');
            return;
        }

        if (!$user) {
            $this->command->info('Skipping CustomerInteractionSeeder for tenant ' . app('currentTenant')->name . ': No users found. Please ensure UserSeeder has been run for this tenant.');
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

        $this->command->info('Customer interactions for tenant ' . app('currentTenant')->name . ' seeded successfully.');
    }
}
