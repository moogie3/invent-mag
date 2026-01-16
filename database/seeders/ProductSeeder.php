<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Categories;
use App\Models\Unit;
use App\Models\Supplier;
use App\Models\Warehouse;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;

        // Fetch existing IDs for the current tenant
        $categoryIds = Categories::where('tenant_id', $tenantId)->pluck('id')->toArray();
        $unitIds = Unit::where('tenant_id', $tenantId)->pluck('id')->toArray();
        $supplierIds = Supplier::where('tenant_id', $tenantId)->pluck('id')->toArray();
        $warehouseIds = Warehouse::where('tenant_id', $tenantId)->pluck('id')->toArray();

        // Check if necessary data exists
        if (empty($categoryIds) || empty($unitIds) || empty($supplierIds) || empty($warehouseIds)) {
            $this->command->info('Skipping ProductSeeder for tenant ' . app('currentTenant')->name . ': Categories, Units, Suppliers, or Warehouses not found. Please ensure their seeders run correctly for this tenant.');
            return;
        }

        $faker = \Faker\Factory::create();

        Product::updateOrCreate([
            'code' => 'TR11',
            'tenant_id' => $tenantId,
        ],[
            'barcode' => '1234567890123' . '-' . $tenantId,
            'name' => 'Near Low Stock Capacitor',
            'stock_quantity' => 9, // low_stock_threshold is 10, so this is low stock
            'low_stock_threshold' => 10,
            'price' => 600.00,
            'selling_price' => 1100.00,
            'category_id' => $faker->randomElement($categoryIds),
            'units_id' => $faker->randomElement($unitIds),
            'supplier_id' => $faker->randomElement($supplierIds),
            'description' => 'Capacitor close to low stock',
            'warehouse_id' => $faker->randomElement($warehouseIds),
            'image' => null,
            'has_expiry' => false,
        ]);

        Product::updateOrCreate([
            'code' => 'TR12',
            'tenant_id' => $tenantId,
        ],[
            'barcode' => $faker->unique()->ean13() . '-' . $tenantId,
            'name' => 'Expired Diode',
            'stock_quantity' => 15,
            'low_stock_threshold' => 10,
            'price' => 180.00,
            'selling_price' => 350.00,
            'category_id' => $faker->randomElement($categoryIds),
            'units_id' => $faker->randomElement($unitIds),
            'supplier_id' => $faker->randomElement($supplierIds),
            'description' => 'Diode product already expired',
            'warehouse_id' => $faker->randomElement($warehouseIds),
            'image' => null,
            'has_expiry' => true,
        ]);

        Product::updateOrCreate([
            'code' => 'TR13',
            'tenant_id' => $tenantId,
        ],[
            'barcode' => $faker->unique()->ean13() . '-' . $tenantId,
            'name' => 'Near Expiry Crystal Oscillator',
            'stock_quantity' => 50,
            'low_stock_threshold' => 20,
            'price' => 950.00,
            'selling_price' => 1700.00,
            'category_id' => $faker->randomElement($categoryIds),
            'units_id' => $faker->randomElement($unitIds),
            'supplier_id' => $faker->randomElement($supplierIds),
            'description' => 'Crystal oscillator expiring soon',
            'warehouse_id' => $faker->randomElement($warehouseIds),
            'image' => null,
            'has_expiry' => true,
        ]);

        Product::updateOrCreate([
            'code' => 'TR14',
            'tenant_id' => $tenantId,
        ],[
            'barcode' => $faker->unique()->ean13() . '-' . $tenantId,
            'name' => 'Low Stock LED',
            'stock_quantity' => 0,
            'low_stock_threshold' => 5,
            'price' => 90.00,
            'selling_price' => 160.00,
            'category_id' => $faker->randomElement($categoryIds),
            'units_id' => $faker->randomElement($unitIds),
            'supplier_id' => $faker->randomElement($supplierIds),
            'description' => 'LED near expiry and low stock',
            'warehouse_id' => $faker->randomElement($warehouseIds),
            'image' => null,
            'has_expiry' => true,
        ]);

        Product::updateOrCreate([
            'code' => 'TR15',
            'tenant_id' => $tenantId,
        ],[
            'barcode' => $faker->unique()->ean13() . '-' . $tenantId,
            'name' => 'Expired Voltage Regulator',
            'stock_quantity' => 0,
            'low_stock_threshold' => 10,
            'price' => 1400.00,
            'selling_price' => 2600.00,
            'category_id' => $faker->randomElement($categoryIds),
            'units_id' => $faker->randomElement($unitIds),
            'supplier_id' => $faker->randomElement($supplierIds),
            'description' => 'Voltage regulator expired long ago',
            'warehouse_id' => $faker->randomElement($warehouseIds),
            'image' => null,
            'has_expiry' => true,
        ]);
    }
}
