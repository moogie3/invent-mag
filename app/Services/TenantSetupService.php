<?php

namespace App\Services;

use App\Models\Tenant;
use Database\Seeders\AccountSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\SupplierSeeder;
use Database\Seeders\TaxSeeder;
use Database\Seeders\UnitSeeder;
use Database\Seeders\WarehouseSeeder;
use Illuminate\Support\Facades\Artisan;

class TenantSetupService
{
    /**
     * Set up default data for a new tenant
     */
    public function setup(Tenant $tenant): void
    {
        // Make tenant current
        $tenant->makeCurrent();

        // Run essential seeders for new tenant
        $this->runEssentialSeeders();

        // Create default warehouse (required for operations)
        $this->createDefaultWarehouse($tenant);

        // Create default customer (cash sales)
        $this->createDefaultCustomer($tenant);

        // Forget tenant context
        Tenant::forgetCurrent();
    }

    /**
     * Run seeders that are essential for tenant operation
     */
    private function runEssentialSeeders(): void
    {
        $seeders = [
            AccountSeeder::class,      // Chart of accounts
            CategorySeeder::class,     // Product categories
            UnitSeeder::class,         // Units of measurement
            CurrencySeeder::class,     // Currency settings
            TaxSeeder::class,          // Tax configurations
        ];

        foreach ($seeders as $seeder) {
            try {
                $instance = new $seeder();
                $instance->run();
            } catch (\Exception $e) {
                \Log::error("Error running seeder {$seeder}: " . $e->getMessage());
                // Continue with other seeders
            }
        }
    }

    /**
     * Create a default warehouse for the tenant
     */
    private function createDefaultWarehouse(Tenant $tenant): void
    {
        try {
            \App\Models\Warehouse::create([
                'name' => 'Main Warehouse',
                'address' => $tenant->name . ' Main Location',
                'is_main' => true,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creating default warehouse: ' . $e->getMessage());
        }
    }

    /**
     * Create a default customer for cash sales
     */
    private function createDefaultCustomer(Tenant $tenant): void
    {
        try {
            \App\Models\Customer::create([
                'name' => 'Walk-in Customer',
                'email' => 'cash@' . $tenant->domain,
                'phone_number' => '-',
                'address' => '-',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creating default customer: ' . $e->getMessage());
        }
    }
}
