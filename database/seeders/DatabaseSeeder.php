<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
       $this->call([
            AccountSeeder::class,
            UserSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            SuperUserSeeder::class,
            RolePermissionSeeder::class,
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
            SalesPipelineSeeder::class,
            PipelineStageSeeder::class,
            SalesOpportunitySeeder::class,
        ]);
    }
}