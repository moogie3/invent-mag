<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\AgingReportService;
use App\Services\FinancialReportingService;
use App\Services\ReportExporter;
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
    protected $financialReportingServiceMock;
    protected $agingReportServiceMock;
    protected $reportExporterMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');

        // Mock the services
        $this->transactionServiceMock = Mockery::mock(TransactionService::class);
        $this->financialReportingServiceMock = Mockery::mock(FinancialReportingService::class);
        $this->agingReportServiceMock = Mockery::mock(AgingReportService::class);
        $this->reportExporterMock = Mockery::mock(ReportExporter::class);

        $this->app->instance(TransactionService::class, $this->transactionServiceMock);
        $this->app->instance(FinancialReportingService::class, $this->financialReportingServiceMock);
        $this->app->instance(AgingReportService::class, $this->agingReportServiceMock);
        $this->app->instance(ReportExporter::class, $this->reportExporterMock);
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

    public function test_income_statement_displays_income_statement_page()
    {
        $this->financialReportingServiceMock->shouldReceive('generateIncomeStatement')->andReturn([
            'revenue_accounts' => collect([]),
            'total_revenue' => 0,
            'expense_accounts' => collect([]),
            'total_expenses' => 0,
            'net_income' => 0,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.reports.income-statement'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.income-statement');
        $response->assertViewHas('revenueAccounts');
        $response->assertViewHas('totalRevenue');
        $response->assertViewHas('expenseAccounts');
        $response->assertViewHas('totalExpenses');
        $response->assertViewHas('netIncome');
    }

    public function test_balance_sheet_displays_balance_sheet_page()
    {
        $this->financialReportingServiceMock->shouldReceive('generateBalanceSheet')->andReturn([
            'assets' => collect([]),
            'liabilities' => collect([]),
            'equity' => collect([]),
            'total_assets' => 0,
            'total_liabilities' => 0,
            'total_equity' => 0,
            'equation_balanced' => true,
            'end_date' => '2024-01-31',
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.reports.balance-sheet'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.balance-sheet');
        $response->assertViewHas('assets');
        $response->assertViewHas('liabilities');
        $response->assertViewHas('equity');
        $response->assertViewHas('totalAssets');
    }

    public function test_aged_receivables_displays_aged_receivables_page()
    {
        $this->agingReportServiceMock->shouldReceive('generateAgedReceivables')->andReturn([
            'current' => collect([]),
            '1-30' => collect([]),
            '31-60' => collect([]),
            '61-90' => collect([]),
            '90+' => collect([]),
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.reports.aged-receivables'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.aged-receivables');
        $response->assertViewHas('aging');
    }

    public function test_aged_payables_displays_aged_payables_page()
    {
        $this->agingReportServiceMock->shouldReceive('generateAgedPayables')->andReturn([
            'current' => collect([]),
            '1-30' => collect([]),
            '31-60' => collect([]),
            '61-90' => collect([]),
            '90+' => collect([]),
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.reports.aged-payables'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.aged-payables');
        $response->assertViewHas('aging');
    }

    public function test_export_income_statement_returns_pdf()
    {
        $this->financialReportingServiceMock->shouldReceive('generateIncomeStatement')->andReturn([
            'revenue_accounts' => collect([]),
            'total_revenue' => 0,
            'expense_accounts' => collect([]),
            'total_expenses' => 0,
            'net_income' => 0,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $this->reportExporterMock->shouldReceive('exportToPdf')->andReturn(response('pdf content', 200, [
            'Content-Type' => 'application/pdf',
        ]));

        $response = $this->actingAs($this->user)->post(route('admin.reports.income-statement.export'), [
            'export_option' => 'pdf',
        ]);

        $response->assertStatus(200);
    }

    public function test_export_income_statement_returns_csv()
    {
        $this->financialReportingServiceMock->shouldReceive('generateIncomeStatement')->andReturn([
            'revenue_accounts' => collect([]),
            'total_revenue' => 0,
            'expense_accounts' => collect([]),
            'total_expenses' => 0,
            'net_income' => 0,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $this->reportExporterMock->shouldReceive('exportIncomeStatementToCsv')->andReturn(response('csv content', 200, [
            'Content-Type' => 'text/csv',
        ]));

        $response = $this->actingAs($this->user)->post(route('admin.reports.income-statement.export'), [
            'export_option' => 'csv',
        ]);

        $response->assertStatus(200);
    }

    public function test_export_income_statement_validates_export_option()
    {
        $response = $this->actingAs($this->user)->post(route('admin.reports.income-statement.export'), [
            'export_option' => 'invalid',
        ]);

        $response->assertStatus(302); // Validation error redirects back
        $response->assertSessionHasErrors('export_option');
    }

    public function test_export_balance_sheet_returns_pdf()
    {
        $this->financialReportingServiceMock->shouldReceive('generateBalanceSheet')->andReturn([
            'assets' => collect([]),
            'liabilities' => collect([]),
            'equity' => collect([]),
            'total_assets' => 0,
            'total_liabilities' => 0,
            'total_equity' => 0,
            'equation_balanced' => true,
            'end_date' => '2024-01-31',
        ]);

        $this->reportExporterMock->shouldReceive('exportToPdf')->andReturn(response('pdf content', 200, [
            'Content-Type' => 'application/pdf',
        ]));

        $response = $this->actingAs($this->user)->post(route('admin.reports.balance-sheet.export'), [
            'export_option' => 'pdf',
        ]);

        $response->assertStatus(200);
    }

    public function test_export_aged_receivables_returns_pdf()
    {
        $this->agingReportServiceMock->shouldReceive('generateAgedReceivables')->andReturn([
            'current' => collect([]),
            '1-30' => collect([]),
            '31-60' => collect([]),
            '61-90' => collect([]),
            '90+' => collect([]),
        ]);

        $this->reportExporterMock->shouldReceive('exportToPdf')->andReturn(response('pdf content', 200, [
            'Content-Type' => 'application/pdf',
        ]));

        $response = $this->actingAs($this->user)->post(route('admin.reports.aged-receivables.export'), [
            'export_option' => 'pdf',
        ]);

        $response->assertStatus(200);
    }

    public function test_export_aged_payables_returns_csv()
    {
        $this->agingReportServiceMock->shouldReceive('generateAgedPayables')->andReturn([
            'current' => collect([]),
            '1-30' => collect([]),
            '31-60' => collect([]),
            '61-90' => collect([]),
            '90+' => collect([]),
        ]);

        $this->reportExporterMock->shouldReceive('exportAgingToCsv')->andReturn(response('csv content', 200, [
            'Content-Type' => 'text/csv',
        ]));

        $response = $this->actingAs($this->user)->post(route('admin.reports.aged-payables.export'), [
            'export_option' => 'csv',
        ]);

        $response->assertStatus(200);
    }

    public function test_export_adjustment_log_returns_pdf()
    {
        $this->reportExporterMock->shouldReceive('exportToPdf')->andReturn(response('pdf content', 200, [
            'Content-Type' => 'application/pdf',
        ]));

        $response = $this->actingAs($this->user)->post(route('admin.reports.adjustment-log.export'), [
            'export_option' => 'pdf',
        ]);

        $response->assertStatus(200);
    }

    public function test_export_recent_transactions_returns_pdf()
    {
        $this->transactionServiceMock->shouldReceive('getTransactionsForExport')->andReturn(collect([]));

        $this->reportExporterMock->shouldReceive('exportToPdf')->andReturn(response('pdf content', 200, [
            'Content-Type' => 'application/pdf',
        ]));

        $response = $this->actingAs($this->user)->post(route('admin.reports.recent-transactions.export'), [
            'export_option' => 'pdf',
        ]);

        $response->assertStatus(200);
    }

    public function test_bulk_export_transactions_returns_pdf()
    {
        $this->transactionServiceMock->shouldReceive('bulkExportTransactions')->andReturn(response('pdf content', 200, [
            'Content-Type' => 'application/pdf',
        ]));

        $response = $this->actingAs($this->user)->post(route('admin.reports.recent-transactions.bulk-export'), [
            'export_option' => 'pdf',
        ]);

        $response->assertStatus(200);
    }

    public function test_bulk_export_validates_export_option()
    {
        $response = $this->actingAs($this->user)->post(route('admin.reports.recent-transactions.bulk-export'), [
            'export_option' => 'invalid',
        ]);

        $response->assertStatus(302); // Validation error redirects back
        $response->assertSessionHasErrors('export_option');
    }
}
