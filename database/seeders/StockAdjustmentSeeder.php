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

        $this->command->info("Seeding Initial Stock for tenant: " . app('currentTenant')->name);

        // Get IDs of products that already have stock (e.g. from ProductSeeder) to avoid double-stocking
        $productsWithExistingStock = ProductWarehouse::where('tenant_id', $tenantId)
            ->where('quantity', '>', 0)
            ->pluck('product_id')
            ->toArray();

        $products = $products->reject(fn ($p) => in_array($p->id, $productsWithExistingStock));
        $totalProducts = $products->count();

        if ($totalProducts === 0) {
            $this->command->info("All products already have stock. Skipping stock adjustment seeding.");
            return;
        }

        $totalValue = 0;
        $this->command->getOutput()->progressStart($totalProducts);

        foreach ($products as $product) {
            $warehouse = $warehouses->random();
            $initialQty = rand(100, 500);
            $totalValue += $initialQty * ($product->price ?? 0);

            $stockRecord = ProductWarehouse::firstOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $tenantId],
                ['quantity' => 0]
            );

            StockAdjustment::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'adjustment_type' => 'increase',
                'quantity_before' => $stockRecord->quantity,
                'quantity_after' => $stockRecord->quantity + $initialQty,
                'adjustment_amount' => $initialQty,
                'reason' => 'Initial stock adjustment',
                'adjusted_by' => $users->random()->id,
                'tenant_id' => $tenantId,
            ]);

            $stockRecord->increment('quantity', $initialQty);

            $this->command->getOutput()->progressAdvance();
        }

        $this->command->getOutput()->progressFinish();

        $this->command->info("Stock seeding completed!");
        $this->command->info("Created initial stock records for {$totalProducts} products (100-500 qty each). Total value: \$" . number_format($totalValue, 2));
    }
}
