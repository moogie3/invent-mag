<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Product;
use App\Models\Categories;
use App\Models\Unit;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\StockAdjustment;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Mockery;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.defaults.guard' => 'web']);

        $this->seed(CurrencySeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('superuser');

        $this->actingAs($this->user);

        // Create a main warehouse to avoid errors in views
        Warehouse::factory()->create(['is_main' => true]);
    }

    /** @test */
    public function it_can_display_the_product_index_page()
    {
        Categories::factory()->count(3)->create();
        Unit::factory()->count(3)->create();
        Supplier::factory()->count(3)->create();

        $response = $this->withViewErrors([])->get(route('admin.product'));

        if ($response->status() !== 200) {
            $response->dump();
        }

        $response->assertStatus(200);
        $response->assertViewIs('admin.product.index');
    }

    /** @test */
    public function it_can_display_the_product_create_page()
    {
        Categories::factory()->count(3)->create();
        Unit::factory()->count(3)->create();
        Supplier::factory()->count(3)->create();

        $response = $this->withViewErrors([])->get(route('admin.product.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.product.product-create');
    }

    /** @test */
    public function it_can_store_a_new_product()
    {
        Storage::fake('public');

        $category = Categories::factory()->create();
        $unit = Unit::factory()->create();
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $productData = [
            'code' => 'P001',
            'name' => 'Test Product',
            'stock_quantity' => 100,
            'low_stock_threshold' => 10,
            'price' => 10000,
            'selling_price' => 15000,
            'category_id' => $category->id,
            'units_id' => $unit->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'description' => 'This is a test product.',
            'image' => UploadedFile::fake()->image('product.jpg'),
            'has_expiry' => false,
        ];

        $response = $this->post(route('admin.product.store'), $productData);

        $response->assertRedirect(route('admin.product'));
        $response->assertSessionHas('success', 'Product created');

        $this->assertDatabaseHas('products', [
            'code' => 'P001',
            'name' => 'Test Product',
        ]);

        $product = Product::where('code', 'P001')->first();
        $this->assertNotNull($product->getRawOriginal('image'));
    }

    /** @test */
    public function it_can_display_the_product_edit_page()
    {
        $product = Product::factory()->create();
        Categories::factory()->count(3)->create();
        Unit::factory()->count(3)->create();
        Supplier::factory()->count(3)->create();

        $response = $this->withViewErrors([])->get(route('admin.product.edit', $product->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.product.product-edit');
        $response->assertViewHas('products', $product);
    }

    /** @test */
    public function it_can_update_a_product()
    {
        $product = Product::factory()->create();

        $category = Categories::factory()->create();
        $unit = Unit::factory()->create();
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $updateData = [
            'name' => 'Updated Product Name',
            'code' => $product->code,
            'stock_quantity' => 50,
            'price' => 12000,
            'selling_price' => 18000,
            'category_id' => $category->id,
            'units_id' => $unit->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
        ];

        $response = $this->put(route('admin.product.update', $product->id), $updateData);

        $response->assertRedirect(route('admin.product'));
        $response->assertSessionHas('success', 'Product updated successfully');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'stock_quantity' => 50,
        ]);
    }

    /** @test */
    public function it_can_delete_a_product()
    {
        $product = Product::factory()->create();

        $response = $this->delete(route('admin.product.destroy', $product->id));

        $response->assertRedirect(route('admin.product'));
        $response->assertSessionHas('success', 'Product deleted');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /** @test */
    public function it_can_bulk_delete_products()
    {
        Storage::fake('public');

        $products = Product::factory()->count(3)->create();
        $productWithImage = Product::factory()->create([
            'image' => UploadedFile::fake()->image('product_image.jpg')->store('public/image'),
        ]);

        $idsToDelete = $products->pluck('id')->toArray();
        $idsToDelete[] = $productWithImage->id;

        $response = $this->postJson(route('product.bulk-delete'), ['ids' => $idsToDelete]);

        $response->assertOk()
                 ->assertJson([
                     'success' => true,
                     'message' => "Successfully deleted " . count($idsToDelete) . " product(s)",
                 ]);

        foreach ($idsToDelete as $id) {
            $this->assertDatabaseMissing('products', ['id' => $id]);
        }

        Storage::disk('public')->assertMissing(basename($productWithImage->getRawOriginal('image')));
    }

    /** @test */
    public function it_can_bulk_update_product_stock()
    {
        $products = Product::factory()->count(3)->create(['stock_quantity' => 100]);
        $product1 = $products[0];
        $product2 = $products[1];
        $product3 = $products[2];

        $updates = [
            ['id' => $product1->id, 'stock_quantity' => 110], // Increase
            ['id' => $product2->id, 'stock_quantity' => 90],  // Decrease
            ['id' => $product3->id, 'stock_quantity' => 100], // No change
        ];

        $reason = 'Bulk update for testing';

        $response = $this->postJson(route('product.bulk-update-stock'), [
            'updates' => $updates,
            'reason' => $reason,
        ]);

        $response->assertOk()
                 ->assertJson([
                     'success' => true,
                     'message' => "Successfully updated stock for 2 product(s)", // Only 2 products changed stock
                     'updated_count' => 2,
                 ]);

        $this->assertDatabaseHas('products', [
            'id' => $product1->id,
            'stock_quantity' => 110,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product2->id,
            'stock_quantity' => 90,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product3->id,
            'stock_quantity' => 100,
        ]);

        // Assert StockAdjustments were created
        $this->assertDatabaseHas('stock_adjustments', [
            'product_id' => $product1->id,
            'adjustment_type' => 'increase',
            'quantity_before' => 100,
            'quantity_after' => 110,
            'adjustment_amount' => 10,
            'reason' => $reason,
            'adjusted_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('stock_adjustments', [
            'product_id' => $product2->id,
            'adjustment_type' => 'decrease',
            'quantity_before' => 100,
            'quantity_after' => 90,
            'adjustment_amount' => 10,
            'reason' => $reason,
            'adjusted_by' => $this->user->id,
        ]);

        // No adjustment should be recorded for product3 as stock didn't change
        $this->assertDatabaseMissing('stock_adjustments', [
            'product_id' => $product3->id,
            'reason' => $reason,
        ]);
    }

    /** @test */
    public function it_can_get_product_data_for_modal_view()
    {
        $product = Product::factory()->create();

        $response = $this->getJson(route('admin.product.modal-view', $product->id));

        $response->assertOk();
        $response->assertJson([
            'id' => $product->id,
            'name' => $product->name,
            'formatted_selling_price' => \App\Helpers\CurrencyHelper::formatWithPosition($product->selling_price)
        ]);
    }

    /** @test */
    public function modal_view_returns_404_for_non_existent_product()
    {
        $nonExistentId = 9999;
        $response = $this->getJson(route('admin.product.modal-view', $nonExistentId));

        $response->assertNotFound();
        $response->assertJson(['error' => 'Product not found']);
    }

    /** @test */
    public function it_can_quick_create_a_product()
    {
        $category = Categories::factory()->create();
        $unit = Unit::factory()->create();
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create(); // Create a warehouse

        $productData = [
            'code' => 'QC001',
            'name' => 'Quick Create Product',
            'stock_quantity' => 50,
            'price' => 5000,
            'selling_price' => 7500,
            'category_id' => $category->id,
            'units_id' => $unit->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'description' => 'Quick create product description', // Add description
        ];

        $response = $this->postJson(route('admin.product.quickCreate'), $productData);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Product created successfully',
        ]);
        $response->assertJsonStructure(['product' => ['id', 'name']]);

        $this->assertDatabaseHas('products', [
            'code' => 'QC001',
            'name' => 'Quick Create Product',
        ]);
    }

    /** @test */
    public function quick_create_returns_validation_errors_for_invalid_data()
    {
        $invalidData = [
            'code' => '', // required
            'name' => '', // required
            'stock_quantity' => 'not-a-number', // integer
            'price' => 'not-a-number', // numeric
            'selling_price' => 'not-a-number', // numeric
            'category_id' => 999, // not exists
            'units_id' => 999, // not exists
            'supplier_id' => 999, // not exists
        ];

        $response = $this->postJson(route('admin.product.quickCreate'), $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code', 'name', 'stock_quantity', 'price', 'selling_price', 'category_id', 'units_id', 'supplier_id']);
    }

    /** @test */
    public function it_can_adjust_stock_for_a_product()
    {
        $product = Product::factory()->create(['stock_quantity' => 100]);
        $reason = 'Found extra items during stock take';

        $adjustmentData = [
            'product_id' => $product->id,
            'adjustment_amount' => 10,
            'adjustment_type' => 'increase',
            'reason' => $reason,
        ];

        $response = $this->postJson(route('admin.product.adjust-stock'), $adjustmentData);

        $response->assertOk();
        $response->assertJson(['success' => true, 'message' => 'Stock adjusted successfully.']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 110,
        ]);

        $this->assertDatabaseHas('stock_adjustments', [
            'product_id' => $product->id,
            'adjustment_type' => 'increase',
            'adjustment_amount' => 10,
            'reason' => $reason,
            'adjusted_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_decrease_stock_for_a_product()
    {
        $product = Product::factory()->create(['stock_quantity' => 100]);
        $reason = 'Damaged items removed from stock';

        $adjustmentData = [
            'product_id' => $product->id,
            'adjustment_amount' => 20,
            'adjustment_type' => 'decrease',
            'reason' => $reason,
        ];

        $response = $this->postJson(route('admin.product.adjust-stock'), $adjustmentData);

        $response->assertOk();
        $response->assertJson(['success' => true, 'message' => 'Stock adjusted successfully.']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 80,
        ]);

        $this->assertDatabaseHas('stock_adjustments', [
            'product_id' => $product->id,
            'adjustment_type' => 'decrease',
            'adjustment_amount' => 20,
            'reason' => $reason,
            'adjusted_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function adjust_stock_handles_service_exception()
    {
        $product = Product::factory()->create(['stock_quantity' => 100]);
        $productServiceMock = Mockery::mock(\App\Services\ProductService::class);
        $this->app->instance(\App\Services\ProductService::class, $productServiceMock);

        $errorMessage = 'Service failed to adjust stock.';
        $productServiceMock->shouldReceive('adjustProductStock')
                           ->andThrow(new \Exception($errorMessage));

        $adjustmentData = [
            'product_id' => $product->id,
            'adjustment_amount' => 5,
            'adjustment_type' => 'increase',
            'reason' => 'Test exception',
        ];

        $response = $this->postJson(route('admin.product.adjust-stock'), $adjustmentData);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Error adjusting stock: ' . $errorMessage,
        ]);
    }

    /** @test */
    public function adjust_stock_returns_validation_errors_for_invalid_data()
    {
        $product = Product::factory()->create();

        $invalidData = [
            'product_id' => 9999, // Non-existent product
            'adjustment_amount' => 0, // Less than 1
            'adjustment_type' => 'invalid_type', // Not in: increase, decrease, correction
            'reason' => str_repeat('a', 501), // Too long
        ];

        $response = $this->postJson(route('admin.product.adjust-stock'), $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['product_id', 'adjustment_amount', 'adjustment_type', 'reason']);
    }
}
