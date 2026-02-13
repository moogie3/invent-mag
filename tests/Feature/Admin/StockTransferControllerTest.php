<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class StockTransferControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
    }

    public function test_index_displays_stock_transfer_page()
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.stock-transfer.page'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.stock-transfer');
        $response->assertViewHas('warehouses');
    }

    public function test_store_creates_stock_transfer()
    {
        $fromWarehouse = Warehouse::factory()->create();
        $toWarehouse = Warehouse::factory()->create();
        
        // Create product with stock in from warehouse using factory
        $product = Product::factory()->create();
        $productWarehouse = $product->productWarehouses()->first();
        $productWarehouse->update([
            'warehouse_id' => $fromWarehouse->id,
            'quantity' => 100,
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.reports.stock-transfer'), [
            'from_warehouse_id' => $fromWarehouse->id,
            'to_warehouse_id' => $toWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 50,
            'reason' => 'Test transfer',
        ]);

        $response->assertRedirect(route('admin.reports.adjustment-log'));
        $response->assertSessionHas('success');

        // Verify stock was reduced in from warehouse
        $fromStock = ProductWarehouse::where('product_id', $product->id)
            ->where('warehouse_id', $fromWarehouse->id)
            ->first();
        $this->assertEquals(50, $fromStock->quantity);

        // Verify stock was added to to warehouse
        $toStock = ProductWarehouse::where('product_id', $product->id)
            ->where('warehouse_id', $toWarehouse->id)
            ->first();
        $this->assertNotNull($toStock);
        $this->assertEquals(50, $toStock->quantity);

        // Verify stock adjustments were created
        $this->assertDatabaseHas('stock_adjustments', [
            'product_id' => $product->id,
            'warehouse_id' => $fromWarehouse->id,
            'adjustment_type' => 'transfer',
            'quantity_before' => 100,
            'quantity_after' => 50,
        ]);

        $this->assertDatabaseHas('stock_adjustments', [
            'product_id' => $product->id,
            'warehouse_id' => $toWarehouse->id,
            'adjustment_type' => 'transfer',
            'quantity_before' => 0,
            'quantity_after' => 50,
        ]);
    }

    public function test_store_validates_warehouses_must_be_different()
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)->post(route('admin.reports.stock-transfer'), [
            'from_warehouse_id' => $warehouse->id,
            'to_warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 50,
        ]);

        $response->assertSessionHasErrors('to_warehouse_id');
    }

    public function test_store_validates_sufficient_stock()
    {
        $fromWarehouse = Warehouse::factory()->create();
        $toWarehouse = Warehouse::factory()->create();
        
        // Create product with only 10 units in stock
        $product = Product::factory()->create();
        $productWarehouse = $product->productWarehouses()->first();
        $productWarehouse->update([
            'warehouse_id' => $fromWarehouse->id,
            'quantity' => 10,
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.reports.stock-transfer'), [
            'from_warehouse_id' => $fromWarehouse->id,
            'to_warehouse_id' => $toWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 50, // Requesting more than available
            'reason' => 'Test transfer',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_get_products_returns_available_products()
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        
        // Update existing product warehouse to our test warehouse
        $productWarehouse = $product->productWarehouses()->first();
        $productWarehouse->update([
            'warehouse_id' => $warehouse->id,
            'quantity' => 100,
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.reports.stock-transfer.products', $warehouse->id));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'id' => $product->id,
            'name' => $product->name,
            'quantity' => 100,
        ]);
    }

    public function test_get_products_excludes_zero_quantity_products()
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        
        // Set quantity to zero
        $productWarehouse = $product->productWarehouses()->first();
        $productWarehouse->update([
            'warehouse_id' => $warehouse->id,
            'quantity' => 0,
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.reports.stock-transfer.products', $warehouse->id));

        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    public function test_store_requires_authentication()
    {
        $response = $this->post(route('admin.reports.stock-transfer'), [
            'from_warehouse_id' => 1,
            'to_warehouse_id' => 2,
            'product_id' => 1,
            'quantity' => 50,
        ]);

        $response->assertRedirect();
    }

    public function test_index_requires_permission()
    {
        // Create user without adjust-stock permission (use 'pos' role which doesn't have it)
        $userWithoutPermission = User::factory()->create();
        $userWithoutPermission->assignRole('pos');

        $response = $this->actingAs($userWithoutPermission)->get(route('admin.reports.stock-transfer.page'));

        $response->assertStatus(403);
    }
}
