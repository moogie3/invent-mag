<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('products')->insert([
            // Near low stock, no expiry
            [
                'code' => 'TR11',
                'name' => 'Near Low Stock Capacitor',
                'stock_quantity' => 9, // low_stock_threshold is 10, so this is low stock
                'low_stock_threshold' => 10,
                'price' => 600.00,
                'selling_price' => 1100.00,
                'category_id' => 1,
                'units_id' => 2,
                'supplier_id' => 1,
                'description' => 'Capacitor close to low stock',
                'warehouse_id' => 1,
                'image' => null,
                'has_expiry' => false,
                'expiry_date' => null,
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
                'category_id' => 1,
                'units_id' => 2,
                'supplier_id' => 2,
                'description' => 'Diode product already expired',
                'warehouse_id' => 1,
                'image' => null,
                'has_expiry' => true,
                'expiry_date' => now()->subDays(5), // expired 5 days ago
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
                'category_id' => 1,
                'units_id' => 2,
                'supplier_id' => 2,
                'description' => 'Crystal oscillator expiring soon',
                'warehouse_id' => 2,
                'image' => null,
                'has_expiry' => true,
                'expiry_date' => now()->addDays(10), // expires in 10 days
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
                'category_id' => 2,
                'units_id' => 2,
                'supplier_id' => 2,
                'description' => 'LED near expiry and low stock',
                'warehouse_id' => 1,
                'image' => null,
                'has_expiry' => true,
                'expiry_date' => now()->addDays(3), // expires in 3 days
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
                'category_id' => 1,
                'units_id' => 2,
                'supplier_id' => 3,
                'description' => 'Voltage regulator expired long ago',
                'warehouse_id' => 1,
                'image' => null,
                'has_expiry' => true,
                'expiry_date' => now()->subMonths(3), // expired 3 months ago
                'created_at' => now()->subYears(1),
                'updated_at' => now(),
            ],
        ]);
    }
}
