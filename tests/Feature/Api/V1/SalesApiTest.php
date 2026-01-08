<?php

namespace Tests\Feature\Api\V1;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Sales;
use Spatie\Permission\Models\Role;
use Tests\Traits\CreatesTenant;
use Illuminate\Support\Facades\Auth;

class SalesApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    private $customer;
    private $product;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();

        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->user->assignRole($role);
        $this->customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        // Configure accounting settings for the user
        $cashAccount = Account::firstOrCreate(
            ['name' => 'Cash Account', 'tenant_id' => $this->tenant->id],
            ['code' => '1010', 'type' => 'asset']
        );
        $accountsReceivableAccount = Account::firstOrCreate(
            ['name' => 'Accounts Receivable', 'tenant_id' => $this->tenant->id],
            ['code' => '1020', 'type' => 'asset']
        );
        $salesRevenueAccount = Account::firstOrCreate(
            ['name' => 'Sales Revenue', 'tenant_id' => $this->tenant->id],
            ['code' => '4010', 'type' => 'revenue']
        );
        $costOfGoodsSoldAccount = Account::firstOrCreate(
            ['name' => 'Cost of Goods Sold', 'tenant_id' => $this->tenant->id],
            ['code' => '5010', 'type' => 'expense']
        );
        $inventoryAccount = Account::firstOrCreate(
            ['name' => 'Inventory', 'tenant_id' => $this->tenant->id],
            ['code' => '1030', 'type' => 'asset']
        );

        $this->user->accounting_settings = [
            'cash_account_id' => $cashAccount->id,
            'accounts_receivable_account_id' => $accountsReceivableAccount->id,
            'sales_revenue_account_id' => $salesRevenueAccount->id,
            'cost_of_goods_sold_account_id' => $costOfGoodsSoldAccount->id,
            'inventory_account_id' => $inventoryAccount->id,
        ];
        $this->user->save();

        // Create some sales for metrics and expiring sales tests
        Sales::factory()->count(2)->create([
            'status' => 'Paid',
            'total' => 200,
            'order_date' => now()->subMonths(2),
            'due_date' => now()->subMonth(),
            'customer_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);
        Sales::factory()->count(1)->create([
            'status' => 'Unpaid',
            'total' => 150,
            'order_date' => now()->subDays(5),
            'due_date' => now()->addDays(5), // Expiring soon
            'customer_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);
        Sales::factory()->count(1)->create([
            'status' => 'Partial',
            'total' => 100,
            'order_date' => now()->subDays(10),
            'due_date' => now()->addDays(15), // Expiring soon
            'customer_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_index_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson('/api/v1/sales');

        $response->assertUnauthorized();
    }

    public function test_index_returns_json_data()
    {
        Sales::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/sales');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'customer_id',
                        'invoice',
                        'order_date',
                        'total',
                        'status',
                    ]
                ]
            ]);
    }

    public function test_store_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $response = $this->postJson('/api/v1/sales', []);

        $response->assertUnauthorized();
    }

    public function test_store_creates_a_new_sale()
    {
        $saleData = [
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'order_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'), // Added required due_date
            'payment_type' => 'Cash',
            'status' => 'Unpaid',
            'invoice' => 'INV-2025-001',
            'total' => 100.00,
            'products' => json_encode([ // JSON encode the products array
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'customer_price' => 100.00, // Changed from 'price' to 'customer_price' as per SalesService
                    'discount' => 0,
                    'discount_type' => 'fixed',
                ]
            ])
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/sales', $saleData);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'invoice' => 'INV-2025-001',
                    'total' => 100.00,
                ]
            ]);

        $this->assertDatabaseHas('sales', [
            'invoice' => 'INV-2025-001',
            'total' => 100.00,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_show_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $sale = Sales::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson("/api/v1/sales/{$sale->id}");

        $response->assertUnauthorized();
    }

    public function test_show_returns_json_data()
    {
        $sale = Sales::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/sales/{$sale->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $sale->id,
                    'invoice' => $sale->invoice,
                ]
            ]);
    }

    public function test_update_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $sale = Sales::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->putJson("/api/v1/sales/{$sale->id}", ['status' => 'Paid']);

        $response->assertUnauthorized();
    }

    public function test_update_modifies_an_existing_sale()
    {
        $sale = Sales::factory()->create(['status' => 'Unpaid', 'customer_id' => $this->customer->id, 'user_id' => $this->user->id, 'tenant_id' => $this->tenant->id]);
        $newStatus = 'Paid';

        // Create a product and associate it with the sale's customer
        $product = Product::factory()->create();
        $sale->salesItems()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'customer_price' => 100.00,
            'discount' => 0,
            'discount_type' => 'fixed',
            'total' => 100.00,
        ]);


        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/v1/sales/{$sale->id}", [
            'customer_id' => $sale->customer_id,
            'user_id' => $sale->user_id,
            'order_date' => $sale->order_date->format('Y-m-d'),
            'due_date' => $sale->due_date->format('Y-m-d'),
            'payment_type' => 'Cash',
            'status' => $newStatus,
            'invoice' => $sale->invoice,
            'total' => $sale->total,
            'products' => json_encode([
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'customer_price' => 100.00,
                    'discount' => 0,
                    'discount_type' => 'fixed',
                ]
            ])
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => $newStatus
                ]
            ]);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'status' => $newStatus,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_destroy_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $sale = Sales::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->deleteJson("/api/v1/sales/{$sale->id}");

        $response->assertUnauthorized();
    }

    public function test_destroy_deletes_a_sale()
    {
        $sale = Sales::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')->deleteJson("/api/v1/sales/{$sale->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('sales', [
            'id' => $sale->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_bulk_delete_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $response = $this->postJson('/api/v1/sales/bulk-delete', ['sale_ids' => []]);

        $response->assertUnauthorized();
    }

    public function test_bulk_delete_deletes_multiple_sales()
    {
        $sales = Sales::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);
        $saleIds = $sales->pluck('id')->toArray();

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/sales/bulk-delete', [
            'ids' => $saleIds,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Successfully deleted sales order(s)'
            ]);

        foreach ($saleIds as $id) {
            $this->assertDatabaseMissing('sales', [
                'id' => $id,
                'tenant_id' => $this->tenant->id,
            ]);
        }
    }

    public function test_bulk_mark_paid_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $response = $this->putJson('/api/v1/sales/bulk-mark-paid', ['sale_ids' => []]);

        $response->assertUnauthorized();
    }

    public function test_bulk_mark_paid_marks_multiple_sales_as_paid()
    {
        $sales = Sales::factory()->count(3)->create(['status' => 'Unpaid', 'tenant_id' => $this->tenant->id]);
        $saleIds = $sales->pluck('id')->toArray();

        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/sales/bulk-mark-paid', [
            'ids' => $saleIds,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Successfully marked 3 sales order(s) as paid.',
                'updated_count' => 3
            ]);

        foreach ($saleIds as $id) {
            $this->assertDatabaseHas('sales', [
                'id' => $id,
                'status' => 'Paid',
                'tenant_id' => $this->tenant->id,
            ]);
        }
    }

    public function test_metrics_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson('/api/v1/sales/metrics');

        $response->assertUnauthorized();
    }

    public function test_metrics_returns_json_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/sales/metrics');

        $response->assertOk()
            ->assertJsonStructure([
                'totalinvoice',
                'unpaidDebt',
                'totalMonthly',
                'pendingOrders',
                'dueInvoices',
                'posTotal',
            ]);
    }

    public function test_expiring_soon_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson('/api/v1/sales/expiring-soon');

        $response->assertUnauthorized();
    }

    public function test_expiring_soon_returns_json_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/sales/expiring-soon');

        $response->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'invoice',
                    'customer' => [
                        'id',
                        'name',
                    ],
                    'due_date',
                    'total',
                ]
            ]);
    }

    public function test_add_payment_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $sale = Sales::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->postJson("/api/v1/sales/{$sale->id}/payment", ['amount' => 10]);

        $response->assertUnauthorized();
    }

    public function test_add_payment_adds_payment_to_sale()
    {
        $sale = Sales::factory()->create(['total' => 100, 'status' => 'Unpaid']);

        $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/v1/sales/{$sale->id}/payment", [
            'amount' => 50,
            'payment_date' => now()->format('Y-m-d'), // Added payment_date
            'payment_method' => 'Cash',
            'notes' => 'Partial payment',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Payment added successfully.'
            ]);

        $this->assertDatabaseHas('payments', [
            'paymentable_id' => $sale->id,
            'paymentable_type' => Sales::class,
            'amount' => 50,
            'tenant_id' => $this->tenant->id,
        ]);
        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'status' => 'Partial',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_get_customer_price_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson("/api/v1/sales/customer-price/{$customer->id}/{$product->id}");

        $response->assertUnauthorized();
    }

    public function test_get_customer_price_returns_json_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/v1/sales/customer-price/{$this->customer->id}/{$this->product->id}");

        $response->assertOk()
            ->assertJson([
                'past_price' => 0.00
            ]);
    }
}