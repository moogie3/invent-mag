<?php

namespace Tests\Feature\Api\V1;

use App\Models\Product;
use App\Models\User;
use App\Models\Categories;
use App\Models\Unit;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Illuminate\Support\Facades\Auth;

class ProductApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    private User $userWithoutPermission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();

        $permissions = [
            'view-products',
            'create-products',
            'edit-products',
            'delete-products',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
            Permission::findOrCreate($permission, 'api');
        }

        $this->user->givePermissionTo($permissions);
        $this->userWithoutPermission = User::factory()->create(['tenant_id' => $this->tenant->id]);

        \App\Models\Warehouse::factory()->create(['is_main' => true, 'tenant_id' => $this->tenant->id]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_products_api()
    {
        Auth::guard('web')->logout();
        $this->getJson('/api/v1/products')->assertStatus(401);
    }

    #[Test]
    public function user_without_permission_is_forbidden_from_products_api()
    {
        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/products')
            ->assertStatus(403);
    }

    #[Test]
    public function authenticated_user_can_list_products()
    {
        Product::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function authenticated_user_can_view_a_product()
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$product->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $product->id]);
    }

    #[Test]
    public function authenticated_user_can_create_a_product()
    {
        $payload = [
            'name' => 'API Product',
            'code' => 'API-001',
            'category_id' => Categories::factory()->create(['tenant_id' => $this->tenant->id])->id,
            'units_id' => Unit::factory()->create(['tenant_id' => $this->tenant->id])->id,
            'supplier_id' => Supplier::factory()->create(['tenant_id' => $this->tenant->id])->id,
            'price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 10,
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $payload)
            ->assertStatus(201);

        $this->assertDatabaseHas('products', [
            'code' => 'API-001',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    #[Test]
    public function authenticated_user_can_update_a_product()
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", [
                'name' => 'Updated Product',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    #[Test]
    public function authenticated_user_can_delete_a_product()
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }
}
