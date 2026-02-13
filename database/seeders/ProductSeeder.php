<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Categories;
use App\Models\Unit;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\ProductWarehouse;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;
        $tenantName = app('currentTenant')->name;

        // Fetch existing IDs for the current tenant
        $categoryIds = Categories::where('tenant_id', $tenantId)->pluck('id')->toArray();
        $unitIds = Unit::where('tenant_id', $tenantId)->pluck('id')->toArray();
        $supplierIds = Supplier::where('tenant_id', $tenantId)->pluck('id')->toArray();
        $warehouseIds = Warehouse::where('tenant_id', $tenantId)->pluck('id')->toArray();

        // Check if necessary data exists
        if (empty($categoryIds) || empty($unitIds) || empty($supplierIds) || empty($warehouseIds)) {
            $this->command->warn('Skipping ProductSeeder for tenant ' . $tenantName . ': Categories, Units, Suppliers, or Warehouses not found. Please ensure their seeders run correctly for this tenant.');
            return;
        }

        $this->command->info("Seeding test products for tenant: {$tenantName}");
        $this->command->info("Available: " . count($categoryIds) . " categories, " . count($unitIds) . " units, " . count($supplierIds) . " suppliers, " . count($warehouseIds) . " warehouses");

        $faker = \Faker\Factory::create();

        $product1 = Product::updateOrCreate([
            'code' => 'TR11',
            'tenant_id' => $tenantId,
        ],[
            'barcode' => '1234567890123' . '-' . $tenantId,
            'name' => 'Near Low Stock Capacitor',
            // 'stock_quantity' => 9, // low_stock_threshold is 10, so this is low stock
            'low_stock_threshold' => 10,
            'price' => 600.00,
            'selling_price' => 1100.00,
            'category_id' => $faker->randomElement($categoryIds),
            'units_id' => $faker->randomElement($unitIds),
            'supplier_id' => $faker->randomElement($supplierIds),
            'description' => 'Capacitor close to low stock',
            // 'warehouse_id' => $faker->randomElement($warehouseIds), // Removed
            'image' => null,
            'has_expiry' => false,
        ]);
        
        // Add stock to main warehouse
        ProductWarehouse::updateOrCreate([
            'product_id' => $product1->id,
            'warehouse_id' => $faker->randomElement($warehouseIds),
            'tenant_id' => $tenantId,
        ],[
            'quantity' => 9,
        ]);

        $product2 = Product::updateOrCreate([
            'code' => 'TR12',
            'tenant_id' => $tenantId,
        ],[
            'barcode' => $faker->unique()->ean13() . '-' . $tenantId,
            'name' => 'Expired Diode',
            // 'stock_quantity' => 15,
            'low_stock_threshold' => 10,
            'price' => 180.00,
            'selling_price' => 350.00,
            'category_id' => $faker->randomElement($categoryIds),
            'units_id' => $faker->randomElement($unitIds),
            'supplier_id' => $faker->randomElement($supplierIds),
            'description' => 'Diode product already expired',
            // 'warehouse_id' => $faker->randomElement($warehouseIds),
            'image' => null,
            'has_expiry' => true,
        ]);

        ProductWarehouse::updateOrCreate([
            'product_id' => $product2->id,
            'warehouse_id' => $faker->randomElement($warehouseIds),
            'tenant_id' => $tenantId,
        ],[
            'quantity' => 15,
        ]);

        $product3 = Product::updateOrCreate([
            'code' => 'TR13',
            'tenant_id' => $tenantId,
        ],[
            'barcode' => $faker->unique()->ean13() . '-' . $tenantId,
            'name' => 'Near Expiry Crystal Oscillator',
            // 'stock_quantity' => 50,
            'low_stock_threshold' => 20,
            'price' => 950.00,
            'selling_price' => 1700.00,
            'category_id' => $faker->randomElement($categoryIds),
            'units_id' => $faker->randomElement($unitIds),
            'supplier_id' => $faker->randomElement($supplierIds),
            'description' => 'Crystal oscillator expiring soon',
            // 'warehouse_id' => $faker->randomElement($warehouseIds),
            'image' => null,
            'has_expiry' => true,
        ]);

        ProductWarehouse::updateOrCreate([
            'product_id' => $product3->id,
            'warehouse_id' => $faker->randomElement($warehouseIds),
            'tenant_id' => $tenantId,
        ],[
            'quantity' => 50,
        ]);

        $product4 = Product::updateOrCreate([
            'code' => 'TR14',
            'tenant_id' => $tenantId,
        ],[
            'barcode' => $faker->unique()->ean13() . '-' . $tenantId,
            'name' => 'Low Stock LED',
            // 'stock_quantity' => 0,
            'low_stock_threshold' => 5,
            'price' => 90.00,
            'selling_price' => 160.00,
            'category_id' => $faker->randomElement($categoryIds),
            'units_id' => $faker->randomElement($unitIds),
            'supplier_id' => $faker->randomElement($supplierIds),
            'description' => 'LED near expiry and low stock',
            // 'warehouse_id' => $faker->randomElement($warehouseIds),
            'image' => null,
            'has_expiry' => true,
        ]);

        ProductWarehouse::updateOrCreate([
            'product_id' => $product4->id,
            'warehouse_id' => $faker->randomElement($warehouseIds),
            'tenant_id' => $tenantId,
        ],[
            'quantity' => 0,
        ]);

        $product5 = Product::updateOrCreate([
            'code' => 'TR15',
            'tenant_id' => $tenantId,
        ],[
            'barcode' => $faker->unique()->ean13() . '-' . $tenantId,
            'name' => 'Expired Voltage Regulator',
            // 'stock_quantity' => 0,
            'low_stock_threshold' => 10,
            'price' => 1400.00,
            'selling_price' => 2600.00,
            'category_id' => $faker->randomElement($categoryIds),
            'units_id' => $faker->randomElement($unitIds),
            'supplier_id' => $faker->randomElement($supplierIds),
            'description' => 'Voltage regulator expired long ago',
            // 'warehouse_id' => $faker->randomElement($warehouseIds),
            'image' => null,
            'has_expiry' => true,
        ]);

        ProductWarehouse::updateOrCreate([
            'product_id' => $product5->id,
            'warehouse_id' => $faker->randomElement($warehouseIds),
            'tenant_id' => $tenantId,
        ],[
            'quantity' => 0,
        ]);
        
        $this->command->info("Products seeded successfully!");
        $this->command->info("Created 5 test products with warehouse stock:");
        $this->command->info("  - Near Low Stock Capacitor (qty: 9, threshold: 10)");
        $this->command->info("  - Expired Diode (qty: 15, has expiry)");
        $this->command->info("  - Near Expiry Crystal Oscillator (qty: 50)");
        $this->command->info("  - Low Stock LED (qty: 0)");
        $this->command->info("  - Expired Voltage Regulator (qty: 0)");
    }
}
