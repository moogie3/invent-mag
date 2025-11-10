<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $adminUser;
    protected $transactionServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user for authentication
        $this->adminUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create the superuser role if it doesn't exist
        $superUserRole = Role::firstOrCreate(['name' => 'superuser']);
        // Assign the superuser role to the admin user
        $this->adminUser->assignRole($superUserRole);

        // Mock the TransactionService
        $this->transactionServiceMock = Mockery::mock(TransactionService::class);
        $this->app->instance(TransactionService::class, $this->transactionServiceMock);
    }

    public function test_adjustment_log_displays_adjustment_log_page()
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.reports.adjustment-log'));

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

        $response = $this->actingAs($this->adminUser)->get(route('admin.reports.recent-transactions'));

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

        $response = $this->actingAs($this->adminUser)->get(route('admin.reports.recent-transactions', ['export' => 'excel']));

        $response->assertStatus(200);
        $this->assertEquals('excel', $response->getContent());
    }

    public function test_bulk_mark_as_paid_marks_transactions_as_paid()
    {
        $transactionIds = [1, 2, 3];
        $this->transactionServiceMock->shouldReceive('bulkMarkAsPaid')->with($transactionIds)->andReturn(3);

        $response = $this->actingAs($this->adminUser)->post(route('admin.transactions.bulk-mark-paid'), ['transaction_ids' => $transactionIds]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Successfully marked 3 transaction(s) as paid.',
            'updated_count' => 3,
        ]);
    }

    public function test_bulk_mark_as_paid_handles_no_transactions_selected()
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.transactions.bulk-mark-paid'), ['transaction_ids' => []]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'No transactions selected.',
        ]);
    }

    public function test_mark_as_paid_marks_transaction_as_paid()
    {
        $this->transactionServiceMock->shouldReceive('markTransactionAsPaid')->with(1, 'sale')->andReturn(['success' => true]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.transactions.mark-paid', 1), ['type' => 'sale']);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}