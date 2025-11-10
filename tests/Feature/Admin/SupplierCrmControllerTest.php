<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\POItem;
use App\Models\Product;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierCrmControllerTest extends TestCase
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
}
