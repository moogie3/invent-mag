<?php

namespace App\Services;

use App\Models\Plan;
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
use Illuminate\Support\Facades\Log;

class TenantSetupService
{
    protected PlanService $planService;

    public function __construct()
    {
        $this->planService = new PlanService();
    }

    /**
     * Set up default data for a new tenant.
     *
     * @param  string|null  $planSlug  The plan slug to assign (e.g., 'starter', 'professional', 'enterprise')
     */
    public function setup(Tenant $tenant, ?string $planSlug = null): void
    {
        set_time_limit(0); // Ensure long-running setup completes
        // Make tenant current
        $tenant->makeCurrent();

        // Assign plan to tenant (backward compatible: defaults to config value)
        $this->assignPlan($tenant, $planSlug);

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
     * Assign a plan to the tenant during setup.
     * If no plan slug is provided, uses the configured default plan.
     * If no plans exist in the database yet, gracefully skips (backward compat).
     */
    private function assignPlan(Tenant $tenant, ?string $planSlug): void
    {
        // If no plans table or no plans exist yet, skip gracefully
        try {
            if (Plan::count() === 0) {
                Log::info("No plans found in database. Skipping plan assignment for tenant '{$tenant->name}'.");
                return;
            }
        } catch (\Exception $e) {
            Log::info("Plans table not available yet. Skipping plan assignment for tenant '{$tenant->name}'.");
            return;
        }

        $slug = $planSlug ?? config('plans.default_plan', 'starter');

        // Determine if trial should start (Professional and Enterprise offer trials)
        $plan = Plan::findBySlug($slug);
        $startTrial = $plan && $plan->hasTrial();

        $this->planService->assignPlanToTenant($tenant, $slug, $startTrial);
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
            } catch (\Throwable $e) {
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
