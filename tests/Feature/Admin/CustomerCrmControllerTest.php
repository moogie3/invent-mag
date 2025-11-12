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

    public function test_show_handles_service_level_exception()
    {
        // $this->withoutExceptionHandling(); // Temporarily disable exception handling

        $customer = Customer::factory()->create();
        $errorMessage = 'Failed to load CRM data: Service exception.';

        // Mock the CrmService to throw an exception
        $mockService = \Mockery::mock(\App\Services\CrmService::class);
        $mockService->shouldReceive('getCustomerCrmData')
                    ->once()
                    ->andThrow(new \Exception('Service exception.'));
        $this->app->instance(\App\Services\CrmService::class, $mockService);

        $response = $this->getJson("/admin/customers/{$customer->id}/crm-details");

        $response->assertStatus(500) // Internal Server Error
            ->assertJson(['message' => $errorMessage]);
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

    public function test_store_interaction_with_invalid_data_returns_validation_errors()
    {
        $customer = Customer::factory()->create();

        $invalidInteractionData = [
            'type' => '', // Required
            'notes' => '', // Required
            'interaction_date' => 'not-a-date', // Invalid date
        ];

        $response = $this->postJson("/admin/customers/{$customer->id}/interactions", $invalidInteractionData);

        $response->assertStatus(422) // Unprocessable Entity for validation errors
            ->assertJsonValidationErrors(['type', 'notes', 'interaction_date']);
    }

    public function test_store_interaction_handles_service_level_exception()
    {
        // $this->withoutExceptionHandling(); // Temporarily disable exception handling

        $customer = Customer::factory()->create();
        $interactionData = [
            'type' => 'Call',
            'notes' => 'Called customer to follow up on a sale.',
            'interaction_date' => now()->format('Y-m-d'),
        ];
        $errorMessage = 'Failed to store interaction: Service interaction failed.';

        // Mock the CrmService to throw an exception
        $mockService = \Mockery::mock(\App\Services\CrmService::class);
        $mockService->shouldReceive('storeCustomerInteraction')
                    ->once()
                    ->andThrow(new \Exception('Service interaction failed.'));
        $this->app->instance(\App\Services\CrmService::class, $mockService);

        $response = $this->postJson("/admin/customers/{$customer->id}/interactions", $interactionData);

        $response->assertStatus(500) // Internal Server Error
            ->assertJson(['message' => $errorMessage]);
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

    public function test_get_product_history_handles_service_level_exception()
    {
        // $this->withoutExceptionHandling(); // Temporarily disable exception handling

        $customer = Customer::factory()->create();
        $errorMessage = 'Failed to load product history: Service product history failed.';

        // Mock the CrmService to throw an exception
        $mockService = \Mockery::mock(\App\Services\CrmService::class);
        $mockService->shouldReceive('getCustomerProductHistory')
                    ->once()
                    ->andThrow(new \Exception('Service product history failed.'));
        $this->app->instance(\App\Services\CrmService::class, $mockService);

        $response = $this->getJson("/admin/customers/{$customer->id}/product-history");

        $response->assertStatus(500) // Internal Server Error
            ->assertJson(['message' => $errorMessage]);
    }

    public function test_get_historical_purchases_returns_json()
    {
        $customer = Customer::factory()->create();
        $expectedPurchases = [
            ['id' => 1, 'date' => '2023-01-01', 'total' => 100.00],
            ['id' => 2, 'date' => '2023-02-15', 'total' => 250.50],
        ];

        // Mock the CrmService
        $mockService = \Mockery::mock(\App\Services\CrmService::class);
        $mockService->shouldReceive('getHistoricalPurchases')
                    ->once()
                    ->with(\Mockery::on(function ($argCustomer) use ($customer) {
                        return $argCustomer->id === $customer->id;
                    }))
                    ->andReturn($expectedPurchases);
        $this->app->instance(\App\Services\CrmService::class, $mockService);

        $response = $this->get(route('admin.customer.historical-purchases', $customer->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'historical_purchases' => $expectedPurchases,
        ]);
    }

    public function test_get_historical_purchases_handles_service_level_exception()
    {
        // $this->withoutExceptionHandling(); // Temporarily disable exception handling

        $customer = Customer::factory()->create();
        $errorMessage = 'Failed to load historical purchases: Service historical purchases failed.';

        // Mock the CrmService to throw an exception
        $mockService = \Mockery::mock(\App\Services\CrmService::class);
        $mockService->shouldReceive('getHistoricalPurchases')
                    ->once()
                    ->andThrow(new \Exception('Service historical purchases failed.'));
        $this->app->instance(\App\Services\CrmService::class, $mockService);

        $response = $this->getJson(route('admin.customer.historical-purchases', $customer->id));

        $response->assertStatus(500) // Internal Server Error
            ->assertJson(['message' => $errorMessage]);
    }
}