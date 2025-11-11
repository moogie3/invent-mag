<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Warehouse;
use Database\Factories\SalesFactory;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Carbon\Carbon;
use App\Services\PosService;
use Mockery;

class POSControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;
    protected Product $product;

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

        Warehouse::factory()->create(['is_main' => true]);
        $this->customer = Customer::factory()->create();
        $this->product = Product::factory()->create(['stock_quantity' => 10]); // Ensure stock
    }

    public function tearDown(): void
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
            'tax_rate' => 0,
            'tax_amount' => 0,
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
            'is_pos' => true, // POS sales should be marked as such
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
            'tax_rate' => 0,
            'tax_amount' => 0,
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
            'is_pos' => true,
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

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Something went wrong: ' . $errorMessage,
        ]);
    }

    public function test_it_can_display_the_receipt_page()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'is_pos' => true,
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
            'is_pos' => true,
            'invoice' => 'POS-1234'
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);

        $response = $this->get(route('admin.sales.view', $sale->id));

        $response->assertRedirect(route('admin.pos.receipt', $sale->id));
    }

    public function test_it_can_display_print_receipt_button()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'is_pos' => true,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);

        $response = $this->get(route('admin.pos.receipt', $sale->id));

        $response->assertStatus(200);
        $response->assertSee('Print Receipt');
    }
}
