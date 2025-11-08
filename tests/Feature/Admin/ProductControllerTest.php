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
}
