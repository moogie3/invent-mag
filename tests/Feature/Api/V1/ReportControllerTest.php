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

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->user->assignRole($role);
        
        // Create a stock adjustment to ensure pagination returns meta/links
        StockAdjustment::factory()->create();
    }

    public function test_adjustment_log_returns_unauthorized_if_user_is_not_authenticated()
    {
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
        $response = $this->getJson('/api/v1/reports/recent-transactions');

        $response->assertUnauthorized();
    }

    public function test_recent_transactions_returns_json_data()
    {
        Sales::factory()->count(5)->create();
        Purchase::factory()->count(5)->create();

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
        $response = $this->postJson('/api/v1/reports/transactions/bulk-mark-paid');

        $response->assertUnauthorized();
    }

    public function test_bulk_mark_as_paid_marks_transactions_as_paid()
    {
        $sales = Sales::factory()->count(2)->create(['status' => 'Unpaid']);
        $purchases = Purchase::factory()->count(2)->create(['status' => 'Unpaid']);

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
        $sale = Sales::factory()->create(['status' => 'Unpaid']);

        $response = $this->postJson("/api/v1/reports/transactions/{$sale->id}/mark-paid",['type'=>'sale']);

        $response->assertUnauthorized();
    }

    public function test_mark_as_paid_marks_transaction_as_paid()
    {
        $sale = Sales::factory()->create(['status' => 'Unpaid']);

        $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/v1/reports/transactions/{$sale->id}/mark-paid",['type'=>'sale']);

        $response->assertOk()
            ->assertJson([
                'success' => true
            ]);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'status' => 'Paid',
        ]);

        $purchase = Purchase::factory()->create(['status' => 'Unpaid']);
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