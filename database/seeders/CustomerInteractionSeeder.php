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
        Schema::disableForeignKeyConstraints();
        CustomerInteraction::truncate();
        Schema::enableForeignKeyConstraints();

        $customers = Customer::all();
        $user = User::find(1); // Assuming user with ID 1 exists and is used for sales

        if ($customers->isEmpty()) {
            $this->command->info('Skipping CustomerInteractionSeeder: No customers found. Please run CustomerSeeder first.');
            return;
        }

        if (!$user) {
            $this->command->info('Skipping CustomerInteractionSeeder: User with ID 1 not found. Please ensure UserSeeder has been run.');
            // Fallback to a random user if user 1 doesn't exist
            $user = User::inRandomOrder()->first();
            if (!$user) {
                $this->command->info('Skipping CustomerInteractionSeeder: No users found at all.');
                return;
            }
        }

        foreach ($customers as $customer) {
            // Create 1 to 3 interactions per customer
            CustomerInteraction::factory()->count(rand(1, 3))->create([
                'customer_id' => $customer->id,
                'user_id' => $user->id,
            ]);
        }

        $this->command->info('Customer interactions seeded successfully.');
    }
}
