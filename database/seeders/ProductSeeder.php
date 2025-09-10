<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Categories;
use App\Models\Unit;
use App\Models\Supplier;
use App\Models\Warehouse;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch existing IDs
        $categoryIds = Categories::pluck('id')->toArray();
        $unitIds = Unit::pluck('id')->toArray();
        $supplierIds = Supplier::pluck('id')->toArray();
        $warehouseIds = Warehouse::pluck('id')->toArray();

        // Check if necessary data exists
        if (empty($categoryIds) || empty($unitIds) || empty($supplierIds) || empty($warehouseIds)) {
            $this->command->info('Skipping ProductSeeder: Categories, Units, Suppliers, or Warehouses not found. Please run their seeders first.');
            return;
        }

        DB::table('products')->insert([
            // Near low stock, no expiry
            [
                'code' => 'TR11',
                'name' => 'Near Low Stock Capacitor',
                'stock_quantity' => 9, // low_stock_threshold is 10, so this is low stock
                'low_stock_threshold' => 10,
                'price' => 600.00,
                'selling_price' => 1100.00,
                'category_id' => $categoryIds[array_rand($categoryIds)],
                'units_id' => $unitIds[array_rand($unitIds)],
                'supplier_id' => $supplierIds[array_rand($supplierIds)],
                'description' => 'Capacitor close to low stock',
                'warehouse_id' => $warehouseIds[array_rand($warehouseIds)],
                'image' => null,
                'has_expiry' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Expired product
            [
                'code' => 'TR12',
                'name' => 'Expired Diode',
                'stock_quantity' => 15,
                'low_stock_threshold' => 10,
                'price' => 180.00,
                'selling_price' => 350.00,
                'category_id' => $categoryIds[array_rand($categoryIds)],
                'units_id' => $unitIds[array_rand($unitIds)],
                'supplier_id' => $supplierIds[array_rand($supplierIds)],
                'description' => 'Diode product already expired',
                'warehouse_id' => $warehouseIds[array_rand($warehouseIds)],
                'image' => null,
                'has_expiry' => true,
                'created_at' => now()->subMonths(6),
                'updated_at' => now(),
            ],
            // About to expire in 10 days
            [
                'code' => 'TR13',
                'name' => 'Near Expiry Crystal Oscillator',
                'stock_quantity' => 50,
                'low_stock_threshold' => 20,
                'price' => 950.00,
                'selling_price' => 1700.00,
                'category_id' => $categoryIds[array_rand($categoryIds)],
                'units_id' => $unitIds[array_rand($unitIds)],
                'supplier_id' => $supplierIds[array_rand($supplierIds)],
                'description' => 'Crystal oscillator expiring soon',
                'warehouse_id' => $warehouseIds[array_rand($warehouseIds)],
                'image' => null,
                'has_expiry' => true,
                'created_at' => now()->subMonths(2),
                'updated_at' => now(),
            ],
            // Near low stock and about to expire in 3 days
            [
                'code' => 'TR14',
                'name' => 'Low Stock LED',
                'stock_quantity' => 4,
                'low_stock_threshold' => 5,
                'price' => 90.00,
                'selling_price' => 160.00,
                'category_id' => $categoryIds[array_rand($categoryIds)],
                'units_id' => $unitIds[array_rand($unitIds)],
                'supplier_id' => $supplierIds[array_rand($supplierIds)],
                'description' => 'LED near expiry and low stock',
                'warehouse_id' => $warehouseIds[array_rand($warehouseIds)],
                'image' => null,
                'has_expiry' => true,
                'created_at' => now()->subMonths(1),
                'updated_at' => now(),
            ],
            // Stock is fine, expired long ago (should NOT show low stock)
            [
                'code' => 'TR15',
                'name' => 'Expired Voltage Regulator',
                'stock_quantity' => 100,
                'low_stock_threshold' => 10,
                'price' => 1400.00,
                'selling_price' => 2600.00,
                'category_id' => $categoryIds[array_rand($categoryIds)],
                'units_id' => $unitIds[array_rand($unitIds)],
                'supplier_id' => $supplierIds[array_rand($supplierIds)],
                'description' => 'Voltage regulator expired long ago',
                'warehouse_id' => $warehouseIds[array_rand($warehouseIds)],
                'image' => null,
                'has_expiry' => true,
                'created_at' => now()->subYears(1),
                'updated_at' => now(),
            ],
        ]);
    }
}
