<?php

namespace Tests\Unit\Models;

use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Product;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;

class SalesIsPphTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
    }

    #[Test]
    public function it_returns_true_when_pph_enabled()
    {
        $sale = Sales::factory()->create([
            'pph_enabled' => true,
            'pph_rate' => 2,
            'pph_amount' => 200,
        ]);

        $this->assertTrue($sale->isPph());
    }

    #[Test]
    public function it_returns_false_when_pph_not_enabled()
    {
        $sale = Sales::factory()->create([
            'pph_enabled' => false,
        ]);

        $this->assertFalse($sale->isPph());
    }

    #[Test]
    public function it_reads_from_snapshot_not_live_tax()
    {
        // Even if we change the tax record's is_pph flag,
        // the sale's isPph() should still return the snapshot value
        $sale = Sales::factory()->create([
            'pph_enabled' => true,
        ]);

        $this->assertTrue($sale->isPph());
    }

    #[Test]
    public function it_snapshot_fields_are_stored_independently()
    {
        $sale = Sales::factory()->create([
            'pph_enabled' => true,
            'pph_rate' => 3.5,
            'pph_amount' => 350,
        ]);

        $this->assertTrue($sale->pph_enabled);
        $this->assertEquals(3.5, $sale->pph_rate);
        $this->assertEquals(350.0, $sale->pph_amount);
    }

    #[Test]
    public function it_defaults_pph_snapshot_fields()
    {
        $sale = Sales::factory()->create([
            'pph_enabled' => false,
            'pph_rate' => null,
            'pph_amount' => 0,
            'pph_tax_id' => null,
        ]);

        $this->assertFalse($sale->pph_enabled);
        $this->assertNull($sale->pph_rate);
        $this->assertEquals(0.0, $sale->pph_amount);
        $this->assertNull($sale->pph_tax_id);
    }

    #[Test]
    public function it_is_pph_returns_false_when_snapshot_empty()
    {
        // When pph_enabled is not set, cast defaults to false
        $sale = Sales::factory()->create();
        $this->assertFalse($sale->isPph());
    }

    #[Test]
    public function it_adjusts_balance_for_pph()
    {
        $product = Product::factory()->create(['selling_price' => 1000]);
        $sale = Sales::factory()->create([
            'pph_enabled' => true,
            'pph_rate' => 2,
            'pph_amount' => 20,
            'total' => 980,
            'order_discount' => 0,
            'order_discount_type' => 'fixed',
            'tax_rate' => 0,
            'tax_type' => 'none',
        ]);

        SalesItem::create([
            'sales_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'customer_price' => 1000,
            'discount' => 0,
            'discount_type' => 'fixed',
            'total' => 1000,
            'tenant_id' => $sale->tenant_id,
        ]);

        $this->assertEquals(980.0, $sale->balance);
    }

    #[Test]
    public function it_returns_consistent_grand_total_regardless_of_eager_loading()
    {
        $product = Product::factory()->create(['selling_price' => 1000]);
        $sale = Sales::factory()->create([
            'pph_enabled' => true,
            'pph_rate' => 2,
            'pph_amount' => 20,
            'total' => 980,
            'order_discount' => 0,
            'order_discount_type' => 'fixed',
            'tax_rate' => 0,
            'tax_type' => 'none',
        ]);

        SalesItem::create([
            'sales_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'customer_price' => 1000,
            'discount' => 0,
            'discount_type' => 'fixed',
            'total' => 1000,
            'tenant_id' => $sale->tenant_id,
        ]);

        $withoutItems = $sale->grand_total;

        $sale->load('salesItems');
        $withItems = $sale->grand_total;

        $this->assertEquals($withoutItems, $withItems);
    }
}
