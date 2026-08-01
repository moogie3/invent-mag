<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class StockOpnameSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;

        $products = Product::where('tenant_id', $tenantId)->limit(10)->get();
        $users = User::where('tenant_id', $tenantId)->get();
        $warehouses = Warehouse::where('tenant_id', $tenantId)->get();

        if ($products->isEmpty() || $users->isEmpty() || $warehouses->isEmpty()) {
            if ($this->command) {
                $this->command->warn('Skipping StockOpnameSeeder: Missing dependency data.');
            }

            return;
        }

        if ($this->command) {
            $this->command->info('Seeding Stock Opnames...');
        }

        $defaultWarehouse = $warehouses->first();
        $defaultUser = $users->first();

        // 1. Draft Stock Opname
        $draftOpname = StockOpname::create([
            'tenant_id' => $tenantId,
            'warehouse_id' => $defaultWarehouse->id,
            'reference_number' => 'SO-'.date('ymd').'-0001',
            'status' => 'Draft',
            'scheduled_date' => now()->addDays(7),
            'notes' => 'Monthly scheduled stock opname',
            'created_by' => $defaultUser->id,
        ]);

        foreach ($products->take(5) as $product) {
            StockOpnameItem::create([
                'stock_opname_id' => $draftOpname->id,
                'product_id' => $product->id,
                'expected_qty' => rand(10, 100),
                'counted_qty' => null,
                'variance' => null,
                'variance_value' => null,
            ]);
        }

        if ($this->command) {
            $this->command->info("Created: {$draftOpname->reference_number}");
        }

        // 2. In Progress Stock Opname
        $inProgressOpname = StockOpname::create([
            'tenant_id' => $tenantId,
            'warehouse_id' => $defaultWarehouse->id,
            'reference_number' => 'SO-'.date('ymd').'-0002',
            'status' => 'In Progress',
            'scheduled_date' => now()->subDays(2),
            'notes' => 'Quick count - partial',
            'created_by' => $defaultUser->id,
        ]);

        foreach ($products->take(5) as $index => $product) {
            $expected = rand(10, 100);
            $counted = $index < 3 ? $expected + rand(-5, 5) : null;
            $variance = $counted !== null ? $counted - $expected : null;

            StockOpnameItem::create([
                'stock_opname_id' => $inProgressOpname->id,
                'product_id' => $product->id,
                'expected_qty' => $expected,
                'counted_qty' => $counted,
                'variance' => $variance,
                'variance_value' => null,
            ]);
        }

        if ($this->command) {
            $this->command->info("Created: {$inProgressOpname->reference_number}");
        }

        // 3. Completed Stock Opname
        $completedOpname = StockOpname::create([
            'tenant_id' => $tenantId,
            'warehouse_id' => $defaultWarehouse->id,
            'reference_number' => 'SO-'.date('ymd').'-0003',
            'status' => 'Completed',
            'scheduled_date' => now()->subDays(10),
            'completed_at' => now()->subDays(9),
            'notes' => 'Q1 2026 stock audit',
            'created_by' => $defaultUser->id,
        ]);

        foreach ($products->take(5) as $product) {
            $expected = rand(20, 80);
            $counted = $expected + rand(-3, 3);
            $variance = $counted - $expected;

            StockOpnameItem::create([
                'stock_opname_id' => $completedOpname->id,
                'product_id' => $product->id,
                'expected_qty' => $expected,
                'counted_qty' => $counted,
                'variance' => $variance,
                'variance_value' => abs($variance) * ($product->cost ?? 10000),
            ]);
        }

        if ($this->command) {
            $this->command->info("Created: {$completedOpname->reference_number}");
            $this->command->info('Done!');
        }
    }
}
