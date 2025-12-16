<?php

namespace Tests\Feature\Api\V1;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $userWithoutPermissions;

    public function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $permissions = [
            'view-products',
            'create-products',
            'edit-products',
            'delete-products',
        ];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Create a user with permissions
        $this->user = User::factory()->create();
        $this->user->givePermissionTo($permissions);

        // Create a user without permissions
        $this->userWithoutPermissions = User::factory()->create();

        // Create a main warehouse required by the ProductService
        \App\Models\Warehouse::factory()->create(['is_main' => true]);
    }

    #[Test]
    public function test_unauthenticated_user_cannot_get_products()
    {
        $response = $this->getJson('/api/v1/products');
        $response->assertStatus(401);
    }

    #[Test]
    public function test_unauthorized_user_cannot_get_products()
    {
        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->getJson('/api/v1/products');
        $response->assertStatus(403);
    }

    #[Test]
    public function test_can_get_all_products()
    {
        Product::factory()->count(3)->create();
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/products');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'code',
                    'stock_quantity',
                    'selling_price',
                ]
            ]
        ]);
    }
    
    #[Test]
    public function test_can_get_a_product()
    {
        $product = Product::factory()->create();
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/products/' . $product->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'code',
                'description',
                'stock_quantity',
                'low_stock_threshold',
                'price',
                'selling_price',
                'has_expiry',
                'image',
                'category',
                'unit',
                'supplier',
                'warehouse',
            ]
        ]);
        $response->assertJsonFragment(['id' => $product->id]);
    }

    #[Test]
    public function test_store_fails_with_invalid_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/products', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'code', 'category_id', 'units_id', 'price', 'selling_price']);
    }

    #[Test]
    public function test_can_create_a_product()
    {
        $productData = [
            'name' => 'New API Product',
            'code' => 'API-001',
            'category_id' => \App\Models\Categories::factory()->create()->id,
            'units_id' => \App\Models\Unit::factory()->create()->id,
            'supplier_id' => \App\Models\Supplier::factory()->create()->id,
            'price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 50,
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/products', $productData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', [
            'name' => 'New API Product',
            'code' => 'API-001'
        ]);
    }

    #[Test]
    public function test_can_update_a_product()
    {
        $product = Product::factory()->create();
        $updateData = [
            'name' => 'Updated API Product',
            'selling_price' => 200,
            // Include required fields for validation to pass
            'code' => $product->code,
            'category_id' => $product->category_id,
            'units_id' => $product->units_id,
            'price' => $product->price,
        ];
    
        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/products/' . $product->id, $updateData);
    
        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'id' => $product->id, 
            'name' => 'Updated API Product',
            'selling_price' => 200
        ]);
    }

    #[Test]
    public function test_can_delete_a_product()
    {
        $product = Product::factory()->create();
        $response = $this->actingAs($this->user, 'sanctum')->deleteJson('/api/v1/products/' . $product->id);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}