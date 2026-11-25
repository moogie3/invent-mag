<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Services\StockValuationService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database for personal standalone use.
     */
    public function run(): void
    {
        // Global roles and permissions
        $this->call([
            PermissionSeeder::class,
            StockOpnamePermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
        ]);

        // Single workspace
        $domain = config('app.domain', 'localhost');
        $tenant = Tenant::firstOrCreate(
            ['domain' => $domain],
            ['name' => config('app.name', 'Invent-MAG')]
        );
        $this->command->info("Workspace ready: {$tenant->name}");

        $tenant->makeCurrent();

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
            TaxSeeder::class,
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

        Tenant::forgetCurrent();
    }
}
