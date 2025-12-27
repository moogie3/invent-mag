<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\StockAdjustment;
use App\Models\Product;
use App\Models\User;

class StockAdjustmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();
        $users = User::all();

        if ($products->isEmpty() || $users->isEmpty()) {
            $this->command->info('Skipping StockAdjustmentSeeder: Products or Users not found. Please run their seeders first.');
            return;
        }

        foreach ($products as $product) {
            StockAdjustment::create([
                'product_id' => $product->id,
                'adjustment_type' => 'increase',
                'quantity_before' => $product->stock_quantity,
                'quantity_after' => $product->stock_quantity + 10,
                'adjustment_amount' => 10,
                'reason' => 'Initial stock adjustment',
                'adjusted_by' => $users->random()->id,
            ]);
            $product->increment('stock_quantity', 10);
        }
    }
}
