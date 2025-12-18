<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\StockAdjustment;
use Spatie\Permission\Models\Role;

class StockAdjustmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $product;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->user->assignRole($role);
        $this->product = Product::factory()->create();
    }

    public function test_index_returns_unauthorized_if_user_is_not_authenticated()
    {
        $response = $this->getJson('/api/v1/stock-adjustments');

        $response->assertUnauthorized();
    }

    public function test_index_returns_json_data()
    {
        StockAdjustment::factory()->count(3)->create([
            'product_id' => $this->product->id,
            'adjusted_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/stock-adjustments');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'product_id',
                        'adjustment_type',
                        'quantity_before',
                        'quantity_after',
                        'adjustment_amount',
                        'reason',
                        'product' => [
                            'id',
                            'name',
                        ],
                        'adjusted_by' => [
                            'id',
                            'name',
                        ],
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    public function test_show_returns_unauthorized_if_user_is_not_authenticated()
    {
        $adjustment = StockAdjustment::factory()->create([
            'product_id' => $this->product->id,
            'adjusted_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/stock-adjustments/{$adjustment->id}");

        $response->assertUnauthorized();
    }

    public function test_show_returns_json_data()
    {
        $adjustment = StockAdjustment::factory()->create([
            'product_id' => $this->product->id,
            'adjusted_by' => $this->user->id,
            'quantity_before' => 50.5,
            'quantity_after' => 60.5,
            'adjustment_amount' => 10.0,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/stock-adjustments/{$adjustment->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $adjustment->id,
                    'product_id' => $this->product->id,
                    'adjustment_type' => $adjustment->adjustment_type,
                    'quantity_before' => $adjustment->quantity_before,
                    'quantity_after' => $adjustment->quantity_after,
                    'adjustment_amount' => $adjustment->adjustment_amount,
                    'reason' => $adjustment->reason,
                    'product' => [
                        'id' => $this->product->id,
                        'name' => $this->product->name,
                    ],
                    'adjusted_by' => [
                        'id' => $this->user->id,
                        'name' => $this->user->name,
                    ],
                ]
            ]);
    }
}