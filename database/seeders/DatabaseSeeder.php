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
        UserSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
        SuperUserSeeder::class,
        CategorySeeder::class,
        UnitSeeder::class,
        SupplierSeeder::class,
        WarehouseSeeder::class,
        ProductSeeder::class,
        CustomerSeeder::class,
        CurrencySeeder::class,
        PurchaseSeeder::class,
        SalesSeeder::class,
       ]);
    }
}
