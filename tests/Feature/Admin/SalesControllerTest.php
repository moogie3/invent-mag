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

class SalesControllerTest extends TestCase
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
        $this->product = Product::factory()->create();
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
            'invoice' => 'INV-' . rand(10000, 99999),
            'customer_id' => $this->customer->id,
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

        $updateData = [
            'invoice' => $sale->invoice,
            'customer_id' => $this->customer->id,
            'order_date' => Carbon::now()->format('Y-m-d'),
            'due_date' => Carbon::now()->addDays(14)->format('Y-m-d'),
            'order_discount' => 5, // Changed from 'discount_total'
            'order_discount_type' => 'percentage', // Changed from 'discount_total_type'
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

        $response->assertRedirect(route('admin.sales.view', $sale->id));
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
            'is_pos' => false,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);

        $response = $this->get(route('admin.sales.view', $sale->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales.sales-view');
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

        $response->assertRedirect(route('admin.sales.view', $sale->id));
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
                     'message' => "Successfully deleted sales order(s)",
                 ]);

        foreach ($idsToDelete as $id) {
            $this->assertDatabaseMissing('sales', ['id' => $id]);
            $this->assertDatabaseMissing('sales_items', ['sales_id' => $id]);
        }
    }

    public function test_it_can_bulk_mark_paid_sales()
    {
        $sales = SalesFactory::new()->count(3)->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'status' => 'Unpaid',
            'total' => 100, // Ensure total is positive
            'amount_received' => 0, // Ensure balance is positive
        ]);

        $idsToMarkPaid = $sales->pluck('id')->toArray();

        $response = $this->postJson(route('sales.bulk-mark-paid'), ['ids' => $idsToMarkPaid]);

        $response->assertOk()
                 ->assertJson([
                     'success' => true,
                     'message' => "Successfully marked 3 sales order(s) as paid.",
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

    public function test_get_sales_metrics_returns_json()
    {
        $expectedMetrics = [
            'totalSales' => 10,
            'totalPaid' => 5,
            'totalUnpaid' => 5,
        ];

        $mockService = \Mockery::mock(\App\Services\SalesService::class);
        $mockService->shouldReceive('getSalesMetrics')
                    ->once()
                    ->andReturn($expectedMetrics);
        $this->app->instance(\App\Services\SalesService::class, $mockService);

        $response = $this->get(route('admin.sales.metrics'));

        $response->assertStatus(200);
        $response->assertJson($expectedMetrics);
    }

    public function test_get_expiring_soon_sales_returns_json()
    {
        $expectedSales = [
            ['id' => 1, 'name' => 'Sale 1'],
            ['id' => 2, 'name' => 'Sale 2'],
        ];

        $mockService = \Mockery::mock(\App\Services\SalesService::class);
        $mockService->shouldReceive('getExpiringSales')
                    ->once()
                    ->andReturn($expectedSales);
        $this->app->instance(\App\Services\SalesService::class, $mockService);

        $response = $this->get(route('admin.sales.expiring-soon'));

        $response->assertStatus(200);
        $response->assertJson($expectedSales);
    }

    public function test_modal_views_returns_view()
    {
        $sale = SalesFactory::new()->create();
        $mockService = \Mockery::mock(\App\Services\SalesService::class);
        $mockService->shouldReceive('getSalesForModal')
                    ->once()
                    ->with($sale->id)
                    ->andReturn($sale);
        $this->app->instance(\App\Services\SalesService::class, $mockService);

        $response = $this->get(route('admin.sales.modal-view', $sale->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.layouts.modals.salesmodals-view');
        $response->assertViewHas('sales', $sale);
    }

    public function test_modal_views_handles_not_found()
    {
        $mockService = \Mockery::mock(\App\Services\SalesService::class);
        $mockService->shouldReceive('getSalesForModal')
                    ->once()
                    ->with(999)
                    ->andThrow(new \Illuminate\Database\Eloquent\ModelNotFoundException());
        $this->app->instance(\App\Services\SalesService::class, $mockService);

        $response = $this->get(route('admin.sales.modal-view', 999));

        $response->assertStatus(404);
    }

    public function test_store_sale_with_invalid_data_returns_validation_errors()
    {
        $invalidData = [
            'invoice' => 'duplicate-invoice', // unique
            'customer_id' => 999, // not exists
            'order_date' => 'not-a-date', // invalid date
            'due_date' => 'not-a-date', // invalid date
            'products' => 'not-a-json-string', // invalid json
            'discount_total' => -10, // min:0
            'discount_total_type' => 'invalid', // in:fixed,percentage
        ];

        $response = $this->post(route('admin.sales.store'), $invalidData);

        $response->assertSessionHasErrors(['customer_id', 'order_date', 'due_date', 'products', 'discount_total', 'discount_total_type']);
        $response->assertStatus(302); // Redirect back on validation error
    }

    public function test_update_sale_with_invalid_data_returns_validation_errors()
    {
        $sale = SalesFactory::new()->create();

        $invalidData = [
            'invoice' => 'duplicate-invoice', // unique
            'customer_id' => 999, // not exists
            'order_date' => 'not-a-date', // invalid date
            'due_date' => 'not-a-date', // invalid date
            'products' => 'not-a-json-string', // invalid json
            'discount_total' => -10, // min:0
            'discount_total_type' => 'invalid', // in:fixed,percentage
        ];

        $response = $this->put(route('admin.sales.update', $sale->id), $invalidData);

        $response->assertSessionHasErrors(['customer_id', 'order_date', 'due_date', 'products', 'discount_total', 'discount_total_type']);
        $response->assertStatus(302); // Redirect back on validation error
    }
}