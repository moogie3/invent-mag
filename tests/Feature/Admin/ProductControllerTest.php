<?php

namespace Tests\Feature;

use App\Models\Categories;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function a_user_can_view_the_product_index_page()
    {
        $this->actingAs($this->user)
            ->get(route('admin.product'))
            ->assertStatus(200)
            ->assertViewIs('admin.product.index');
    }

    #[Test]
    public function a_user_can_view_the_product_create_page()
    {
        $this->actingAs($this->user)
            ->get(route('admin.product.create'))
            ->assertStatus(200)
            ->assertViewIs('admin.product.product-create');
    }

    #[Test]
    public function a_user_can_view_the_product_edit_page()
    {
        $product = Product::factory()->create();
        $this->actingAs($this->user)
            ->get(route('admin.product.edit', $product->id))
            ->assertStatus(200)
            ->assertViewIs('admin.product.product-edit')
            ->assertSee($product->name);
    }

    #[Test]
    public function a_user_can_create_a_product()
    {
        $category = Categories::factory()->create();
        $unit = Unit::factory()->create();
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $productData = [
            'code' => $this->faker->unique()->ean13(),
            'name' => $this->faker->word(),
            'stock_quantity' => $this->faker->numberBetween(10, 100),
            'low_stock_threshold' => $this->faker->numberBetween(1, 5),
            'price' => $this->faker->randomFloat(2, 10, 100),
            'selling_price' => $this->faker->randomFloat(2, 100, 200),
            'category_id' => $category->id,
            'units_id' => $unit->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'description' => $this->faker->sentence(),
            'image' => UploadedFile::fake()->image('product.jpg'),
            'has_expiry' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('admin.product.store'), $productData);

        $response->assertRedirect(route('admin.product'))
            ->assertSessionHas('success', 'Product created');

        $this->assertDatabaseHas('products', [
            'code' => $productData['code'],
            'name' => $productData['name'],
            'category_id' => $productData['category_id'],
        ]);
    }

    #[Test]
    public function a_user_can_update_a_product()
    {
        $product = Product::factory()->create();
        $newCategory = Categories::factory()->create();

        $updatedData = [
            'code' => $product->code,
            'name' => 'Updated Product Name',
            'category_id' => $newCategory->id,
            'units_id' => $product->units_id,
            'supplier_id' => $product->supplier_id,
            'price' => 150.00,
            'selling_price' => 250.00,
            'stock_quantity' => 50,
            'low_stock_threshold' => 5,
            'has_expiry' => false,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('admin.product.update', $product->id), $updatedData);

        $response->assertRedirect(route('admin.product'))
            ->assertSessionHas('success', 'Product updated successfully');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'category_id' => $newCategory->id,
        ]);
    }

    #[Test]
    public function a_user_can_delete_a_product()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('admin.product.destroy', $product->id));

        $response->assertRedirect(route('admin.product'))
            ->assertSessionHas('success', 'Product deleted');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    #[Test]
    public function a_user_can_quick_create_a_product()
    {
        $category = Categories::factory()->create();
        $unit = Unit::factory()->create();
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $productData = [
            'code' => $this->faker->unique()->ean13(),
            'name' => $this->faker->word(),
            'stock_quantity' => $this->faker->numberBetween(10, 100),
            'low_stock_threshold' => $this->faker->numberBetween(1, 5),
            'price' => $this->faker->randomFloat(2, 10, 100),
            'selling_price' => $this->faker->randomFloat(2, 100, 200),
            'category_id' => $category->id,
            'units_id' => $unit->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'description' => $this->faker->sentence(),
            'has_expiry' => false,
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.product.quickCreate'), $productData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product created successfully',
            ]);

        $this->assertDatabaseHas('products', [
            'code' => $productData['code'],
            'name' => $productData['name'],
        ]);
    }

    #[Test]
    public function a_user_can_bulk_delete_products()
    {
        $products = Product::factory()->count(3)->create();
        $idsToDelete = $products->pluck('id')->toArray();

        $response = $this->actingAs($this->user)
            ->postJson(route('product.bulk-delete'), ['ids' => $idsToDelete]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => "Successfully deleted 3 product(s)",
                'deleted_count' => 3,
            ]);

        foreach ($idsToDelete as $id) {
            $this->assertDatabaseMissing('products', ['id' => $id]);
        }
    }

    #[Test]
    public function a_user_can_bulk_update_stock()
    {
        $product1 = Product::factory()->create(['stock_quantity' => 10]);
        $product2 = Product::factory()->create(['stock_quantity' => 20]);

        $updates = [
            ['id' => $product1->id, 'stock_quantity' => 15],
            ['id' => $product2->id, 'stock_quantity' => 25],
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('product.bulk-update-stock'), ['updates' => $updates, 'reason' => 'Test bulk update']);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => "Successfully updated stock for 2 product(s)",
                'updated_count' => 2,
            ]);

        $this->assertDatabaseHas('products', ['id' => $product1->id, 'stock_quantity' => 15]);
        $this->assertDatabaseHas('products', ['id' => $product2->id, 'stock_quantity' => 25]);
    }

    #[Test]
    public function a_user_can_adjust_product_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.product.adjust-stock'), [
                'product_id' => $product->id,
                'adjustment_amount' => 5,
                'adjustment_type' => 'increase',
                'reason' => 'Test adjustment',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Stock adjusted successfully.',
            ]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 15]);
    }

    #[Test]
    public function a_user_can_search_products()
    {
        Product::factory()->create(['name' => 'Laptop Pro']);
        Product::factory()->create(['name' => 'Desktop Mini']);

        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.search', ['q' => 'Laptop']));

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Laptop Pro']);
    }

    #[Test]
    public function a_user_can_search_products_by_barcode()
    {
        $product = Product::factory()->create(['barcode' => '1234567890123']);

        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.search-by-barcode', ['barcode' => '1234567890123']));

        $response->assertStatus(200)
            ->assertJsonFragment(['barcode' => '1234567890123']);
    }

    #[Test]
    public function a_user_can_get_product_metrics()
    {
        Product::factory()->count(5)->create();
        Categories::factory()->count(2)->create();

        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.metrics'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'totalproduct',
                'totalcategory',
                'lowStockCount',
            ]);
    }

    #[Test]
    public function a_user_can_get_expiring_soon_products()
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.expiring-soon'));

        $response->assertStatus(200)
            ->assertJson([]);
    }

    #[Test]
    public function a_user_can_get_adjustment_log_for_a_product()
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $this->actingAs($this->user)
            ->postJson(route('admin.product.adjust-stock'), [
                'product_id' => $product->id,
                'adjustment_amount' => 10,
                'adjustment_type' => 'increase',
                'reason' => 'Test adjustment 1',
            ]);

        $this->actingAs($this->user)
            ->postJson(route('admin.product.adjust-stock'), [
                'product_id' => $product->id,
                'adjustment_amount' => 5,
                'adjustment_type' => 'decrease',
                'reason' => 'Test adjustment 2',
            ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.adjustment-log', $product->id));

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    #[Test]
    public function a_user_can_view_product_details_in_modal()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.modal-view', $product->id));

        $response->assertStatus(200)
            ->assertJson([
                'id' => $product->id,
                'name' => $product->name,
            ]);
    }

    #[Test]
    public function search_returns_all_products_when_query_is_empty()
    {
        Product::query()->delete();
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.search', ['q' => '']));

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    #[Test]
    public function search_returns_empty_array_when_no_products_match()
    {
        Product::factory()->create(['name' => 'Laptop Pro']);

        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.search', ['q' => 'NonExistentProduct']));

        $response->assertStatus(200)
            ->assertJsonCount(0);
    }

    #[Test]
    public function search_by_barcode_returns_404_when_product_not_found()
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.search-by-barcode', ['barcode' => 'nonexistent']));

        $response->assertStatus(404)
            ->assertJson(['message' => 'Product not found']);
    }

    #[Test]
    public function modal_view_returns_404_for_non_existent_product()
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.modal-view', 99999));

        $response->assertStatus(404)
            ->assertJsonStructure(['error', 'message']);
    }

    #[Test]
    public function adjust_stock_can_decrease_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 20]);

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.product.adjust-stock'), [
                'product_id' => $product->id,
                'adjustment_amount' => 5,
                'adjustment_type' => 'decrease',
                'reason' => 'Test decrease',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Stock adjusted successfully.',
            ]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 15]);
    }

    #[Test]
    public function adjust_stock_can_perform_correction()
    {
        $product = Product::factory()->create(['stock_quantity' => 20]);

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.product.adjust-stock'), [
                'product_id' => $product->id,
                'adjustment_amount' => 10,
                'adjustment_type' => 'correction',
                'reason' => 'Stock count correction',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Stock adjusted successfully.',
            ]);
    }

    #[Test]
    public function adjust_stock_returns_error_for_invalid_product()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('admin.product.adjust-stock'), [
                'product_id' => 99999,
                'adjustment_amount' => 5,
                'adjustment_type' => 'increase',
                'reason' => 'Test',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    #[Test]
    public function adjust_stock_validates_adjustment_type()
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.product.adjust-stock'), [
                'product_id' => $product->id,
                'adjustment_amount' => 5,
                'adjustment_type' => 'invalid_type',
                'reason' => 'Test',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['adjustment_type']);
    }

    #[Test]
    public function bulk_delete_validates_required_ids()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('product.bulk-delete'), ['ids' => []]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);
    }

    #[Test]
    public function bulk_delete_validates_product_exists()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('product.bulk-delete'), ['ids' => [99999]]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids.0']);
    }

    #[Test]
    public function bulk_update_stock_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('product.bulk-update-stock'), ['updates' => []]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['updates']);
    }

    #[Test]
    public function bulk_update_stock_validates_stock_quantity_is_non_negative()
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $response = $this->actingAs($this->user)
            ->postJson(route('product.bulk-update-stock'), [
                'updates' => [
                    ['id' => $product->id, 'stock_quantity' => -5],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['updates.0.stock_quantity']);
    }

    #[Test]
    public function quick_create_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('admin.product.quickCreate'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'code',
                'name',
                'stock_quantity',
                'price',
                'selling_price',
                'category_id',
                'units_id',
                'supplier_id',
            ]);
    }

    #[Test]
    public function quick_create_validates_category_exists()
    {
        $unit = Unit::factory()->create();
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.product.quickCreate'), [
                'code' => $this->faker->unique()->ean13(),
                'name' => $this->faker->word(),
                'stock_quantity' => 10,
                'price' => 50.00,
                'selling_price' => 100.00,
                'category_id' => 99999,
                'units_id' => $unit->id,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    #[Test]
    public function store_validates_image_type()
    {
        $category = Categories::factory()->create();
        $unit = Unit::factory()->create();
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('admin.product.store'), [
                'code' => $this->faker->unique()->ean13(),
                'name' => $this->faker->word(),
                'stock_quantity' => 10,
                'low_stock_threshold' => 2,
                'price' => 50.00,
                'selling_price' => 100.00,
                'category_id' => $category->id,
                'units_id' => $unit->id,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'image' => UploadedFile::fake()->create('document.pdf', 100),
            ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors(['image']);
    }

    #[Test]
    public function update_validates_unique_barcode()
    {
        $product1 = Product::factory()->create(['barcode' => 'BARCODE123']);
        $product2 = Product::factory()->create(['barcode' => 'BARCODE456']);

        $response = $this->actingAs($this->user)
            ->put(route('admin.product.update', $product2->id), [
                'code' => $product2->code,
                'name' => $product2->name,
                'barcode' => 'BARCODE123',
                'stock_quantity' => $product2->stock_quantity,
                'price' => $product2->price,
                'selling_price' => $product2->selling_price,
                'category_id' => $product2->category_id,
                'units_id' => $product2->units_id,
                'supplier_id' => $product2->supplier_id,
            ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors(['barcode']);
    }

    #[Test]
    public function adjustment_log_returns_empty_array_for_product_with_no_adjustments()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.adjustment-log', $product->id));

        $response->assertStatus(200)
            ->assertJsonCount(0);
    }

    #[Test]
    public function get_product_metrics_returns_correct_structure()
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.metrics'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'totalproduct',
                'totalcategory',
                'lowStockCount',
            ]);
    }

    #[Test]
    public function store_handles_service_exception_gracefully()
    {
        $category = Categories::factory()->create();
        $unit = Unit::factory()->create();
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('createProduct')
            ->once()
            ->andThrow(new \Exception('Database connection error'));

        $this->app->instance(ProductService::class, $mockService);

        $productData = [
            'code' => $this->faker->unique()->ean13(),
            'name' => $this->faker->word(),
            'stock_quantity' => 10,
            'low_stock_threshold' => 2,
            'price' => 50.00,
            'selling_price' => 100.00,
            'category_id' => $category->id,
            'units_id' => $unit->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('admin.product.store'), $productData);

        $response->assertRedirect()
            ->assertSessionHas('error', 'Error creating product. Please try again.');
    }

    #[Test]
    public function update_handles_service_exception_gracefully()
    {
        $product = Product::factory()->create();

        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('updateProduct')
            ->once()
            ->andThrow(new \Exception('Failed to update product'));

        $this->app->instance(ProductService::class, $mockService);

        $updatedData = [
            'code' => $product->code,
            'name' => 'Updated Name',
            'stock_quantity' => 50,
            'price' => 150.00,
            'selling_price' => 250.00,
            'category_id' => $product->category_id,
            'units_id' => $product->units_id,
            'supplier_id' => $product->supplier_id,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('admin.product.update', $product->id), $updatedData);

        $response->assertRedirect()
            ->assertSessionHas('error', 'Error updating product. Please try again.');
    }

    #[Test]
    public function delete_handles_service_exception_gracefully()
    {
        $product = Product::factory()->create();

        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('deleteProduct')
            ->once()
            ->andThrow(new \Exception('Cannot delete product with existing orders'));

        $this->app->instance(ProductService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->delete(route('admin.product.destroy', $product->id));

        $response->assertRedirect()
            ->assertSessionHas('error', 'Error deleting product. Please try again.');
    }

    #[Test]
    public function bulk_delete_handles_service_exception()
    {
        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('bulkDeleteProducts')
            ->once()
            ->andThrow(new \Exception('Storage error'));

        $this->app->instance(ProductService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->postJson(route('product.bulk-delete'), ['ids' => [1, 2]]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Error deleting products. Please try again.',
            ]);
    }

    #[Test]
    public function bulk_update_stock_handles_service_exception()
    {
        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('bulkUpdateStock')
            ->once()
            ->andThrow(new \Exception('Database transaction failed'));

        $this->app->instance(ProductService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->postJson(route('product.bulk-update-stock'), [
                'updates' => [['id' => 1, 'stock_quantity' => 15]],
                'reason' => 'Test',
            ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Error updating stock quantities. Please try again.',
            ]);
    }

    #[Test]
    public function adjust_stock_handles_service_exception()
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('adjustProductStock')
            ->once()
            ->andThrow(new \Exception('Stock adjustment failed'));

        $this->app->instance(ProductService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.product.adjust-stock'), [
                'product_id' => $product->id,
                'adjustment_amount' => 5,
                'adjustment_type' => 'increase',
                'reason' => 'Test',
            ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Error adjusting stock: Stock adjustment failed',
            ]);
    }

    #[Test]
    public function search_by_barcode_returns_null_when_service_returns_null()
    {
        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('searchByBarcode')
            ->once()
            ->with('INVALID_BARCODE')
            ->andReturn(null);

        $this->app->instance(ProductService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.search-by-barcode', ['barcode' => 'INVALID_BARCODE']));

        $response->assertStatus(404)
            ->assertJson(['message' => 'Product not found']);
    }

    #[Test]
    public function bulk_update_returns_correct_response_structure_from_service()
    {
        $product1 = Product::factory()->create(['stock_quantity' => 10, 'low_stock_threshold' => 5]);
        $product2 = Product::factory()->create(['stock_quantity' => 20, 'low_stock_threshold' => 5]);

        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('bulkUpdateStock')
            ->once()
            ->andReturn([
                'updated_count' => 2,
                'changes' => [
                    [
                        'product_id' => $product1->id,
                        'product_code' => $product1->code,
                        'original_stock' => 10,
                        'new_stock_quantity' => 15,
                        'change' => 5,
                        'low_stock_threshold' => 5,
                    ],
                    [
                        'product_id' => $product2->id,
                        'product_code' => $product2->code,
                        'original_stock' => 20,
                        'new_stock_quantity' => 25,
                        'change' => 5,
                        'low_stock_threshold' => 5,
                    ],
                ],
            ]);

        $this->app->instance(ProductService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->postJson(route('product.bulk-update-stock'), [
                'updates' => [
                    ['id' => $product1->id, 'stock_quantity' => 15],
                    ['id' => $product2->id, 'stock_quantity' => 25],
                ],
                'reason' => 'Test',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'updated_count' => 2,
            ])
            ->assertJsonStructure([
                'changes' => [
                    '*' => [
                        'product_id',
                        'product_code',
                        'original_stock',
                        'new_stock_quantity',
                        'change',
                        'badge_class',
                        'badge_text',
                    ],
                ],
            ]);
    }

    #[Test]
    public function adjust_stock_returns_correct_product_from_service()
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);
        $adjustedProduct = $product->replicate();
        $adjustedProduct->stock_quantity = 15;

        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('adjustProductStock')
            ->once()
            ->with(
                Mockery::on(fn($p) => $p->id === $product->id),
                5.0,
                'increase',
                'Test adjustment',
                $this->user->id
            )
            ->andReturn($adjustedProduct);

        $this->app->instance(ProductService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.product.adjust-stock'), [
                'product_id' => $product->id,
                'adjustment_amount' => 5,
                'adjustment_type' => 'increase',
                'reason' => 'Test adjustment',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Stock adjusted successfully.',
                'product' => [
                    'stock_quantity' => 15,
                ],
            ]);
    }

    #[Test]
    public function bulk_delete_returns_correct_counts_from_service()
    {
        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('bulkDeleteProducts')
            ->once()
            ->with([1, 2, 3])
            ->andReturn([
                'deleted_count' => 3,
                'images_deleted' => 2,
            ]);

        $this->app->instance(ProductService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->postJson(route('product.bulk-delete'), ['ids' => [1, 2, 3]]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully deleted 3 product(s)',
                'deleted_count' => 3,
                'images_deleted' => 2,
            ]);
    }

    #[Test]
    public function search_returns_correctly_formatted_products_from_service()
    {
        $mockProducts = collect([
            (object)[
                'id' => 1,
                'name' => 'Test Product',
                'code' => 'TEST001',
                'category' => (object)['name' => 'Category1'],
                'unit' => (object)['symbol' => 'pcs'],
            ],
        ]);

        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('searchProducts')
            ->once()
            ->with('Test')
            ->andReturn($mockProducts);

        $this->app->instance(ProductService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.search', ['q' => 'Test']));

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'name' => 'Test Product',
                'code' => 'TEST001',
            ]);
    }

    #[Test]
    public function quick_create_handles_service_exception()
    {
        $category = Categories::factory()->create();
        $unit = Unit::factory()->create();
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('quickCreateProduct')
            ->once()
            ->andThrow(new \Exception('Duplicate product code'));

        $this->app->instance(ProductService::class, $mockService);

        $productData = [
            'code' => $this->faker->unique()->ean13(),
            'name' => $this->faker->word(),
            'stock_quantity' => 10,
            'price' => 50.00,
            'selling_price' => 100.00,
            'category_id' => $category->id,
            'units_id' => $unit->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.product.quickCreate'), $productData);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Error creating product. Please try again.',
            ]);
    }

    #[Test]
    public function index_handles_service_exception_in_get_paginated_products()
    {
        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('getPaginatedProducts')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $mockService->shouldReceive('getProductFormData')->never();

        $this->app->instance(ProductService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->get(route('admin.product'));

        $response->assertRedirect()
            ->assertSessionHas('error', 'Error loading products. Please try again.');
    }

    #[Test]
    public function create_handles_service_exception_in_get_form_data()
    {
        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('getProductFormData')
            ->once()
            ->andThrow(new \Exception('Failed to load form data'));

        $this->app->instance(ProductService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->get(route('admin.product.create'));

        $response->assertRedirect()
            ->assertSessionHas('error', 'Error loading form. Please try again.');
    }

    #[Test]
    public function search_handles_service_exception()
    {
        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('searchProducts')
            ->once()
            ->andThrow(new \Exception('Search index unavailable'));

        $this->app->instance(ProductService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.search', ['q' => 'test']));

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Error searching products.',
            ]);
    }

    #[Test]
    public function search_by_barcode_handles_service_exception()
    {
        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('searchByBarcode')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->app->instance(ProductService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.search-by-barcode', ['barcode' => '123456']));

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Error searching by barcode.',
            ]);
    }

    #[Test]
    public function get_expiring_soon_handles_service_exception()
    {
        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('getExpiringSoonPOItems')
            ->once()
            ->andThrow(new \Exception('Failed to fetch expiring items'));

        $this->app->instance(ProductService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.expiring-soon'));

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Error fetching expiring products.',
            ]);
    }

    #[Test]
    public function search_returns_empty_array_when_service_returns_empty()
    {
        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('searchProducts')
            ->once()
            ->with('NonExistent')
            ->andReturn(collect([]));

        $this->app->instance(ProductService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.search', ['q' => 'NonExistent']));

        $response->assertStatus(200)
            ->assertJsonCount(0);
    }
}
