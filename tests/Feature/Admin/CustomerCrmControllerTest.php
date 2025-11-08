<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Customer;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCrmControllerTest extends TestCase
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
    }

    public function test_it_can_display_customer_crm_data()
    {
        $customer = Customer::factory()->create();

        $response = $this->getJson("/admin/customers/{$customer->id}/crm-details");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'customer',
                'lifetimeValue',
                'totalSalesCount',
                'averageOrderValue',
                'favoriteCategory',
                'lastPurchaseDate',
                'lastInteractionDate',
                'mostPurchasedProduct',
                'totalProductsPurchased',
                'sales',
                'currencySettings',
            ]);
    }

    public function test_it_can_store_a_customer_interaction()
    {
        $customer = Customer::factory()->create();

        $interactionData = [
            'type' => 'Call',
            'notes' => 'Called customer to follow up on a sale.',
            'interaction_date' => now()->format('Y-m-d'),
        ];

        $response = $this->postJson("/admin/customers/{$customer->id}/interactions", $interactionData);

        $response->assertStatus(200)
            ->assertJsonFragment(['type' => 'Call']);

        $this->assertDatabaseHas('customer_interactions', [
            'customer_id' => $customer->id,
            'type' => 'Call',
            'notes' => 'Called customer to follow up on a sale.',
        ]);
    }

    public function test_it_can_get_customer_product_history()
    {
        $customer = Customer::factory()->create();
        $product = \App\Models\Product::factory()->create();
        $sale = \App\Models\Sales::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'order_date' => now()->format('Y-m-d H:i:s'),
        ]);
        \App\Models\SalesItem::factory()->create([
            'sales_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'customer_price' => 50,
        ]);

        $response = $this->getJson("/admin/customers/{$customer->id}/product-history");

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'product_name',
                    'last_price',
                    'history' => [
                        '*' => [
                            'invoice',
                            'order_date',
                            'quantity',
                            'price_at_purchase',
                        ],
                    ],
                ],
            ]);
    }
}
