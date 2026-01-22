<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;

class ReportControllerTest extends TestCase
{
    use WithFaker, CreatesTenant, RefreshDatabase;

    protected $transactionServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');

        // Mock the TransactionService
        $this->transactionServiceMock = Mockery::mock(TransactionService::class);
        $this->app->instance(TransactionService::class, $this->transactionServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_adjustment_log_displays_adjustment_log_page()
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.adjustment-log'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.adjustment-log');
        $response->assertViewHas('adjustments');
    }

    public function test_recent_transactions_displays_recent_transactions_page()
    {
        $this->transactionServiceMock->shouldReceive('getTransactions')->andReturn(
            new LengthAwarePaginator(collect([]), 0, 15)
        );
        $this->transactionServiceMock->shouldReceive('getTransactionsSummary')->andReturn([
            'total_count' => 0,
            'sales_count' => 0,
            'purchases_count' => 0,
            'total_amount' => 0,
            'sales_amount' => 0,
            'paid_count' => 0,
            'paid_amount' => 0,
            'unpaid_count' => 0,
            'unpaid_amount' => 0,
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.reports.recent-transactions'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.recentts');
        $response->assertViewHas('transactions');
        $response->assertViewHas('summary');
    }

    public function test_recent_transactions_exports_to_excel()
    {
        $this->transactionServiceMock->shouldReceive('getTransactions')->andReturn(
            new LengthAwarePaginator(collect([]), 0, 15)
        );
        $this->transactionServiceMock->shouldReceive('getTransactionsSummary')->andReturn([
            'total_count' => 0,
            'sales_count' => 0,
            'purchases_count' => 0,
            'total_amount' => 0,
            'sales_amount' => 0,
            'paid_count' => 0,
            'paid_amount' => 0,
            'unpaid_count' => 0,
            'unpaid_amount' => 0,
        ]);
        $this->transactionServiceMock->shouldReceive('exportTransactions')->andReturn(response('excel'));

        $response = $this->actingAs($this->user)->get(route('admin.reports.recent-transactions', ['export' => 'excel']));

        $response->assertStatus(200);
        $this->assertEquals('excel', $response->getContent());
    }

    public function test_bulk_mark_as_paid_marks_transactions_as_paid()
    {
        $transactions = [
            ['id' => 1, 'type' => 'sale'],
            ['id' => 2, 'type' => 'purchase']
        ];
        $this->transactionServiceMock->shouldReceive('bulkMarkAsPaid')->with($transactions)->andReturn(2);

        $response = $this->actingAs($this->user)->post(route('admin.transactions.bulk-mark-paid'), ['transactions' => $transactions]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Successfully marked 2 transaction(s) as paid.',
            'updated_count' => 2,
        ]);
    }

    public function test_bulk_mark_as_paid_handles_no_transactions_selected()
    {
        $response = $this->actingAs($this->user)->post(route('admin.transactions.bulk-mark-paid'), ['transactions' => []]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'No transactions selected.',
        ]);
    }

    public function test_mark_as_paid_marks_transaction_as_paid()
    {
        $this->transactionServiceMock->shouldReceive('markTransactionAsPaid')->with(1, 'sale')->andReturn(['success' => true]);

        $response = $this->actingAs($this->user)->post(route('admin.transactions.mark-paid', 1), ['type' => 'sale']);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }
}