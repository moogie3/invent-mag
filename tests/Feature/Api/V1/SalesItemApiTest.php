<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Sales;
use App\Models\Product;
use App\Models\SalesItem;
use Spatie\Permission\Models\Role;

class SalesItemApiTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $sales;
    private $product;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->user->assignRole($role);
        $this->sales = Sales::factory()->create();
        $this->product = Product::factory()->create();
    }

    public function test_index_returns_unauthorized_if_user_is_not_authenticated()
    {
        $response = $this->getJson('/api/v1/sales-items');

        $response->assertUnauthorized();
    }

    public function test_index_returns_json_data()
    {
        SalesItem::factory()->count(3)->create([
            'sales_id' => $this->sales->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/sales-items');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'sales_id',
                        'product_id',
                        'quantity',
                        'customer_price',
                        'discount',
                        'discount_type',
                        'total',
                        'price',
                        'product' => [
                            'id',
                            'name',
                        ]
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    public function test_show_returns_unauthorized_if_user_is_not_authenticated()
    {
        $salesItem = SalesItem::factory()->create([
            'sales_id' => $this->sales->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->getJson("/api/v1/sales-items/{$salesItem->id}");

        $response->assertUnauthorized();
    }

    public function test_show_returns_json_data()
    {
        $salesItem = SalesItem::factory()->create([
            'sales_id' => $this->sales->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/sales-items/{$salesItem->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $salesItem->id,
                    'sales_id' => $this->sales->id,
                    'product_id' => $this->product->id,
                    'quantity' => $salesItem->quantity,
                    'customer_price' => round($salesItem->customer_price, 2),
                    'discount' => $salesItem->discount,
                    'discount_type' => $salesItem->discount_type,
                    'total' => round($salesItem->total, 2),
                    'price' => round($salesItem->price, 2),
                    'product' => [
                        'id' => $this->product->id,
                        'name' => $this->product->name,
                    ]
                ]
            ]);
    }
}
