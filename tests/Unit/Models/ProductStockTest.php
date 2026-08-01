<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ProductStockTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
    }

    #[Test]
    public function it_returns_main_warehouse_stock()
    {
        $mainWarehouse = Warehouse::factory()->create(['is_main' => true]);
        $otherWarehouse = Warehouse::factory()->create(['is_main' => false]);
        $product = Product::factory()->create();

        // Clear any existing stock first
        $product->productWarehouses()->delete();

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $mainWarehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 50]
        );

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $otherWarehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 30]
        );

        $this->assertEquals(50, $product->main_warehouse_stock);
    }

    #[Test]
    public function it_returns_zero_when_no_main_warehouse_stock()
    {
        $otherWarehouse = Warehouse::factory()->create(['is_main' => false]);
        $product = Product::factory()->create();

        // Clear any existing stock first
        $product->productWarehouses()->delete();

        $this->assertEquals(0, $product->main_warehouse_stock);
    }

    #[Test]
    public function it_returns_main_warehouse_id()
    {
        $mainWarehouse = Warehouse::factory()->create(['is_main' => true]);
        $product = Product::factory()->create();

        $this->assertEquals($mainWarehouse->id, $product->main_warehouse_id);
    }

    #[Test]
    public function it_can_get_stock_for_specific_warehouse()
    {
        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();
        $product = Product::factory()->create();

        // Clear any existing stock first
        $product->productWarehouses()->delete();

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse1->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 25]
        );

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse2->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 15]
        );

        $this->assertEquals(25, $product->getWarehouseStock($warehouse1->id));
        $this->assertEquals(15, $product->getWarehouseStock($warehouse2->id));
    }

    #[Test]
    public function it_returns_zero_when_no_stock_for_warehouse()
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        // Clear any existing stock first
        $product->productWarehouses()->delete();

        $this->assertEquals(0, $product->getWarehouseStock($warehouse->id));
    }

    #[Test]
    public function it_can_check_sufficient_stock()
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        // Clear any existing stock first
        $product->productWarehouses()->delete();

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 10]
        );

        $this->assertTrue($product->hasSufficientStock(5, $warehouse->id));
        $this->assertTrue($product->hasSufficientStock(10, $warehouse->id));
        $this->assertFalse($product->hasSufficientStock(15, $warehouse->id));
    }

    #[Test]
    public function it_can_deduct_stock_with_validation()
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        // Clear any existing stock first
        $product->productWarehouses()->delete();

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 20]
        );

        $product->deductStock(5, $warehouse->id);

        $this->assertEquals(15, $product->fresh()->getWarehouseStock($warehouse->id));
    }

    #[Test]
    public function it_can_add_stock()
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        // Clear any existing stock first
        $product->productWarehouses()->delete();

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 10]
        );

        $product->addStock(5, $warehouse->id);

        $this->assertEquals(15, $product->fresh()->getWarehouseStock($warehouse->id));
    }

    #[Test]
    public function it_can_check_low_stock()
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create(['low_stock_threshold' => 10]);

        // Clear any existing stock first
        $product->productWarehouses()->delete();

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 5]
        );

        $this->assertTrue($product->isLowStock($warehouse->id));
    }

    #[Test]
    public function it_can_check_out_of_stock()
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        // Clear any existing stock first
        $product->productWarehouses()->delete();

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 0]
        );

        $this->assertTrue($product->isOutOfStock($warehouse->id));
    }

    #[Test]
    public function it_returns_total_stock_across_all_warehouses()
    {
        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();
        $product = Product::factory()->create();

        // Clear any existing stock first
        $product->productWarehouses()->delete();

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse1->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 30]
        );

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse2->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 20]
        );

        $this->assertEquals(50, $product->total_stock);
    }
}
