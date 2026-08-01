<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ProductStockControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected User $user;

    protected Product $product;

    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('superuser');
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::factory()->create(['is_main' => true]);
        $this->product = Product::factory()->create();

        ProductWarehouse::updateOrCreate([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'tenant_id' => app('currentTenant')->id,
        ], ['quantity' => 100]);
    }

    public function test_it_can_get_main_stock()
    {
        $response = $this->get('/admin/product/'.$this->product->id.'/main-stock');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'product_id' => $this->product->id,
        ]);
    }

    public function test_it_can_get_warehouse_stock()
    {
        $response = $this->get('/admin/product/'.$this->product->id.'/warehouse-stock/'.$this->warehouse->id);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
        ]);
    }

    public function test_it_can_check_stock()
    {
        $response = $this->postJson('/admin/product/stock-check', [
            'products' => [
                ['id' => $this->product->id, 'quantity' => 50],
            ],
            'warehouse_id' => $this->warehouse->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'available' => true,
        ]);
    }

    public function test_it_returns_insufficient_stock()
    {
        $response = $this->postJson('/admin/product/stock-check', [
            'products' => [
                ['id' => $this->product->id, 'quantity' => 200],
            ],
            'warehouse_id' => $this->warehouse->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'available' => false,
        ]);
    }

    public function test_it_can_get_bulk_main_stock()
    {
        $response = $this->postJson('/admin/product/bulk-main-stock', [
            'product_ids' => [$this->product->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_it_can_get_all_warehouse_stock()
    {
        $response = $this->get('/admin/product/'.$this->product->id.'/all-warehouse-stock');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'product_id' => $this->product->id,
        ]);
    }

    public function test_it_validates_check_stock_request()
    {
        $response = $this->postJson('/admin/product/stock-check', []);

        $response->assertStatus(422);
    }

    public function test_it_returns_404_for_invalid_product()
    {
        $response = $this->get('/admin/product/99999/main-stock');

        $response->assertStatus(404);
    }

    public function test_it_can_check_stock_without_warehouse()
    {
        $response = $this->postJson('/admin/product/stock-check', [
            'products' => [
                ['id' => $this->product->id, 'quantity' => 10],
            ],
        ]);

        $response->assertStatus(200);
    }
}
