<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Warehouse;
use Carbon\Carbon;
use Database\Factories\SalesFactory;
use Database\Seeders\AccountSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class SalesControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected Customer $customer;

    protected Product $product;

    protected Warehouse $warehouse; // Added

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(AccountSeeder::class);
        $this->user->assignRole('superuser');

        // Ensure the current tenant has the necessary accounting settings
        $tenantId = app('currentTenant')->id;
        $this->tenant->accounting_settings = [
            'cash_account_id' => Account::where('code', '1110-'.$tenantId)->first()->id,
            'accounts_receivable_account_id' => Account::where('code', '1130-'.$tenantId)->first()->id,
            'sales_revenue_account_id' => Account::where('code', '4100-'.$tenantId)->first()->id,
            'cost_of_goods_sold_account_id' => Account::where('code', '5200-'.$tenantId)->first()->id,
            'inventory_account_id' => Account::where('code', '1140-'.$tenantId)->first()->id,
        ];
        $this->tenant->save();
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::factory()->create(['is_main' => true]); // Added
        $this->customer = Customer::factory()->create();
        $this->product = Product::factory()->create();

        // Ensure product has stock in the warehouse
        ProductWarehouse::updateOrCreate(
            [
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
                'tenant_id' => $tenantId,
            ],
            ['quantity' => 100]
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_can_display_the_sales_index_page()
    {
        SalesFactory::new()->count(5)->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('admin.sales'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales.index');
        $response->assertViewHas('sales');
    }

    public function test_it_can_display_the_sales_create_page()
    {
        $response = $this->get(route('admin.sales.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales.sales-create');
        $response->assertViewHasAll(['sales', 'customers', 'products', 'items', 'tax']);
    }

    public function test_it_can_store_a_new_sale()
    {
        $salesData = [
            'invoice' => 'INV-'.rand(10000, 99999),
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id, // Added
            'order_date' => Carbon::now()->format('Y-m-d'),
            'due_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'discount_total' => 10,
            'discount_total_type' => 'fixed',
            'products' => json_encode([
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'customer_price' => $this->product->selling_price, // Changed from 'price'
                    'total' => $this->product->selling_price * 2,
                ],
            ]),
        ];

        $response = $this->post(route('admin.sales.store'), $salesData);

        $response->assertRedirect(route('admin.sales'));
        $response->assertSessionHas('success', 'Sale created successfully.');

        $this->assertDatabaseHas('sales', [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id, // Added
            'invoice' => $salesData['invoice'],
        ]);

        $this->assertDatabaseHas('sales_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);
    }

    public function test_it_can_display_the_sales_edit_page()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);

        $response = $this->get(route('admin.sales.edit', $sale->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales.sales-edit');
        $response->assertViewHas('sales', $sale);
    }

    public function test_it_can_update_a_sale()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);

        $newProduct = Product::factory()->create();
        // Ensure new product has stock
        ProductWarehouse::updateOrCreate(
            [
                'product_id' => $newProduct->id,
                'warehouse_id' => $this->warehouse->id,
                'tenant_id' => $sale->tenant_id,
            ],
            ['quantity' => 100]
        );

        $updateData = [
            'invoice' => $sale->invoice,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id, // Added
            'order_date' => Carbon::now()->format('Y-m-d'),
            'due_date' => Carbon::now()->addDays(14)->format('Y-m-d'),
            'discount_total' => 5,
            'discount_total_type' => 'percentage',
            'products' => json_encode([
                [
                    'product_id' => $newProduct->id,
                    'quantity' => 3,
                    'customer_price' => $newProduct->selling_price,
                    'total' => $newProduct->selling_price * 3,
                ],
            ]),
        ];

        $response = $this->put(route('admin.sales.update', $sale->id), $updateData);

        $response->assertRedirect(route('admin.sales.show', $sale->id));
        $response->assertSessionHas('success', 'Sale updated successfully.');

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'order_discount' => 5, // Changed from 'discount_total'
            'order_discount_type' => 'percentage', // Changed from 'discount_total_type'
        ]);

        $this->assertDatabaseHas('sales_items', [
            'sales_id' => $sale->id,
            'product_id' => $newProduct->id,
            'quantity' => 3,
        ]);
    }

    public function test_it_can_delete_a_sale()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);

        $response = $this->delete(route('admin.sales.destroy', $sale->id));

        $response->assertRedirect(route('admin.sales'));
        $response->assertSessionHas('success', 'Sales order deleted successfully');

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertDatabaseMissing('sales_items', ['sales_id' => $sale->id]);
    }

    public function test_it_can_display_the_sales_view_page()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => Sales::TYPE_REGULAR,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);

        $response = $this->get(route('admin.sales.show', $sale->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales.show');
        $response->assertViewHas('sales', $sale);
    }

    public function test_it_can_add_payment_to_a_sale()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'total' => 100,
            'status' => 'Unpaid',
        ]);

        $paymentData = [
            'amount' => 50,
            'payment_date' => Carbon::now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'notes' => 'Partial payment',
        ];

        $response = $this->post(route('admin.sales.add-payment', $sale->id), $paymentData);

        $response->assertRedirect(route('admin.sales.show', $sale->id));
        $response->assertSessionHas('success', 'Payment added successfully.');

        $this->assertDatabaseHas('payments', [
            'paymentable_id' => $sale->id,
            'paymentable_type' => Sales::class,
            'amount' => 50,
        ]);
    }

    public function test_it_can_bulk_delete_sales()
    {
        $sales = SalesFactory::new()->count(3)->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
        ]);
        foreach ($sales as $sale) {
            SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);
        }

        $idsToDelete = $sales->pluck('id')->toArray();

        $response = $this->postJson(route('sales.bulk-delete'), ['ids' => $idsToDelete]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Successfully deleted sales order(s)',
            ]);

        foreach ($idsToDelete as $id) {
            $this->assertDatabaseMissing('sales', ['id' => $id]);
            $this->assertDatabaseMissing('sales_items', ['sales_id' => $id]);
        }
    }

    public function test_it_can_bulk_mark_paid_sales()
    {
        $product = Product::factory()->create();
        $sales = SalesFactory::new()->count(3)->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'status' => 'Unpaid',
            'total' => 100,
            'amount_received' => 0,
        ]);

        foreach ($sales as $sale) {
            SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $product->id, 'quantity' => 1, 'total' => 100]);
        }

        $idsToMarkPaid = $sales->pluck('id')->toArray();

        $response = $this->postJson(route('sales.bulk-mark-paid'), ['ids' => $idsToMarkPaid]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Successfully marked 3 sales order(s) as paid.',
                'updated_count' => 3,
            ]);

        foreach ($idsToMarkPaid as $id) {
            $this->assertDatabaseHas('sales', [
                'id' => $id,
                'status' => 'Paid',
            ]);
        }
    }

    public function test_it_can_get_customer_price_for_product()
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        // Create a sales record for this customer and product
        $sale = SalesFactory::new()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
        ]);
        SalesItem::factory()->create([
            'sales_id' => $sale->id,
            'product_id' => $product->id,
            'customer_price' => 123.45, // Specific price to assert
        ]);

        $response = $this->get(route('admin.sales.get-customer-price', ['customer' => $customer->id, 'product' => $product->id]));

        $response->assertOk()
            ->assertJson(['past_price' => 123.45]);
    }
}
