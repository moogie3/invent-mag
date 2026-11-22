<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tenant;
use App\Services\StockValuationService;
use Illuminate\Database\Seeder;

// Add this line

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create super admin account (landlord-level)
        $this->call(SuperAdminSeeder::class);

        // 2. Create global roles, permissions, and plans first
        $this->call([
            PermissionSeeder::class,
            StockOpnamePermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
            PlanSeeder::class,
        ]);

        $domain = config('app.domain', 'localhost');
        // 2. Create tenants for each plan tier
        $tenants = [
            [
                'name' => 'Starter Tenant',
                'domain' => 'starter.'.$domain,
                'plan' => 'starter',
            ],
            [
                'name' => 'Professional Tenant',
                'domain' => 'professional.'.$domain,
                'plan' => 'professional',
            ],
            [
                'name' => 'Enterprise Tenant',
                'domain' => 'enterprise.'.$domain,
                'plan' => 'enterprise',
            ],
            [
                'name' => 'Demo Starter Workspace',
                'domain' => 'demo-starter.'.$domain,
                'plan' => 'starter',
            ],
            [
                'name' => 'Demo Professional Workspace',
                'domain' => 'demo-pro.'.$domain,
                'plan' => 'professional',
            ],
            [
                'name' => 'Demo Enterprise Workspace',
                'domain' => 'demo-enterprise.'.$domain,
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
                $plan = Plan::findBySlug($planSlug);
                if ($plan) {
                    $isDemo = str_starts_with($tenantData['name'], 'Demo ');
                    $tenant->assignPlan($plan, ! $isDemo); // false for Demo to make it active, true for regular to start trial
                    $this->command->info("  - Assigned {$plan->name} plan".($isDemo ? ' (Active/No Trial)' : ' (On Trial)'));
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
                StockAdjustmentSeeder::class,
                CustomerSeeder::class,
                CurrencySeeder::class,
                TaxSeeder::class,          // ← must run BEFORE Purchase/Sales seeders
                PurchaseSeeder::class,
                SalesSeeder::class,
                PurchaseReturnSeeder::class,
                SalesReturnSeeder::class,
                CustomerInteractionSeeder::class,
                SupplierInteractionSeeder::class,
                StockTransferSeeder::class,
                StockOpnameSeeder::class,
                SalesPipelineSeeder::class,
                PipelineStageSeeder::class,
                SalesOpportunitySeeder::class,
                ManualJournalEntrySeeder::class,
                QuotationSeeder::class,
                ActivityLogSeeder::class,
                CreditFeatureSeeder::class,
                EmployeeSeeder::class,
                AttendanceSeeder::class,
                PayrollSeeder::class,
            ]);

            // Four of those seeders move stock without journaling it, so a
            // fresh seed ends with goods the Inventory account has never
            // heard of. Close that gap the same way the 2026-11-21 shelf
            // migration closes it on an existing database; a second seed
            // measures zero and posts nothing.
            $gap = app(StockValuationService::class)->alignGlToShelf(
                'Stock on hand not in the books - demo seed alignment',
                'demo seed alignment'
            );

            if ($gap && isset($this->command)) {
                $this->command->info(sprintf(
                    '  Stock on hand aligned with the Inventory account: %s%s',
                    $gap > 0 ? '+' : '',
                    number_format($gap, 2)
                ));
            }
        }

        // 5. Forget the current tenant to clean up
        Tenant::forgetCurrent();
    }
}
