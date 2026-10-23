<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Warehouse;
use App\Services\PosService;
use Carbon\Carbon;
use Database\Factories\SalesFactory;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class POSControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected Customer $customer;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.defaults.guard' => 'web']);
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');

        Warehouse::factory()->create(['is_main' => true]);
        $this->customer = Customer::factory()->create();
        $this->product = Product::factory()->create();

        // Ensure stock exists using updateOrCreate
        ProductWarehouse::updateOrCreate(
            [
                'product_id' => $this->product->id,
                'warehouse_id' => Warehouse::first()->id,
                'tenant_id' => $this->product->tenant_id,
            ],
            ['quantity' => 10]
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_can_display_the_pos_index_page()
    {
        $response = $this->get(route('admin.pos'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.pos.index');
        // Assuming pos.index view has 'customers' and 'products'
        $response->assertViewHasAll(['customers', 'products']);
    }

    public function test_it_can_store_a_new_pos_sale()
    {
        $posData = [
            'transaction_date' => Carbon::now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'products' => json_encode([
                [
                    'id' => $this->product->id,
                    'quantity' => 1,
                    'price' => $this->product->selling_price,
                    'total' => $this->product->selling_price * 1,
                ],
            ]),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'grand_total' => $this->product->selling_price,
            'payment_method' => 'Cash',
            'amount_received' => $this->product->selling_price,
            'change_amount' => 0,
        ];

        $response = $this->post(route('admin.pos.store'), $posData);

        $response->assertStatus(302);
        $location = $response->headers->get('Location');
        $saleId = (int) last(explode('/', $location));

        $response->assertRedirect(route('admin.pos.receipt', $saleId));
        $response->assertSessionHas('success', 'Transaction completed successfully.');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'customer_id' => $this->customer->id,
            'type' => Sales::TYPE_POS, // POS sales should be marked as such
        ]);

        $this->assertDatabaseHas('sales_items', [
            'sales_id' => $saleId,
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);
    }

    public function test_it_can_store_a_new_pos_sale_via_ajax()
    {
        $posData = [
            'transaction_date' => Carbon::now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'products' => json_encode([
                [
                    'id' => $this->product->id,
                    'quantity' => 1,
                    'price' => $this->product->selling_price,
                    'total' => $this->product->selling_price * 1,
                ],
            ]),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'grand_total' => $this->product->selling_price,
            'payment_method' => 'Cash',
            'amount_received' => $this->product->selling_price,
            'change_amount' => 0,
        ];

        $response = $this->postJson(route('admin.pos.store'), $posData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Transaction completed successfully.']);
        $saleId = $response->json('sale_id');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'customer_id' => $this->customer->id,
            'type' => Sales::TYPE_POS,
        ]);
    }

    public function test_store_pos_sale_with_invalid_data_returns_validation_errors()
    {
        $invalidData = [
            'transaction_date' => '', // required
            'products' => 'not-a-json-string', // required, json
            'grand_total' => -100, // required, min:0
            'payment_method' => 'Unsupported', // required, in:Cash,Card,Transfer,eWallet
        ];

        $response = $this->postJson(route('admin.pos.store'), $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['transaction_date', 'products', 'grand_total', 'payment_method']);
    }

    public function test_store_pos_sale_requires_amount_received_for_cash_payment()
    {
        $invalidData = [
            'transaction_date' => Carbon::now()->format('Y-m-d'),
            'products' => json_encode([['id' => $this->product->id, 'quantity' => 1, 'price' => 100, 'total' => 100]]),
            'grand_total' => 100,
            'payment_method' => 'Cash',
            'amount_received' => '', // required_if:payment_method,Cash
        ];

        $response = $this->postJson(route('admin.pos.store'), $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount_received']);
    }

    public function test_store_handles_service_exception()
    {
        $posServiceMock = Mockery::mock(PosService::class);
        $this->app->instance(PosService::class, $posServiceMock);

        $errorMessage = 'Service unavailable';
        $posServiceMock->shouldReceive('createSale')->andThrow(new \Exception($errorMessage));

        $posData = [
            'transaction_date' => Carbon::now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'products' => json_encode([['id' => $this->product->id, 'quantity' => 1, 'price' => 100, 'total' => 100]]),
            'grand_total' => 100,
            'payment_method' => 'Cash',
            'amount_received' => 100,
        ];

        $response = $this->postJson(route('admin.pos.store'), $posData);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => $errorMessage,
        ]);
    }

    public function test_it_can_display_the_receipt_page()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => Sales::TYPE_POS,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);

        $response = $this->get(route('admin.pos.receipt', $sale->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.pos.receipt');
        $response->assertViewHas('sale', $sale);
    }

    public function test_it_can_display_the_receipt_page_from_sales_view()
    {
        $this->withoutExceptionHandling();
        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => Sales::TYPE_POS,
            'invoice' => 'POS-1234',
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);

        $response = $this->get(route('admin.sales.show', $sale->id));

        $response->assertRedirect(route('admin.pos.receipt', $sale->id));
    }

    public function test_it_can_display_print_receipt_button()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => Sales::TYPE_POS,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);

        $response = $this->get(route('admin.pos.receipt', $sale->id));

        $response->assertStatus(200);
        $response->assertSee('Print Receipt');
    }

    public function test_pos_store_fails_with_insufficient_stock()
    {
        $posData = [
            'transaction_date' => Carbon::now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'products' => json_encode([
                [
                    'id' => $this->product->id,
                    'quantity' => 100,
                    'price' => $this->product->selling_price,
                    'total' => $this->product->selling_price * 100,
                ],
            ]),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'grand_total' => $this->product->selling_price * 100,
            'payment_method' => 'Cash',
            'amount_received' => $this->product->selling_price * 100,
            'change_amount' => 0,
        ];

        $response = $this->postJson(route('admin.pos.store'), $posData);

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('Insufficient stock', $response->json('message'));
    }

    public function test_pos_store_fails_with_exact_stock_limit()
    {
        $mainWarehouse = Warehouse::where('is_main', true)->first();

        ProductWarehouse::updateOrCreate(
            [
                'product_id' => $this->product->id,
                'warehouse_id' => $mainWarehouse->id,
                'tenant_id' => $this->product->tenant_id,
            ],
            ['quantity' => 5]
        );

        $posData = [
            'transaction_date' => Carbon::now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'products' => json_encode([
                [
                    'id' => $this->product->id,
                    'quantity' => 6,
                    'price' => $this->product->selling_price,
                    'total' => $this->product->selling_price * 6,
                ],
            ]),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'grand_total' => $this->product->selling_price * 6,
            'payment_method' => 'Cash',
            'amount_received' => $this->product->selling_price * 6,
            'change_amount' => 0,
        ];

        $response = $this->postJson(route('admin.pos.store'), $posData);

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
    }

    public function test_pos_store_succeeds_with_exact_available_stock()
    {
        $mainWarehouse = Warehouse::where('is_main', true)->first();

        ProductWarehouse::updateOrCreate(
            [
                'product_id' => $this->product->id,
                'warehouse_id' => $mainWarehouse->id,
                'tenant_id' => $this->product->tenant_id,
            ],
            ['quantity' => 5]
        );

        $posData = [
            'transaction_date' => Carbon::now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'products' => json_encode([
                [
                    'id' => $this->product->id,
                    'quantity' => 5,
                    'price' => $this->product->selling_price,
                    'total' => $this->product->selling_price * 5,
                ],
            ]),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'grand_total' => $this->product->selling_price * 5,
            'payment_method' => 'Cash',
            'amount_received' => $this->product->selling_price * 5,
            'change_amount' => 0,
        ];

        $response = $this->postJson(route('admin.pos.store'), $posData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_pos_index_renders_only_one_page_of_products_with_scroll_loading()
    {
        // setUp already creates 1 product; 60 more = 61 total (page size 60).
        Product::factory()->count(60)->create();

        $response = $this->get(route('admin.pos'));

        $response->assertStatus(200);
        $this->assertSame(60, substr_count($response->getContent(), 'data-product-id='));
        $response->assertDontSee('id="loadMoreProducts"', false); // no button: scrolling loads more
        $response->assertSee('data-has-more="1"', false);
        $response->assertViewHas('has_more', true);
    }

    public function test_pos_index_marks_grid_as_fully_loaded_when_everything_fits_on_one_page()
    {
        $response = $this->get(route('admin.pos'));

        $response->assertStatus(200);
        $response->assertViewHas('has_more', false);
        $response->assertDontSee('id="loadMoreProducts"', false);
        $response->assertSee('data-has-more="0"', false);
    }

    public function test_pos_products_endpoint_filters_by_name_and_code()
    {
        Product::factory()->create(['name' => 'Kapasitor Filter Special']);

        $response = $this->getJson(route('admin.pos.products', ['q' => 'Kapasitor']));

        $response->assertStatus(200)->assertJsonPath('success', true);
        $products = $response->json('products');
        $this->assertNotEmpty($products);
        foreach ($products as $product) {
            $haystack = strtolower($product['name'].' '.($product['code'] ?? ''));
            $this->assertStringContainsString('kapasitor', $haystack);
        }
        $this->assertArrayHasKey('is_placeholder', $products[0]);
        $this->assertArrayHasKey('image', $products[0]);
        $this->assertArrayHasKey('main_warehouse_stock', $products[0]);
    }

    public function test_pos_products_endpoint_paginates_with_has_more()
    {
        Product::factory()->count(65)->create(); // + 1 from setUp = 66

        $first = $this->getJson(route('admin.pos.products'));
        $first->assertStatus(200);
        $this->assertCount(60, $first->json('products'));
        $this->assertTrue($first->json('has_more'));

        $second = $this->getJson(route('admin.pos.products', ['offset' => 60]));
        $second->assertStatus(200);
        $this->assertCount(6, $second->json('products'));
        $this->assertFalse($second->json('has_more'));
    }

    public function test_pos_products_endpoint_rejects_negative_offsets()
    {
        $this->getJson(route('admin.pos.products', ['offset' => -1]))
            ->assertStatus(422);
    }
}
