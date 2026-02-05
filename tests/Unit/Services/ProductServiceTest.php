<?php

namespace Tests\Unit\Services;

use App\Models\Categories;
use App\Models\POItem;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\ProductWarehouse;
use App\Services\ProductService;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;

class ProductServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->productService = new ProductService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(ProductService::class, $this->productService);
    }

    #[Test]
    public function it_can_get_paginated_products()
    {
        Product::factory()->count(10)->create();
        $result = $this->productService->getPaginatedProducts(5);
        $this->assertCount(5, $result);
        $this->assertEquals(10, $result->total());
    }

    #[Test]
    public function it_can_get_product_form_data()
    {
        $categories = Categories::factory()->count(2)->create();
        $units = Unit::factory()->count(2)->create();
        $suppliers = Supplier::factory()->count(2)->create();
        $warehouses = Warehouse::factory()->count(2)->create();
        $mainWarehouse = Warehouse::factory()->create(['is_main' => true]);
        
        $product = Product::factory()->create([
            'low_stock_threshold' => 10,
            'category_id' => $categories->first()->id,
            'units_id' => $units->first()->id,
            'supplier_id' => $suppliers->first()->id,
        ]);
        
        // Clear factory-generated stock to ensure we control the total stock
        $product->productWarehouses()->delete();

        // Set low stock
        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $mainWarehouse->id,
            'quantity' => 5,
            'tenant_id' => $product->tenant_id
        ]);

        $data = $this->productService->getProductFormData();

        $this->assertCount(2, $data['categories']);
        $this->assertCount(2, $data['units']);
        $this->assertCount(2, $data['suppliers']);
        // 2 + 1 main = 3
        $this->assertGreaterThanOrEqual(3, $data['warehouses']->count());
        $this->assertNotNull($data['mainWarehouse']);
        // Check if low stock product is found
        $this->assertGreaterThanOrEqual(1, $data['lowStockProducts']->count());
    }

    #[Test]
    public function it_can_create_a_product_without_image()
    {
        $category = Categories::factory()->create();
        $unit = Unit::factory()->create();
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $data = [
            'name' => 'Test Product',
            'code' => 'TP-01',
            'description' => 'Test description',
            'category_id' => $category->id,
            'units_id' => $unit->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id, // Initial stock location
            'price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 10, // Should be moved to pivot
            'has_expiry' => 'on',
        ];

        $product = $this->productService->createProduct($data);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertDatabaseHas('products', ['name' => 'Test Product', 'code' => 'TP-01']);
        $this->assertTrue($product->has_expiry);
        
        // Verify stock in pivot table
        $this->assertDatabaseHas('product_warehouse', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 0 // createProduct initializes at 0
        ]);
    }

    #[Test]
    public function it_can_create_a_product_with_image()
    {
        Storage::fake('public');
        $category = Categories::factory()->create();
        $unit = Unit::factory()->create();
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $data = [
            'name' => 'Test Product with Image',
            'code' => 'TP-02',
            'description' => 'Test description',
            'category_id' => $category->id,
            'units_id' => $unit->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 10,
            'has_expiry' => null,
            'image' => UploadedFile::fake()->image('product.jpg'),
        ];

        $product = $this->productService->createProduct($data);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertDatabaseHas('products', ['name' => 'Test Product with Image']);
        $this->assertNotNull($product->image);
        Storage::disk('public')->assertExists('image/' . $product->getRawOriginal('image'));
        $this->assertFalse($product->has_expiry);
        
        $this->assertDatabaseHas('product_warehouse', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 0
        ]);
    }

    #[Test]
    public function it_can_quick_create_a_product()
    {
        $category = Categories::factory()->create();
        $unit = Unit::factory()->create();
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create(['is_main' => true]); // Ensure main warehouse

        $data = [
            'name' => 'Quick Product',
            'code' => 'QP-01',
            'description' => 'Test description',
            'category_id' => $category->id,
            'units_id' => $unit->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'price' => 50,
            'selling_price' => 75,
            'stock_quantity' => 5,
            'has_expiry' => 'on',
        ];

        $product = $this->productService->quickCreateProduct($data);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertDatabaseHas('products', ['name' => 'Quick Product']);
        $this->assertEquals($unit->symbol, $product->unit_symbol);
        $this->assertEquals($warehouse->name, $product->warehouse_name);
        $this->assertNotNull($product->image_url);
    }

    #[Test]
    public function it_can_update_a_product()
    {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // Note: Update no longer handles warehouse_id
        $data = [
            'name' => 'Updated Product Name',
            // 'warehouse_id' => $warehouse->id, // Removed from logic
            'has_expiry' => 'on',
        ];

        $updatedProduct = $this->productService->updateProduct($product, $data);

        $this->assertEquals('Updated Product Name', $updatedProduct->name);
        // $this->assertEquals($warehouse->id, $updatedProduct->warehouse_id); // Removed check
        $this->assertTrue($updatedProduct->has_expiry);
    }

    #[Test]
    public function it_can_delete_a_product()
    {
        Storage::fake('public');
        $product = Product::factory()->create(['image' => 'test.jpg']);
        Storage::disk('public')->put('image/test.jpg', 'content');

        $this->productService->deleteProduct($product);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        Storage::disk('public')->assertMissing('image/test.jpg');
    }

    #[Test]
    public function it_can_bulk_delete_products()
    {
        Storage::fake('public');
        $products = Product::factory()->count(3)->create()->each(function ($product) {
            $imageName = 'test_' . $product->id . '.jpg';
            $product->image = $imageName;
            $product->save();
            Storage::disk('public')->put('image/'.$imageName, 'content');
        });

        $result = $this->productService->bulkDeleteProducts($products->pluck('id')->toArray());

        $this->assertEquals(3, $result['deleted_count']);
        $this->assertEquals(3, $result['images_deleted']);
        foreach ($products as $product) {
            $this->assertDatabaseMissing('products', ['id' => $product->id]);
            Storage::disk('public')->assertMissing('image/'.$product->getRawOriginal('image'));
        }
    }

    #[Test]
    public function it_can_bulk_update_stock()
    {
        $user = User::factory()->create();
        // Setup main warehouse
        $warehouse = Warehouse::factory()->create(['is_main' => true]);
        
        $products = Product::factory()->count(2)->create();
        // Initialize stock for main warehouse manually as factory might pick random
        foreach($products as $p) {
            ProductWarehouse::updateOrCreate(
                ['product_id' => $p->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $p->tenant_id],
                ['quantity' => 10]
            );
        }

        $updates = [
            ['id' => $products[0]->id, 'stock_quantity' => 15],
            ['id' => $products[1]->id, 'stock_quantity' => 5],
        ];

        $result = $this->productService->bulkUpdateStock($updates, 'Test Reason', $user->id);

        $this->assertEquals(2, $result['updated_count']);
        
        // Check pivot table
        $this->assertDatabaseHas('product_warehouse', ['product_id' => $products[0]->id, 'warehouse_id' => $warehouse->id, 'quantity' => 15]);
        $this->assertDatabaseHas('product_warehouse', ['product_id' => $products[1]->id, 'warehouse_id' => $warehouse->id, 'quantity' => 5]);
        
        $this->assertDatabaseHas('stock_adjustments', ['product_id' => $products[0]->id, 'reason' => 'Test Reason', 'warehouse_id' => $warehouse->id]);
        $this->assertDatabaseHas('stock_adjustments', ['product_id' => $products[1]->id, 'reason' => 'Test Reason', 'warehouse_id' => $warehouse->id]);
    }

    #[Test]
    public function it_can_adjust_product_stock()
    {
        $user = User::factory()->create();
        $warehouse = Warehouse::factory()->create(['is_main' => true]);
        $product = Product::factory()->create();
        
        // Ensure pivot exists with known quantity
        \App\Models\ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 10]
        );

        // Test increase
        $this->productService->adjustProductStock($product, 5, 'increase', 'Test Increase', $user->id, $warehouse->id);
        
        $this->assertDatabaseHas('product_warehouse', ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 15]);
        $this->assertDatabaseHas('stock_adjustments', ['product_id' => $product->id, 'adjustment_type' => 'increase', 'warehouse_id' => $warehouse->id]);

        // Test decrease
        $this->productService->adjustProductStock($product, 3, 'decrease', 'Test Decrease', $user->id, $warehouse->id);
        $this->assertDatabaseHas('product_warehouse', ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 12]);
        $this->assertDatabaseHas('stock_adjustments', ['product_id' => $product->id, 'adjustment_type' => 'decrease']);

        // Test correction
        $this->productService->adjustProductStock($product, 20, 'correction', 'Test Correction', $user->id, $warehouse->id);
        $this->assertDatabaseHas('product_warehouse', ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 20]);
        $this->assertDatabaseHas('stock_adjustments', ['product_id' => $product->id, 'adjustment_type' => 'correction']);
    }

    #[Test]
    public function it_can_search_products()
    {
        // Be specific with related model names to avoid accidental matches
        $supplier1 = Supplier::factory()->create(['name' => 'Supplier A']);
        $category1 = Categories::factory()->create(['name' => 'Category X']);
        $category2 = Categories::factory()->create(['name' => 'Electronics']);

        Product::factory()->create([
            'name' => 'Laptop',
            'code' => 'LP-01',
            'supplier_id' => $supplier1->id,
            'category_id' => $category1->id,
        ]);
        Product::factory()->create([
            'name' => 'Mouse',
            'barcode' => '123456789',
            'supplier_id' => $supplier1->id,
            'category_id' => $category1->id,
        ]);
        Product::factory()->create([
            'name' => 'Keyboard',
            'supplier_id' => $supplier1->id,
            'category_id' => $category2->id,
        ]);

        // Search by product name
        $results = $this->productService->searchProducts('Lap');
        $this->assertCount(1, $results);
        $this->assertEquals('Laptop', $results->first()->name);

        // Search by barcode
        $results = $this->productService->searchProducts('12345');
        $this->assertCount(1, $results);
        $this->assertEquals('Mouse', $results->first()->name);

        // Search by category name
        $results = $this->productService->searchProducts('Elec');
        $this->assertCount(1, $results);
        $this->assertEquals('Keyboard', $results->first()->name);
    }

    #[Test]
    public function it_can_search_by_barcode()
    {
        $product = Product::factory()->create(['barcode' => '987654321']);
        $result = $this->productService->searchByBarcode('987654321');
        $this->assertEquals($product->id, $result->id);
    }

    #[Test]
    public function it_can_get_expiring_soon_po_items()
    {
        $product = Product::factory()->create();
        POItem::factory()->create(['product_id' => $product->id, 'expiry_date' => now()->addDays(15), 'remaining_quantity' => 10]);
        POItem::factory()->create(['product_id' => $product->id, 'expiry_date' => now()->addDays(45), 'remaining_quantity' => 10]);
        POItem::factory()->create(['product_id' => $product->id, 'expiry_date' => now()->addDays(10), 'remaining_quantity' => 0]);

        $items = $this->productService->getExpiringSoonPOItems();
        $this->assertCount(1, $items);
    }

    #[Test]
    public function it_can_get_expiring_soon_po_items_count()
    {
        $product = Product::factory()->create();
        POItem::factory()->create(['product_id' => $product->id, 'expiry_date' => now()->addDays(15), 'remaining_quantity' => 10]);
        POItem::factory()->create(['product_id' => $product->id, 'expiry_date' => now()->addDays(45), 'remaining_quantity' => 10]);
        POItem::factory()->create(['product_id' => $product->id, 'expiry_date' => now()->addDays(10), 'remaining_quantity' => 0]);

        $count = $this->productService->getExpiringSoonPOItemsCount();
        $this->assertEquals(1, $count);
    }

    #[Test]
    public function it_can_get_recently_purchased_expiring_po_items()
    {
        $product = Product::factory()->create();
        POItem::factory()->create(['product_id' => $product->id, 'expiry_date' => now()->addDays(15), 'created_at' => now()->subDays(3)]);
        POItem::factory()->create(['product_id' => $product->id, 'expiry_date' => now()->addDays(15), 'created_at' => now()->subDays(10)]);

        $items = $this->productService->getRecentlyPurchasedExpiringPOItems();
        $this->assertCount(1, $items);
    }
}