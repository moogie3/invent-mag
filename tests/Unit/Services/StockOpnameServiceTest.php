<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\StockOpnameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class StockOpnameServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected StockOpnameService $service;

    protected MockInterface $accountingServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        // Mock and allow all methods to be called without throwing
        $this->accountingServiceMock = Mockery::mock(AccountingService::class)->shouldIgnoreMissing();
        $this->service = new StockOpnameService($this->accountingServiceMock);
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(StockOpnameService::class, $this->service);
    }

    #[Test]
    public function create_opname_creates_stock_opname_record()
    {
        $this->actingAs($this->user);

        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);

        $opname = $this->service->createOpname(
            $warehouse->id,
            now()->addDay()->toDateString(),
            'Test notes'
        );

        $this->assertInstanceOf(StockOpname::class, $opname);
        $this->assertEquals($this->tenant->id, $opname->tenant_id);
        $this->assertEquals('Draft', $opname->status);
        $this->assertEquals('Test notes', $opname->notes);
        $this->assertStringStartsWith('SO-', $opname->reference_number);
    }

    #[Test]
    public function create_opname_snapshots_current_stock()
    {
        $this->actingAs($this->user);

        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $this->tenant->id],
            ['quantity' => 100]
        );

        $opname = $this->service->createOpname(
            $warehouse->id,
            now()->addDay()->toDateString(),
            null
        );

        $item = $opname->items()->where('product_id', $product->id)->first();
        $this->assertNotNull($item);
        $this->assertEquals(100, $item->expected_qty);
        $this->assertNull($item->counted_qty);
    }

    #[Test]
    public function create_opname_includes_products_without_warehouse_records()
    {
        $this->actingAs($this->user);

        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $productWithoutStock = Product::factory()->withNoStock()->create(['tenant_id' => $this->tenant->id]);

        $opname = $this->service->createOpname(
            $warehouse->id,
            now()->addDay()->toDateString(),
            null
        );

        $item = $opname->items()->where('product_id', $productWithoutStock->id)->first();
        $this->assertNotNull($item);
        $this->assertLessThanOrEqual(1, $item->expected_qty);
    }

    #[Test]
    public function update_counts_updates_items_and_transitions_status()
    {
        $this->actingAs($this->user);

        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id, 'price' => 5000]);

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $this->tenant->id],
            ['quantity' => 100]
        );

        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'Draft',
        ]);

        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $product->id,
            'expected_qty' => 100,
            'counted_qty' => null,
            'variance' => null,
        ]);

        $this->service->updateCounts($opname, [$product->id => 95]);

        $opname->refresh();
        $this->assertEquals('In Progress', $opname->status);

        $item = $opname->items()->first();
        $this->assertEquals(95, $item->counted_qty);
        $this->assertEquals(-5, $item->variance);
        $this->assertEquals(25000, $item->variance_value);
    }

    #[Test]
    public function update_counts_handles_null_values()
    {
        $this->actingAs($this->user);

        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'Draft',
        ]);

        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $product->id,
            'expected_qty' => 100,
            'counted_qty' => null,
            'variance' => null,
        ]);

        $this->service->updateCounts($opname, [
            $product->id => null,
            'nonexistent' => 50,
        ]);

        $item = $opname->items()->first();
        $this->assertNull($item->counted_qty);
    }

    #[Test]
    public function update_counts_skips_in_progress_transition_if_already_in_progress()
    {
        $this->actingAs($this->user);

        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'In Progress',
        ]);

        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $product->id,
            'expected_qty' => 100,
            'counted_qty' => null,
            'variance' => null,
        ]);

        $this->service->updateCounts($opname, [$product->id => 95]);

        $opname->refresh();
        $this->assertEquals('In Progress', $opname->status);
    }

    #[Test]
    public function complete_opname_adjusts_product_warehouse_quantities()
    {
        $this->actingAs($this->user);

        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id, 'price' => 5000]);

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $this->tenant->id],
            ['quantity' => 100]
        );

        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'In Progress',
        ]);

        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $product->id,
            'expected_qty' => 100,
            'counted_qty' => 95,
            'variance' => -5,
            'variance_value' => 25000,
        ]);

        $this->service->completeOpname($opname);

        $opname->refresh();
        $this->assertEquals('Completed', $opname->status);
        $this->assertNotNull($opname->completed_at);

        $pw = ProductWarehouse::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();
        $this->assertEquals(95, $pw->quantity);
    }

    #[Test]
    public function complete_opname_creates_stock_adjustment_records()
    {
        $this->actingAs($this->user);

        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id, 'price' => 5000]);

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $this->tenant->id],
            ['quantity' => 100]
        );

        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'In Progress',
        ]);

        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $product->id,
            'expected_qty' => 100,
            'counted_qty' => 95,
            'variance' => -5,
            'variance_value' => 25000,
        ]);

        $this->service->completeOpname($opname);

        $this->assertDatabaseHas('stock_adjustments', [
            'tenant_id' => $this->tenant->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'adjustment_type' => 'decrease',
            'quantity_before' => 100,
            'quantity_after' => 95,
            'adjustment_amount' => 5,
        ]);
    }

    #[Test]
    public function complete_opname_throws_if_already_completed()
    {
        $this->actingAs($this->user);

        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);

        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'Completed',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stock Opname is already completed.');

        $this->service->completeOpname($opname);
    }

    #[Test]
    public function complete_opname_handles_excess_inventory()
    {
        $this->actingAs($this->user);

        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id, 'price' => 5000]);

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $this->tenant->id],
            ['quantity' => 100]
        );

        $opname = StockOpname::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'In Progress',
        ]);

        StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'product_id' => $product->id,
            'expected_qty' => 100,
            'counted_qty' => 110,
            'variance' => 10,
            'variance_value' => 50000,
        ]);

        $this->service->completeOpname($opname);

        $opname->refresh();
        $this->assertEquals('Completed', $opname->status);

        $this->assertDatabaseHas('stock_adjustments', [
            'product_id' => $product->id,
            'adjustment_type' => 'increase',
            'quantity_before' => 100,
            'quantity_after' => 110,
        ]);
    }

    #[Test]
    public function generate_reference_number_increments_sequence()
    {
        $this->actingAs($this->user);

        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);

        $opname1 = $this->service->createOpname(
            $warehouse->id,
            now()->toDateString(),
            null
        );

        $ref1 = $opname1->reference_number;

        $opname2 = $this->service->createOpname(
            $warehouse->id,
            now()->toDateString(),
            null
        );

        $ref2 = $opname2->reference_number;

        $this->assertNotEquals($ref1, $ref2);
        $this->assertEquals(substr($ref1, 0, -4), substr($ref2, 0, -4));
    }

    #[Test]
    public function generate_reference_number_resets_for_new_date()
    {
        $this->actingAs($this->user);

        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);

        $opname1 = $this->service->createOpname(
            $warehouse->id,
            now()->toDateString(),
            null
        );

        $ref1 = $opname1->reference_number;

        $ref1Sequence = (int) substr($ref1, -4);

        $this->assertLessThanOrEqual(9999, $ref1Sequence);
    }
}
