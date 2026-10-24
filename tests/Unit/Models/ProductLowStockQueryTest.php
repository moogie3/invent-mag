<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ProductLowStockQueryTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private Warehouse $mainWarehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->mainWarehouse = Warehouse::factory()->create(['is_main' => true]);
    }

    #[Test]
    public function it_counts_products_with_a_null_threshold_as_low_stock()
    {
        $product = Product::factory()->withStock(5)->create(['low_stock_threshold' => null]);

        $modalIds = Product::getLowStockProductsPerWarehouse()->pluck('product_id');

        $this->assertTrue($modalIds->contains($product->id));
        $this->assertSame(1, Product::lowStockCount());
        $this->assertSame(1, Product::lowStockCountForWarehouse($this->mainWarehouse->id));
        $this->assertSame(1, Product::lowStockCountAllWarehouses());
        $this->assertTrue(Product::getLowStockProducts()->pluck('product_id')->contains($product->id));
        $this->assertSame(
            1,
            (int) (Product::lowStockCountPerWarehouse()[$this->mainWarehouse->name] ?? 0)
        );
    }

    #[Test]
    public function it_excludes_a_null_threshold_product_above_the_default_threshold()
    {
        Product::factory()->withStock(50)->create(['low_stock_threshold' => null]);

        $this->assertCount(0, Product::getLowStockProductsPerWarehouse());
        $this->assertSame(0, Product::lowStockCount());
        $this->assertSame(0, Product::lowStockCountAllWarehouses());
    }

    #[Test]
    public function it_still_respects_a_custom_threshold()
    {
        $below = Product::factory()->withStock(4)->create(['low_stock_threshold' => 5]);
        $above = Product::factory()->withStock(7)->create(['low_stock_threshold' => 5]);

        $ids = Product::getLowStockProductsPerWarehouse()->pluck('product_id');

        $this->assertTrue($ids->contains($below->id));
        $this->assertFalse($ids->contains($above->id));
        $this->assertSame(1, Product::lowStockCountAllWarehouses());
    }
}
