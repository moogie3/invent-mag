<?php

namespace Tests\Unit\Services;

use App\Models\Purchase;
use App\Models\Sales;
use App\Services\TransactionService;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;

class TransactionServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected TransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->transactionService = new TransactionService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(TransactionService::class, $this->transactionService);
    }

    #[Test]
    public function it_can_get_all_transactions()
    {
        Sales::factory()->count(3)->create();
        Purchase::factory()->count(2)->create();

        $filters = ['date_range' => null, 'start_date' => null, 'end_date' => null, 'status' => null, 'type' => null, 'search' => null, 'sort' => 'date', 'direction' => 'desc'];
        $transactions = $this->transactionService->getTransactions($filters);

        $this->assertCount(5, $transactions);
    }

    #[Test]
    public function it_can_filter_transactions_by_date_range()
    {
        Sales::factory()->create(['order_date' => now()->subMonth()]);
        Purchase::factory()->create(['order_date' => now()]);

        $filters = ['date_range' => 'this_month', 'start_date' => null, 'end_date' => null, 'status' => null, 'type' => null, 'search' => null, 'sort' => 'date', 'direction' => 'desc'];
        $transactions = $this->transactionService->getTransactions($filters);

        $this->assertCount(1, $transactions);
    }

    #[Test]
    public function it_can_filter_transactions_by_status()
    {
        Sales::factory()->create(['status' => 'Paid']);
        Purchase::factory()->create(['status' => 'Unpaid']);

        $filters = ['date_range' => null, 'start_date' => null, 'end_date' => null, 'status' => 'Paid', 'type' => null, 'search' => null, 'sort' => 'date', 'direction' => 'desc'];
        $transactions = $this->transactionService->getTransactions($filters);

        $this->assertCount(1, $transactions);
        $this->assertEquals('Paid', $transactions->first()->status);
    }

    #[Test]
    public function it_can_filter_transactions_by_type()
    {
        Sales::factory()->create();
        Purchase::factory()->create();

        $filters = ['date_range' => null, 'start_date' => null, 'end_date' => null, 'status' => null, 'type' => 'sale', 'search' => null, 'sort' => 'date', 'direction' => 'desc'];
        $transactions = $this->transactionService->getTransactions($filters);

        $this->assertCount(1, $transactions);
        $this->assertEquals('sale', $transactions->first()->type);
    }

    #[Test]
    public function it_can_get_transactions_summary()
    {
        Sales::factory()->create(['total' => 100, 'status' => 'Paid']);
        Purchase::factory()->create(['total' => 50, 'status' => 'Unpaid']);

        $filters = ['date_range' => null, 'start_date' => null, 'end_date' => null, 'status' => null, 'type' => null, 'search' => null];
        $summary = $this->transactionService->getTransactionsSummary($filters);

        $this->assertEquals(2, $summary['total_count']);
        $this->assertEquals(150, $summary['total_amount']);
        $this->assertEquals(1, $summary['paid_count']);
        $this->assertEquals(100, $summary['paid_amount']);
        $this->assertEquals(1, $summary['unpaid_count']);
        $this->assertEquals(50, $summary['unpaid_amount']);
    }

    #[Test]
    public function it_can_mark_a_transaction_as_paid()
    {
        $sale = Sales::factory()->create(['status' => 'Unpaid']);
        $this->transactionService->markTransactionAsPaid($sale->id, 'sale');
        $this->assertEquals('Paid', $sale->fresh()->status);

        $purchase = Purchase::factory()->create(['status' => 'Unpaid']);
        $this->transactionService->markTransactionAsPaid($purchase->id, 'purchase');
        $this->assertEquals('Paid', $purchase->fresh()->status);
    }

    #[Test]
    public function it_can_bulk_mark_transactions_as_paid()
    {
        $sale = Sales::factory()->create(['status' => 'Unpaid']);
        $purchase = Purchase::factory()->create(['status' => 'Unpaid']);

        $this->transactionService->bulkMarkAsPaid([$sale->id, $purchase->id]);

        $this->assertEquals('Paid', $sale->fresh()->status);
        $this->assertEquals('Paid', $purchase->fresh()->status);
    }

    #[Test]
    public function it_can_get_transactions_for_export()
    {
        Sales::factory()->count(3)->create();
        Purchase::factory()->count(2)->create();

        $filters = ['date_range' => null, 'start_date' => null, 'end_date' => null, 'status' => null, 'type' => null, 'search' => null];
        $transactions = $this->transactionService->getTransactionsForExport($filters, null);

        $this->assertCount(5, $transactions);
    }

    #[Test]
    public function it_can_get_selected_transactions_for_export()
    {
        $sale1 = Sales::factory()->create();
        Sales::factory()->create();
        $purchase1 = Purchase::factory()->create();
        Purchase::factory()->create();

        $filters = ['date_range' => null, 'start_date' => null, 'end_date' => null, 'status' => null, 'type' => null, 'search' => null];
        $transactions = $this->transactionService->getTransactionsForExport($filters, [$sale1->id, $purchase1->id]);

        $this->assertCount(2, $transactions);
    }
}
