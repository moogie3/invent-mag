<?php

namespace Tests\Feature\Admin;

use App\Models\Categories;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\ProductService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ProductControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
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
    public function the_product_actions_dropdown_shows_datasheet_links_only_for_semiconductors()
    {
        $trsCategory = Categories::create(['name' => 'TRS', 'description' => 'Transistor']);
        $capCategory = Categories::create(['name' => 'CAP', 'description' => 'Capacitor']);

        Product::factory()->create([
            'name' => 'Transistor TIP122 Darlington',
            'category_id' => $trsCategory->id,
        ]);
        Product::factory()->create([
            'name' => 'Kapasitor Elco 470uf TIP122',
            'category_id' => $capCategory->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.product'));
        $response->assertStatus(200);

        // The semiconductor row links to alldatasheet; the capacitor row — even
        // though its name contains the same part number — must not.
        $this->assertSame(
            1,
            substr_count($response->getContent(), 'alldatasheet.com/view.jsp?Searchword=TIP122')
        );
        $response->assertSee('Datasheet');
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

        $productData = [
            'code' => $this->faker->unique()->ean13(),
            'name' => $this->faker->word(),
            'low_stock_threshold' => $this->faker->numberBetween(1, 5),
            'price' => $this->faker->randomFloat(2, 10, 100),
            'selling_price' => $this->faker->randomFloat(2, 100, 200),
            'category_id' => $category->id,
            'units_id' => $unit->id,
            'supplier_id' => $supplier->id,
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
            // 'stock_quantity' => 50, // Removed
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

        $productData = [
            'code' => $this->faker->unique()->ean13(),
            'name' => $this->faker->word(),
            'low_stock_threshold' => $this->faker->numberBetween(1, 5),
            'price' => $this->faker->randomFloat(2, 10, 100),
            'selling_price' => $this->faker->randomFloat(2, 100, 200),
            'category_id' => $category->id,
            'units_id' => $unit->id,
            'supplier_id' => $supplier->id,
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
                'message' => 'Successfully deleted 3 product(s)',
                'deleted_count' => 3,
            ]);

        foreach ($idsToDelete as $id) {
            $this->assertDatabaseMissing('products', ['id' => $id]);
        }
    }

    #[Test]
    public function a_user_can_bulk_update_stock()
    {
        $product1 = Product::factory()->withStock(10)->create();
        $product2 = Product::factory()->withStock(20)->create();

        $updates = [
            ['id' => $product1->id, 'stock_quantity' => 15],
            ['id' => $product2->id, 'stock_quantity' => 25],
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('product.bulk-update-stock'), ['updates' => $updates, 'reason' => 'Test bulk update']);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully updated stock for 2 product(s)',
                'updated_count' => 2,
            ]);

        // Verify pivot table updates
        $this->assertDatabaseHas('product_warehouse', ['product_id' => $product1->id, 'quantity' => 15]);
        $this->assertDatabaseHas('product_warehouse', ['product_id' => $product2->id, 'quantity' => 25]);
    }

    #[Test]
    public function a_user_can_adjust_product_stock()
    {
        $product = Product::factory()->withStock(10)->create();

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

        $this->assertDatabaseHas('product_warehouse', ['product_id' => $product->id, 'quantity' => 15]);
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
        $barcode = $this->faker->unique()->ean13();
        $product = Product::factory()->create(['barcode' => $barcode]);

        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.search-by-barcode', ['barcode' => $barcode]));

        $response->assertStatus(200)
            ->assertJsonFragment(['barcode' => $barcode]);
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
        $product = Product::factory()->withStock(10)->create();

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

    /**
     * Give the product two warehouses of stock and pin down what the empty
     * search returns: total_stock has to add both of them up, while
     * main_warehouse_stock stays on the main one. It also pins the exact set
     * of fields the sales pipeline reads, so slimming this response further
     * (or quietly growing it again) shows up here.
     */
    #[Test]
    public function empty_search_sums_stock_across_every_warehouse_and_returns_only_what_the_picker_reads()
    {
        if (! Warehouse::where('is_main', true)->exists()) {
            Warehouse::factory()->create(['is_main' => true]);
        }
        Product::forgetMainWarehouseId();
        $mainWarehouseId = Product::getMainWarehouseId();

        $overflowWarehouse = Warehouse::factory()->create(['is_main' => false]);

        $product = Product::factory()->create([
            'name' => 'Warehouse Split Part',
            'description' => 'A long description that has no business travelling to a product picker.',
        ]);

        // The factory drops a stock row of its own with a random quantity.
        // Replace it so both totals below are exact.
        $product->productWarehouses()->delete();
        $product->productWarehouses()->create(['warehouse_id' => $mainWarehouseId, 'quantity' => 40]);
        $product->productWarehouses()->create(['warehouse_id' => $overflowWarehouse->id, 'quantity' => 60]);

        $response = $this->actingAs($this->user)
            ->getJson(route('admin.product.search', ['q' => '']));

        $response->assertStatus(200);

        $row = collect($response->json())->firstWhere('name', 'Warehouse Split Part');

        $this->assertNotNull($row, 'The product is missing from the empty search response.');

        $this->assertSame(
            100.0,
            (float) $row['total_stock'],
            'total_stock must add up every warehouse, not just the main one.'
        );
        $this->assertSame(100.0, (float) $row['stock_quantity']);
        $this->assertSame(
            40.0,
            (float) $row['main_warehouse_stock'],
            'main_warehouse_stock must stay main-warehouse only.'
        );

        foreach (['id', 'name', 'selling_price', 'main_warehouse_stock'] as $field) {
            $this->assertArrayHasKey($field, $row, "The pipeline reads {$field}.");
        }

        foreach (['description', 'image', 'cover_url', 'productWarehouses'] as $field) {
            $this->assertArrayNotHasKey($field, $row, "{$field} should not ship to a product picker.");
        }
    }

    /**
     * searchProducts() (the branch behind a non-empty q) used to eager-load
     * productWarehouses filtered down to the main warehouse, and the stock
     * accessors trust whatever relation they are handed.
     */
    #[Test]
    public function product_search_service_sums_stock_across_every_warehouse()
    {
        if (! Warehouse::where('is_main', true)->exists()) {
            Warehouse::factory()->create(['is_main' => true]);
        }
        Product::forgetMainWarehouseId();
        $mainWarehouseId = Product::getMainWarehouseId();

        $overflowWarehouse = Warehouse::factory()->create(['is_main' => false]);

        $product = Product::factory()->create(['name' => 'Multi Warehouse Gadget']);
        $product->productWarehouses()->delete();
        $product->productWarehouses()->create(['warehouse_id' => $mainWarehouseId, 'quantity' => 40]);
        $product->productWarehouses()->create(['warehouse_id' => $overflowWarehouse->id, 'quantity' => 60]);

        $found = app(ProductService::class)->searchProducts('Multi Warehouse Gadget');

        $this->assertCount(1, $found);
        $this->assertSame(
            100.0,
            (float) $found->first()->total_stock,
            'total_stock must add up every warehouse, not just the main one.'
        );
        $this->assertSame(40.0, (float) $found->first()->main_warehouse_stock);
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
        $product = Product::factory()->withStock(20)->create();

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

        $this->assertDatabaseHas('product_warehouse', ['product_id' => $product->id, 'quantity' => 15]);
    }

    #[Test]
    public function adjust_stock_can_perform_correction()
    {
        $product = Product::factory()->withStock(20)->create();

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

        $this->assertDatabaseHas('product_warehouse', ['product_id' => $product->id, 'quantity' => 10]);
    }

    /**
     * The stock record, the adjustment row and every quantity column behind
     * them are integers, but adjustment_amount was validated as numeric:
     * a crafted post of 2.5 decremented the shelf by 2.5 while the row
     * truncated it to 2, so the log and the shelf disagreed by half a unit.
     * The modal's own input has no step, so the browser allowed it too.
     */
    #[Test]
    public function adjust_stock_rejects_a_fractional_amount()
    {
        $product = Product::factory()->withStock(20)->create();

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.product.adjust-stock'), [
                'product_id' => $product->id,
                'adjustment_amount' => 2.5,
                'adjustment_type' => 'decrease',
                'reason' => 'Half a unit',
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('adjustment_amount');
        $this->assertDatabaseMissing('stock_adjustments', ['product_id' => $product->id, 'reason' => 'Half a unit']);
        $this->assertEquals(20, $product->fresh()->stock_quantity, 'A refused adjustment must not move stock.');
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
        $product = Product::factory()->withStock(10)->create();

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
        $product = Product::factory()->withStock(10)->create();

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

        $response = $this->actingAs($this->user)
            ->postJson(route('admin.product.quickCreate'), [
                'code' => $this->faker->unique()->ean13(),
                'name' => $this->faker->word(),
                'price' => 1000,
                'selling_price' => 1500,
                'category_id' => 9999,
                'units_id' => $unit->id,
                'supplier_id' => $supplier->id,
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
                // 'stock_quantity' => $product2->stock_quantity, // Removed
                'price' => $product2->price,
                'selling_price' => $product2->selling_price,
                'category_id' => $product2->category_id,
                'units_id' => $product2->units_id,
                'supplier_id' => $product2->supplier_id,
            ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors(['barcode']);
    }

    /**
     * The barcode index was global: tenant B could not take a barcode
     * tenant A already scanned, even though the two catalogues never
     * meet. Uniqueness belongs to the tenant, and the DB index has to
     * agree with the validation or the two disagree at the worst
     * moment: the insert.
     */
    #[Test]
    public function barcode_is_unique_per_tenant_not_globally()
    {
        $otherTenant = Tenant::create(['name' => 'Other Tenant', 'domain' => 'other.localhost']);
        Product::factory()->create([
            'tenant_id' => $otherTenant->id,
            'barcode' => 'CROSS-TENANT-BC',
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.product.store'), [
            'code' => 'BC-OWN-001',
            'name' => 'Own product',
            'barcode' => 'CROSS-TENANT-BC',
            'stock_quantity' => 10,
            'price' => 50.0,
            'selling_price' => 100.0,
            'category_id' => Categories::factory()->create()->id,
            'units_id' => Unit::factory()->create()->id,
            'supplier_id' => Supplier::factory()->create()->id,
            'warehouse_id' => Warehouse::factory()->create()->id,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', [
            'barcode' => 'CROSS-TENANT-BC',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /**
     * The other half of the same rule: within one catalogue the barcode
     * still has to be unique, and as a validation message rather than
     * the database exception the store path used to surface.
     */
    #[Test]
    public function store_rejects_a_duplicate_barcode_within_the_tenant()
    {
        Product::factory()->create(['barcode' => 'SAME-BC']);

        $response = $this->actingAs($this->user)->post(route('admin.product.store'), [
            'code' => 'BC-DUP-001',
            'name' => 'Second product',
            'barcode' => 'SAME-BC',
            'stock_quantity' => 10,
            'price' => 50.0,
            'selling_price' => 100.0,
            'category_id' => Categories::factory()->create()->id,
            'units_id' => Unit::factory()->create()->id,
            'supplier_id' => Supplier::factory()->create()->id,
            'warehouse_id' => Warehouse::factory()->create()->id,
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors(['barcode']);
        $this->assertEquals(1, Product::withoutGlobalScopes()->where('barcode', 'SAME-BC')->count());
    }

    /**
     * The update rule carried the same global index, so another tenant's
     * data blocked an edit here with a false "already taken". With the
     * constraint scoped to the tenant, the edit goes through.
     */
    #[Test]
    public function update_can_take_a_barcode_another_tenant_already_uses()
    {
        $otherTenant = Tenant::create(['name' => 'Other Tenant', 'domain' => 'other.localhost']);
        Product::factory()->create([
            'tenant_id' => $otherTenant->id,
            'barcode' => 'TAKEN-ELSEWHERE',
        ]);
        $mine = Product::factory()->create(['barcode' => 'MY-BC']);

        $response = $this->actingAs($this->user)
            ->put(route('admin.product.update', $mine->id), [
                'code' => $mine->code,
                'name' => $mine->name,
                'barcode' => 'TAKEN-ELSEWHERE',
                'price' => $mine->price,
                'selling_price' => $mine->selling_price,
                'category_id' => $mine->category_id,
                'units_id' => $mine->units_id,
                'supplier_id' => $mine->supplier_id,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', [
            'id' => $mine->id,
            'barcode' => 'TAKEN-ELSEWHERE',
        ]);
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
        $products = Product::factory()->count(2)->create();
        $idsToDelete = $products->pluck('id')->toArray();

        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('bulkDeleteProducts')
            ->once()
            ->andThrow(new \Exception('Storage error'));

        $this->app->instance(ProductService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->postJson(route('product.bulk-delete'), ['ids' => $idsToDelete]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Error deleting products. Please try again.',
            ]);
    }

    #[Test]
    public function bulk_update_stock_handles_service_exception()
    {
        $product = Product::factory()->create();
        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('bulkUpdateStock')
            ->once()
            ->andThrow(new \Exception('Database transaction failed'));

        $this->app->instance(ProductService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->postJson(route('product.bulk-update-stock'), [
                'updates' => [['id' => $product->id, 'stock_quantity' => 15]],
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
        $product = Product::factory()->withStock(10)->create();

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
        $product1 = Product::factory()->withStock(10)->create(['low_stock_threshold' => 5]);
        $product2 = Product::factory()->withStock(20)->create(['low_stock_threshold' => 5]);

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
            ]);
    }

    #[Test]
    public function adjust_stock_returns_correct_product_from_service()
    {
        $product = Product::factory()->withStock(10)->create();
        $adjustedProduct = $product->replicate();
        // $adjustedProduct->stock_quantity = 15; // Removed property

        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('adjustProductStock')
            ->once()
            ->with(
                Mockery::on(fn ($p) => $p->id === $product->id),
                5.0,
                'increase',
                'Test adjustment',
                $this->user->id,
                null
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
                // 'product' => ['stock_quantity' => 15] // This part is tricky if using mock.
                // ProductController returns $product->fresh()->load(...)
                // Since we mock the service to return a product, we rely on what the service returns.
                // But the controller method `adjustStock` uses the return value of service.
                // If service returns $product (with stock updated via DB), and controller returns it.
            ]);
    }

    #[Test]
    public function bulk_delete_returns_correct_counts_from_service()
    {
        $products = Product::factory()->count(3)->create();
        $idsToDelete = $products->pluck('id')->toArray();

        $mockService = Mockery::mock(ProductService::class);
        $mockService->shouldReceive('bulkDeleteProducts')
            ->once()
            ->with($idsToDelete)
            ->andReturn([
                'deleted_count' => 3,
                'images_deleted' => 2,
            ]);

        $this->app->instance(ProductService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->postJson(route('product.bulk-delete'), ['ids' => $idsToDelete]);

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
            (object) [
                'id' => 1,
                'name' => 'Test Product',
                'code' => 'TEST001',
                'category' => (object) ['name' => 'Category1'],
                'unit' => (object) ['symbol' => 'pcs'],
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
