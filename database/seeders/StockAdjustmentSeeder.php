<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\StockAdjustment;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\ProductWarehouse;

class StockAdjustmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;

        $products = Product::where('tenant_id', $tenantId)->get();
        $users = User::where('tenant_id', $tenantId)->get();

        $warehouses = Warehouse::where('tenant_id', $tenantId)->get();

        if ($products->isEmpty() || $users->isEmpty() || $warehouses->isEmpty()) {
            $this->command->warn('Skipping StockAdjustmentSeeder for tenant ' . app('currentTenant')->name . ': Missing dependency data.');
            return;
        }

        $this->command->info("Seeding Initial Stock Adjustments for tenant: " . app('currentTenant')->name);
        $totalProducts = $products->count();
        $this->command->getOutput()->progressStart($totalProducts);

        foreach ($products as $product) {
            $warehouse = $warehouses->random();
            
            // Get current stock from pivot or init at 0
            $stockRecord = ProductWarehouse::firstOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $tenantId],
                ['quantity' => 0]
            );

            StockAdjustment::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id, // Added
                'adjustment_type' => 'increase',
                'quantity_before' => $stockRecord->quantity,
                'quantity_after' => $stockRecord->quantity + 10,
                'adjustment_amount' => 10,
                'reason' => 'Initial stock adjustment',
                'adjusted_by' => $users->random()->id,
                'tenant_id' => $tenantId,
            ]);
            
            $stockRecord->increment('quantity', 10);
            
            $this->command->getOutput()->progressAdvance();
        }
        
        $this->command->getOutput()->progressFinish();
        $this->command->info("Stock Adjustment seeding completed!");
        $this->command->info("Created initial stock increase records for {$totalProducts} products (+10 qty each).");
    }
}
