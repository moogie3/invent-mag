<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Multitenancy\Facades\Tenant as TenantFacade;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create global roles and permissions first
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
        ]);

        // 2. Create a default tenant for initial seeding
        $defaultTenant = Tenant::firstOrCreate([
            'name' => 'Main Tenant',
            'domain' => 'localhost', // Or your default local domain
        ]);

        // 3. Run all other tenant-specific seeders within the context of the default tenant
        TenantFacade::current($defaultTenant)->run(function () {
            $this->call([
                AccountSeeder::class,
                UserSeeder::class, // Keep for now, but will be removed for frontend registration
                CategorySeeder::class,
                UnitSeeder::class,
                SupplierSeeder::class,
                WarehouseSeeder::class,
                ProductSeeder::class,
                CustomerSeeder::class,
                CurrencySeeder::class,
                PurchaseSeeder::class,
                SalesSeeder::class,
                PurchaseReturnSeeder::class,
                SalesReturnSeeder::class,
                CustomerInteractionSeeder::class, // Corrected typo
                SupplierInteractionSeeder::class,
                TaxSeeder::class,
                StockAdjustmentSeeder::class,
                SalesPipelineSeeder::class,
                PipelineStageSeeder::class,
                SalesOpportunitySeeder::class,
            ]);
        });
    }
}
