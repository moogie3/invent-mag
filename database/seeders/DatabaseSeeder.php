<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Spatie\Multitenancy\Multitenancy;
use App\Models\User; // Add this line

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

        $domain = config('app.domain', 'localhost');
        // 2. Create tenants
        $tenants = [
            [
                'name' => 'Tenant A',
                'domain' => 'tenant-a.' . $domain,
            ],
            [
                'name' => 'Tenant B',
                'domain' => 'tenant-b.' . $domain,
            ],
        ];

        foreach ($tenants as $tenantData) {
            // Check if tenant already exists
            $existingTenant = Tenant::where('domain', $tenantData['domain'])->first();
            if ($existingTenant) {
                $this->command->info("Tenant {$tenantData['name']} already exists, skipping creation");
                $tenant = $existingTenant;
            } else {
                $tenant = Tenant::create($tenantData);
                $this->command->info("Created tenant: {$tenant->name}");
            }
            
            $tenant->makeCurrent();

            // 4. Run all other tenant-specific seeders
            $this->call([
                AccountSeeder::class,
                UserSeeder::class,
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
                CustomerInteractionSeeder::class,
                SupplierInteractionSeeder::class,
                TaxSeeder::class,
                StockAdjustmentSeeder::class,
                StockTransferSeeder::class,
                SalesPipelineSeeder::class,
                PipelineStageSeeder::class,
                SalesOpportunitySeeder::class,
                ManualJournalEntrySeeder::class,
            ]);
        }

        // 5. Forget the current tenant to clean up
        Tenant::forgetCurrent();
    }
}
