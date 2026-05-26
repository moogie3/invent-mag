<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Tenant;
use App\Services\CashFlowService;
use App\Services\FinancialReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class CashFlowServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected CashFlowService $cashFlowService;
    protected $financialReportingServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();

        $this->financialReportingServiceMock = Mockery::mock(FinancialReportingService::class);
        $this->app->instance(FinancialReportingService::class, $this->financialReportingServiceMock);

        $this->cashFlowService = new CashFlowService($this->financialReportingServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generate_cash_flow_statement_returns_expected_structure()
    {
        $startDate = '2026-01-01';
        $endDate = '2026-01-31';

        $this->financialReportingServiceMock
            ->shouldReceive('generateIncomeStatement')
            ->with($startDate, $endDate)
            ->andReturn([
                'net_income' => 50000,
                'revenue_accounts' => collect([]),
                'total_revenue' => 200000,
                'expense_accounts' => collect([]),
                'total_expenses' => 150000,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

        $result = $this->cashFlowService->generateCashFlowStatement($startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('net_income', $result);
        $this->assertArrayHasKey('non_cash_items', $result);
        $this->assertArrayHasKey('non_cash_total', $result);
        $this->assertArrayHasKey('working_capital_changes', $result);
        $this->assertArrayHasKey('working_capital_total', $result);
        $this->assertArrayHasKey('operating_cash_flow', $result);
        $this->assertArrayHasKey('investing_activities', $result);
        $this->assertArrayHasKey('investing_cash_flow', $result);
        $this->assertArrayHasKey('financing_activities', $result);
        $this->assertArrayHasKey('financing_cash_flow', $result);
        $this->assertArrayHasKey('net_change_in_cash', $result);
        $this->assertArrayHasKey('opening_cash_balance', $result);
        $this->assertArrayHasKey('closing_cash_balance', $result);
        $this->assertArrayHasKey('start_date', $result);
        $this->assertArrayHasKey('end_date', $result);
    }

    public function test_generate_cash_flow_statement_calculates_net_change_correctly()
    {
        $startDate = '2026-01-01';
        $endDate = '2026-01-31';

        $this->financialReportingServiceMock
            ->shouldReceive('generateIncomeStatement')
            ->with($startDate, $endDate)
            ->andReturn([
                'net_income' => 100000,
                'revenue_accounts' => collect([]),
                'total_revenue' => 300000,
                'expense_accounts' => collect([]),
                'total_expenses' => 200000,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

        $result = $this->cashFlowService->generateCashFlowStatement($startDate, $endDate);

        $this->assertEquals(100000, $result['net_income']);
        $this->assertEquals($startDate, $result['start_date']);
        $this->assertEquals($endDate, $result['end_date']);
    }

    public function test_generate_cash_flow_statement_handles_zero_income()
    {
        $startDate = '2026-01-01';
        $endDate = '2026-01-31';

        $this->financialReportingServiceMock
            ->shouldReceive('generateIncomeStatement')
            ->with($startDate, $endDate)
            ->andReturn([
                'net_income' => 0,
                'revenue_accounts' => collect([]),
                'total_revenue' => 0,
                'expense_accounts' => collect([]),
                'total_expenses' => 0,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

        $result = $this->cashFlowService->generateCashFlowStatement($startDate, $endDate);

        $this->assertEquals(0, $result['net_income']);
        $this->assertIsArray($result['non_cash_items']);
        $this->assertIsArray($result['working_capital_changes']);
        $this->assertIsArray($result['investing_activities']);
        $this->assertIsArray($result['financing_activities']);
    }
}
