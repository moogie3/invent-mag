<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\Product;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Tests\Traits\CreatesTenant;
use Illuminate\Support\Facades\Auth;

class SupplierApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    private $supplier;
    private $product;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();

        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->user->assignRole($role);
        $this->supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_index_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson('/api/v1/suppliers');

        $response->assertUnauthorized();
    }

    public function test_index_returns_json_data()
    {
        Supplier::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/suppliers');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'phone_number',
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    public function test_store_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $response = $this->postJson('/api/v1/suppliers', []);

        $response->assertUnauthorized();
    }

    public function test_show_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson("/api/v1/suppliers/{$this->supplier->id}");

        $response->assertUnauthorized();
    }

    public function test_show_returns_json_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/suppliers/{$this->supplier->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $this->supplier->id,
                    'name' => $this->supplier->name,
                ]
            ]);
    }

    public function test_update_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $response = $this->putJson("/api/v1/suppliers/{$this->supplier->id}", ['name' => 'Updated Supplier']);

        $response->assertUnauthorized();
    }

    public function test_update_modifies_an_existing_supplier()
    {
        $newName = 'Updated Supplier';

        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/v1/suppliers/{$this->supplier->id}", [
            'code' => $this->supplier->code, // Include existing code
            'name' => $newName,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'name' => $newName
                ]
            ]);

        $this->assertDatabaseHas('suppliers', [
            'id' => $this->supplier->id,
            'name' => $newName,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_destroy_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $response = $this->deleteJson("/api/v1/suppliers/{$this->supplier->id}");

        $response->assertUnauthorized();
    }

    public function test_destroy_deletes_a_supplier()
    {
        $response = $this->actingAs($this->user, 'sanctum')->deleteJson("/api/v1/suppliers/{$this->supplier->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('suppliers', [
            'id' => $this->supplier->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_metrics_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson('/api/v1/suppliers/metrics');

        $response->assertUnauthorized();
    }

    public function test_metrics_returns_json_data()
    {
        Purchase::factory()->count(3)->create(['supplier_id' => $this->supplier->id, 'tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/suppliers/metrics');

        $response->assertOk()
            ->assertJsonStructure([
                'totalsupplier',
                'inCount',
                'outCount',
            ]);
    }

    public function test_historical_purchases_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson("/api/v1/suppliers/{$this->supplier->id}/historical-purchases");

        $response->assertUnauthorized();
    }

    public function test_historical_purchases_returns_json_data()
    {
        Purchase::factory()->count(3)->create(['supplier_id' => $this->supplier->id, 'tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/suppliers/{$this->supplier->id}/historical-purchases");

        $response->assertOk()
            ->assertJsonStructure([
                'historical_purchases' => [
                    '*' => [
                        'id',
                        'invoice',
                        'order_date',
                        'total',
                        'status',
                    ]
                ],
            ]);
    }

    public function test_product_history_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson("/api/v1/suppliers/{$this->supplier->id}/product-history");

        $response->assertUnauthorized();
    }

    public function test_product_history_returns_json_data()
    {
        Purchase::factory()->count(1)->create(['supplier_id' => $this->supplier->id, 'tenant_id' => $this->tenant->id])
            ->each(function ($purchase) {
                $purchase->items()->create([
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'price' => 50,
                    'total' => 500,
                ]);
            });

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/suppliers/{$this->supplier->id}/product-history");

        $response->assertOk()
            ->assertJsonStructure([
                'product_history' => [
                    '*' => [
                        'product_name',
                        'last_price',
                        'history' => [
                            '*' => [
                                'product_id',
                                'product_name',
                                'order_date',
                                'invoice',
                                'quantity',
                                'price_at_purchase',
                                'line_total',
                            ]
                        ]
                    ]
                ]
            ]);
    }
}