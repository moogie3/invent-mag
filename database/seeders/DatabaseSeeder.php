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
        // 1. Create global roles, permissions, and plans first
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
            PlanSeeder::class,
        ]);

        $domain = config('app.domain', 'localhost');
        // 2. Create tenants for each plan tier
        $tenants = [
            [
                'name' => 'Starter Tenant',
                'domain' => 'starter.' . $domain,
                'plan' => 'starter',
            ],
            [
                'name' => 'Professional Tenant',
                'domain' => 'professional.' . $domain,
                'plan' => 'professional',
            ],
            [
                'name' => 'Enterprise Tenant',
                'domain' => 'enterprise.' . $domain,
                'plan' => 'enterprise',
            ],
            [
                'name' => 'Demo Starter Workspace',
                'domain' => 'demo-starter.' . $domain,
                'plan' => 'starter',
            ],
            [
                'name' => 'Demo Professional Workspace',
                'domain' => 'demo-pro.' . $domain,
                'plan' => 'professional',
            ],
            [
                'name' => 'Demo Enterprise Workspace',
                'domain' => 'demo-enterprise.' . $domain,
                'plan' => 'enterprise',
            ],
        ];

        foreach ($tenants as $tenantData) {
            $planSlug = $tenantData['plan'];
            unset($tenantData['plan']); // remove from array for creation

            // Check if tenant already exists
            $existingTenant = Tenant::where('domain', $tenantData['domain'])->first();
            if ($existingTenant) {
                $this->command->info("Tenant {$tenantData['name']} already exists, skipping creation");
                $tenant = $existingTenant;
            } else {
                $tenant = Tenant::create($tenantData);
                $this->command->info("Created tenant: {$tenant->name}");
                
                // Assign plan
                $plan = \App\Models\Plan::findBySlug($planSlug);
                if ($plan) {
                    $isDemo = str_starts_with($tenantData['name'], 'Demo ');
                    $tenant->assignPlan($plan, !$isDemo); // false for Demo to make it active, true for regular to start trial
                    $this->command->info("  - Assigned {$plan->name} plan" . ($isDemo ? ' (Active/No Trial)' : ' (On Trial)'));
                }
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
