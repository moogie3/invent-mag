<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class StockTransferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tenantId = app('currentTenant')->id;

        $warehouses = Warehouse::where('tenant_id', $tenantId)->get();
        
        if ($warehouses->count() < 2) {
            $this->command->info('Skipping StockTransferSeeder for tenant ' . app('currentTenant')->name . ': Need at least 2 warehouses.');
            return;
        }

        // Get products that have stock in at least one warehouse
        $productsWithStock = Product::whereHas('productWarehouses', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId)
                  ->where('quantity', '>', 10);
        })->where('tenant_id', $tenantId)->get();

        if ($productsWithStock->isEmpty()) {
            $this->command->info('Skipping StockTransferSeeder for tenant ' . app('currentTenant')->name . ': No products with sufficient stock.');
            return;
        }

        $transferReasons = [
            'Balancing inventory between warehouses',
            'Stock replenishment for branch',
            'Transfer to meet customer demand',
            'Seasonal inventory adjustment',
            'Consolidating slow-moving stock',
            'Preparing for promotion at branch location',
        ];

        $this->command->info('Creating stock transfers for tenant: ' . app('currentTenant')->name);
        $transferCount = 0;

        // Create 20-30 stock transfers
        for ($i = 0; $i < rand(20, 30); $i++) {
            // Pick random warehouses (ensure they're different)
            $fromWarehouse = $warehouses->random();
            $toWarehouse = $warehouses->where('id', '!=', $fromWarehouse->id)->random();

            // Pick a product that has stock in the from warehouse
            $availableProducts = $productsWithStock->filter(function ($product) use ($fromWarehouse) {
                return $product->productWarehouses
                    ->where('warehouse_id', $fromWarehouse->id)
                    ->where('quantity', '>', 10)
                    ->isNotEmpty();
            });

            if ($availableProducts->isEmpty()) {
                continue;
            }

            $product = $availableProducts->random();
            
            // Get current stock
            $fromStockRecord = ProductWarehouse::where('product_id', $product->id)
                ->where('warehouse_id', $fromWarehouse->id)
                ->first();

            if (!$fromStockRecord || $fromStockRecord->quantity <= 10) {
                continue;
            }

            // Calculate transfer quantity (between 5 and 50% of available stock)
            $maxTransfer = (int)($fromStockRecord->quantity * 0.5);
            $quantity = rand(5, max(5, $maxTransfer));

            // Ensure we don't transfer more than available
            if ($quantity >= $fromStockRecord->quantity) {
                $quantity = (int)($fromStockRecord->quantity * 0.3);
            }

            $fromQty = $fromStockRecord->quantity;

            // Get or create destination stock record
            $toStockRecord = ProductWarehouse::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $toWarehouse->id,
                    'tenant_id' => $tenantId,
                ],
                ['quantity' => 0]
            );
            $toQty = $toStockRecord->quantity;

            // Perform the transfer
            DB::transaction(function () use (
                $product, 
                $fromWarehouse, 
                $toWarehouse, 
                $fromStockRecord, 
                $toStockRecord, 
                $fromQty, 
                $toQty, 
                $quantity, 
                $transferReasons,
                $tenantId
            ) {
                // Update quantities
                $fromStockRecord->update(['quantity' => $fromQty - $quantity]);
                $toStockRecord->update(['quantity' => $toQty + $quantity]);

                // Create stock adjustments
                StockAdjustment::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $fromWarehouse->id,
                    'adjustment_type' => 'transfer',
                    'quantity_before' => $fromQty,
                    'quantity_after' => $fromQty - $quantity,
                    'adjustment_amount' => $quantity,
                    'reason' => collect($transferReasons)->random() . " (to {$toWarehouse->name})",
                    'adjusted_by' => 1, // System user
                    'tenant_id' => $tenantId,
                ]);

                StockAdjustment::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $toWarehouse->id,
                    'adjustment_type' => 'transfer',
                    'quantity_before' => $toQty,
                    'quantity_after' => $toQty + $quantity,
                    'adjustment_amount' => $quantity,
                    'reason' => collect($transferReasons)->random() . " (from {$fromWarehouse->name})",
                    'adjusted_by' => 1, // System user
                    'tenant_id' => $tenantId,
                ]);
            });

            $transferCount++;
        }

        $this->command->info("Created {$transferCount} stock transfers successfully.");
        
        // Display summary
        $transferAdjustments = StockAdjustment::where('adjustment_type', 'transfer')
            ->where('tenant_id', $tenantId)
            ->count();
        $this->command->info("Total transfer records in adjustment log: {$transferAdjustments}");
    }
}
