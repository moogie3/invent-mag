<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\POItem;
use App\Models\Product;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;
use App\Services\CrmService;

class SupplierCrmControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
        $this->actingAs($this->user);
    }

    public function test_it_can_display_supplier_crm_data()
    {
        $supplier = Supplier::factory()->create();

        // Create some purchases and POItems for the supplier
        $purchase1 = Purchase::factory()->create(['supplier_id' => $supplier->id, 'total' => 100]);
        POItem::factory()->create(['po_id' => $purchase1->id, 'quantity' => 1, 'price' => 100]);

        $purchase2 = Purchase::factory()->create(['supplier_id' => $supplier->id, 'total' => 200]);
        POItem::factory()->create(['po_id' => $purchase2->id, 'quantity' => 2, 'price' => 100]);

        $response = $this->getJson("/admin/suppliers/{$supplier->id}/srm-details");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'supplier',
                'lifetimeValue',
                'totalPurchasesCount',
                'averagePurchaseValue',
                'lastPurchaseDate',
                'lastInteractionDate',
                'mostPurchasedProduct',
                'totalProductsPurchased',
                'purchases',
                'currencySettings',
            ]);
    }

    public function test_show_handles_service_level_exception()
    {
        // $this->withoutExceptionHandling(); // Temporarily disable exception handling

        $supplier = Supplier::factory()->create();
        $errorMessage = 'Failed to load SRM data: Service exception.';

        // Mock the CrmService to throw an exception
        $mockService = \Mockery::mock(CrmService::class);
        $mockService->shouldReceive('getSupplierCrmData')
                    ->once()
                    ->andThrow(new \Exception('Service exception.'));
        $this->app->instance(CrmService::class, $mockService);

        $response = $this->getJson("/admin/suppliers/{$supplier->id}/srm-details");

        $response->assertStatus(500) // Internal Server Error
            ->assertJson(['error' => $errorMessage]);
    }

    public function test_it_can_store_a_supplier_interaction()
    {
        $supplier = Supplier::factory()->create();

        $interactionData = [
            'type' => 'Call',
            'notes' => 'Called supplier to discuss new product line.',
            'interaction_date' => now()->format('Y-m-d'),
        ];

        $response = $this->postJson("/admin/suppliers/{$supplier->id}/interactions", $interactionData);

        $response->assertStatus(200)
            ->assertJsonFragment(['type' => 'Call']);

        $this->assertDatabaseHas('supplier_interactions', [
            'supplier_id' => $supplier->id,
            'type' => 'Call',
            'notes' => 'Called supplier to discuss new product line.',
        ]);
    }

    public function test_store_interaction_with_invalid_data_returns_validation_errors()
    {
        $supplier = Supplier::factory()->create();

        $invalidInteractionData = [
            'type' => '', // Required
            'notes' => '', // Required
            'interaction_date' => 'not-a-date', // Invalid date
        ];

        $response = $this->postJson("/admin/suppliers/{$supplier->id}/interactions", $invalidInteractionData);

        $response->assertStatus(422) // Unprocessable Entity for validation errors
            ->assertJsonValidationErrors(['type', 'notes', 'interaction_date']);
    }

    public function test_store_interaction_handles_service_level_exception()
    {
        // $this->withoutExceptionHandling(); // Temporarily disable exception handling

        $supplier = Supplier::factory()->create();
        $interactionData = [
            'type' => 'Call',
            'notes' => 'Called supplier to follow up on a sale.',
            'interaction_date' => now()->format('Y-m-d'),
        ];
        $errorMessage = 'Failed to store interaction: Service interaction failed.';

        // Mock the CrmService to throw an exception
        $mockService = \Mockery::mock(CrmService::class);
        $mockService->shouldReceive('storeSupplierInteraction')
                    ->once()
                    ->andThrow(new \Exception('Service interaction failed.'));
        $this->app->instance(CrmService::class, $mockService);

        $response = $this->postJson("/admin/suppliers/{$supplier->id}/interactions", $interactionData);

        $response->assertStatus(500) // Internal Server Error
            ->assertJson(['message' => $errorMessage]);
    }

    public function test_it_can_get_supplier_historical_purchases()
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $purchase = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'order_date' => now()->subDays(5)->format('Y-m-d H:i:s'),
        ]);
        POItem::factory()->create([
            'po_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'price' => 50,
        ]);

        $response = $this->getJson("/admin/suppliers/{$supplier->id}/historical-purchases");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'historical_purchases' => [
                    '*' => [
                        'invoice',
                        'order_date',
                        'total',
                        'items' => [
                            '*' => [
                                'product_name',
                                'quantity',
                                'price',
                            ]
                        ]
                    ]
                ],
            ]);
    }

    public function test_get_historical_purchases_handles_service_level_exception()
    {
        // $this->withoutExceptionHandling(); // Temporarily disable exception handling

        $supplier = Supplier::factory()->create();
        $errorMessage = 'Failed to load historical purchases: Service historical purchases failed.';

        // Mock the CrmService to throw an exception
        $mockService = \Mockery::mock(CrmService::class);
        $mockService->shouldReceive('getSupplierHistoricalPurchases')
                    ->once()
                    ->andThrow(new \Exception('Service historical purchases failed.'));
        $this->app->instance(CrmService::class, $mockService);

        $response = $this->getJson("/admin/suppliers/{$supplier->id}/historical-purchases");

        $response->assertStatus(500) // Internal Server Error
            ->assertJson(['message' => $errorMessage]);
    }

    public function test_it_can_get_supplier_product_history()
    {
        $supplier = Supplier::factory()->create();
        $product1 = Product::factory()->create(['name' => 'Product A']);
        $product2 = Product::factory()->create(['name' => 'Product B']);

        $purchase1 = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'order_date' => now()->subDays(10)->format('Y-m-d H:i:s'),
        ]);
        POItem::factory()->create([
            'po_id' => $purchase1->id,
            'product_id' => $product1->id,
            'quantity' => 5,
            'price' => 10,
        ]);

        $purchase2 = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'order_date' => now()->subDays(2)->format('Y-m-d H:i:s'),
        ]);
        POItem::factory()->create([
            'po_id' => $purchase2->id,
            'product_id' => $product1->id,
            'quantity' => 3,
            'price' => 12,
        ]);
        POItem::factory()->create([
            'po_id' => $purchase2->id,
            'product_id' => $product2->id,
            'quantity' => 2,
            'price' => 25,
        ]);

        $response = $this->getJson("/admin/suppliers/{$supplier->id}/product-history");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'product_history' => [
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
                ],
            ]);
    }

    public function test_get_product_history_handles_service_level_exception()
    {
        // $this->withoutExceptionHandling(); // Temporarily disable exception handling

        $supplier = Supplier::factory()->create();
        $errorMessage = 'Failed to load product history: Service product history failed.';

        // Mock the CrmService to throw an exception
        $mockService = \Mockery::mock(CrmService::class);
        $mockService->shouldReceive('getSupplierProductHistory')
                    ->once()
                    ->andThrow(new \Exception('Service product history failed.'));
        $this->app->instance(CrmService::class, $mockService);

        $response = $this->getJson("/admin/suppliers/{$supplier->id}/product-history");

        $response->assertStatus(500) // Internal Server Error
            ->assertJson(['message' => $errorMessage]);
    }
}