<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Product;
use App\Models\Categories;
use App\Models\Unit;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
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
}
