<?php

namespace Tests\Feature\Api\V1;

use App\Models\Purchase;
use App\Models\Sales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\StockAdjustment;
use Spatie\Permission\Models\Role;
use Tests\Traits\CreatesTenant;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use App\Models\Account;

class ReportApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();

        // Ensure necessary permissions are created for both guards
        Permission::findOrCreate('view-reports', 'web');
        Permission::findOrCreate('view-reports', 'api');
        Permission::findOrCreate('mark-transactions-paid', 'web');
        Permission::findOrCreate('mark-transactions-paid', 'api');

        // Assign 'admin' role to the tenant user, or create it if it doesn't exist
        $role = Role::firstOrCreate(['name' => 'admin', 'tenant_id' => $this->tenant->id]);
        $this->user->assignRole($role);
        $this->user->givePermissionTo(['view-reports', 'mark-transactions-paid']);

        // Create a stock adjustment to ensure pagination returns meta/links
        StockAdjustment::factory()->create(['tenant_id' => $this->tenant->id]);

        // Configure accounting settings for the user
        $cashAccount = Account::factory()->create(['name' => 'Cash Account for Report Test', 'code' => 'R1001', 'tenant_id' => $this->tenant->id]);
        $accountsReceivableAccount = Account::factory()->create(['name' => 'Accounts Receivable for Report Test', 'code' => 'R1002', 'tenant_id' => $this->tenant->id]);
        $salesRevenueAccount = Account::factory()->create(['name' => 'Sales Revenue for Report Test', 'code' => 'R1003', 'tenant_id' => $this->tenant->id]);
        $costOfGoodsSoldAccount = Account::factory()->create(['name' => 'Cost of Goods Sold for Report Test', 'code' => 'R1004', 'tenant_id' => $this->tenant->id]);
        $inventoryAccount = Account::factory()->create(['name' => 'Inventory for Report Test', 'code' => 'R1005', 'tenant_id' => $this->tenant->id]);
        $accountsPayableAccount = Account::factory()->create(['name' => 'Accounts Payable for Report Test', 'code' => 'R1006', 'tenant_id' => $this->tenant->id]);

        $this->user->accounting_settings = [
            'cash_account_id' => $cashAccount->id,
            'accounts_receivable_account_id' => $accountsReceivableAccount->id,
            'sales_revenue_account_id' => $salesRevenueAccount->id,
            'cost_of_goods_sold_account_id' => $costOfGoodsSoldAccount->id,
            'inventory_account_id' => $inventoryAccount->id,
            'accounts_payable_account_id' => $accountsPayableAccount->id,
        ];
        $this->user->save();
    }

    public function test_adjustment_log_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson('/api/v1/reports/adjustment-log');

        $response->assertUnauthorized();
    }

    public function test_adjustment_log_returns_json_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/reports/adjustment-log');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'current_page',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'links',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);
    }

    public function test_recent_transactions_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $response = $this->getJson('/api/v1/reports/recent-transactions');

        $response->assertUnauthorized();
    }

    public function test_recent_transactions_returns_json_data()
    {
        Sales::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);
        Purchase::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/reports/recent-transactions');

        $response->assertOk()
            ->assertJsonStructure([
                'transactions' => [
                    'data' => [
                        '*' => [
                            'id',
                            'type',
                            'invoice',
                            'customer_supplier',
                            'date',
                            'amount',
                            'status',
                        ]
                    ]
                ],
                'summary'
            ]);
    }

    public function test_bulk_mark_as_paid_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $response = $this->postJson('/api/v1/reports/transactions/bulk-mark-paid');

        $response->assertUnauthorized();
    }

    public function test_bulk_mark_as_paid_marks_transactions_as_paid()
    {
        $sales = Sales::factory()->count(2)->create(['status' => 'Unpaid', 'tenant_id' => $this->tenant->id]);
        $purchases = Purchase::factory()->count(2)->create(['status' => 'Unpaid', 'tenant_id' => $this->tenant->id]);

        $transactionIds = $sales->pluck('id')->concat($purchases->pluck('id'))->toArray();

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/reports/transactions/bulk-mark-paid', [
            'transaction_ids' => $transactionIds,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true
            ]);

        foreach ($sales as $sale) {
            $this->assertDatabaseHas('sales', [
                'id' => $sale->id,
                'status' => 'Paid',
            ]);
        }

        foreach ($purchases as $purchase) {
            $this->assertDatabaseHas('po', [
                'id' => $purchase->id,
                'status' => 'Paid',
            ]);
        }
    }

    public function test_mark_as_paid_returns_unauthorized_if_user_is_not_authenticated()
    {
        Auth::guard('web')->logout();
        $sale = Sales::factory()->create(['status' => 'Unpaid', 'tenant_id' => $this->tenant->id]);

        $response = $this->postJson("/api/v1/reports/transactions/{$sale->id}/mark-paid",['type'=>'sale']);

        $response->assertUnauthorized();
    }

    public function test_mark_as_paid_marks_transaction_as_paid()
    {
        $sale = Sales::factory()->create(['status' => 'Unpaid', 'tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/v1/reports/transactions/{$sale->id}/mark-paid",['type'=>'sale']);

        $response->assertOk()
            ->assertJson([
                'success' => true
            ]);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'status' => 'Paid',
        ]);

        $purchase = Purchase::factory()->create(['status' => 'Unpaid', 'tenant_id' => $this->tenant->id]);
        $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/v1/reports/transactions/{$purchase->id}/mark-paid",['type'=>'purchase']);
        $response->assertOk()
            ->assertJson([
                'success' => true
            ]);
        $this->assertDatabaseHas('po', [
            'id' => $purchase->id,
            'status' => 'Paid',
        ]);
    }
}
