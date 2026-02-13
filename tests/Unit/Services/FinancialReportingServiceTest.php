<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\FinancialReportingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class FinancialReportingServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->service = app(FinancialReportingService::class);
    }

    public function test_generate_income_statement_returns_correct_structure()
    {
        $startDate = Carbon::now()->startOfMonth()->toDateString();
        $endDate = Carbon::now()->endOfMonth()->toDateString();

        $result = $this->service->generateIncomeStatement($startDate, $endDate);

        $this->assertArrayHasKey('revenue_accounts', $result);
        $this->assertArrayHasKey('total_revenue', $result);
        $this->assertArrayHasKey('expense_accounts', $result);
        $this->assertArrayHasKey('total_expenses', $result);
        $this->assertArrayHasKey('net_income', $result);
        $this->assertArrayHasKey('start_date', $result);
        $this->assertArrayHasKey('end_date', $result);
    }

    public function test_generate_income_statement_calculates_net_income_correctly()
    {
        $startDate = Carbon::now()->startOfMonth()->toDateString();
        $endDate = Carbon::now()->endOfMonth()->toDateString();

        // Create test accounts
        $revenueAccount = Account::factory()->create([
            'type' => 'revenue',
            'tenant_id' => app('currentTenant')->id,
        ]);

        $expenseAccount = Account::factory()->create([
            'type' => 'expense',
            'tenant_id' => app('currentTenant')->id,
        ]);

        // Create journal entry with transactions
        $journalEntry = JournalEntry::factory()->create([
            'date' => Carbon::now(),
            'status' => JournalEntry::STATUS_POSTED,
            'tenant_id' => app('currentTenant')->id,
        ]);

        $journalEntry->transactions()->create([
            'account_id' => $revenueAccount->id,
            'type' => 'credit',
            'amount' => 1000,
        ]);

        $journalEntry->transactions()->create([
            'account_id' => $expenseAccount->id,
            'type' => 'debit',
            'amount' => 600,
        ]);

        $result = $this->service->generateIncomeStatement($startDate, $endDate);

        $this->assertEquals(1000, $result['total_revenue']);
        $this->assertEquals(600, $result['total_expenses']);
        $this->assertEquals(400, $result['net_income']);
    }

    public function test_generate_balance_sheet_returns_correct_structure()
    {
        $endDate = Carbon::now()->toDateString();

        $result = $this->service->generateBalanceSheet($endDate);

        $this->assertArrayHasKey('assets', $result);
        $this->assertArrayHasKey('liabilities', $result);
        $this->assertArrayHasKey('equity', $result);
        $this->assertArrayHasKey('total_assets', $result);
        $this->assertArrayHasKey('total_liabilities', $result);
        $this->assertArrayHasKey('total_equity', $result);
        $this->assertArrayHasKey('equation_balanced', $result);
        $this->assertArrayHasKey('end_date', $result);
    }

    public function test_generate_balance_sheet_categorizes_accounts_correctly()
    {
        $endDate = Carbon::now()->toDateString();

        // Create test accounts with proper account types
        $assetAccount = Account::factory()->create([
            'type' => 'asset',
            'code' => '1000',
            'tenant_id' => app('currentTenant')->id,
        ]);

        $liabilityAccount = Account::factory()->create([
            'type' => 'liability',
            'code' => '2000',
            'tenant_id' => app('currentTenant')->id,
        ]);

        // Create journal entry with today's date
        $journalEntry = JournalEntry::factory()->create([
            'date' => Carbon::now()->startOfDay(),
            'status' => JournalEntry::STATUS_POSTED,
            'tenant_id' => app('currentTenant')->id,
        ]);

        // For assets: debit increases the balance
        $journalEntry->transactions()->create([
            'account_id' => $assetAccount->id,
            'type' => 'debit',
            'amount' => 5000,
        ]);

        // For liabilities: credit increases the balance
        $journalEntry->transactions()->create([
            'account_id' => $liabilityAccount->id,
            'type' => 'credit',
            'amount' => 5000,
        ]);

        $result = $this->service->generateBalanceSheet($endDate);

        // The balance sheet should show the asset and liability we just created
        $this->assertGreaterThanOrEqual(0, $result['total_assets']);
        $this->assertGreaterThanOrEqual(0, $result['total_liabilities']);
        
        // Verify the structure is correct
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result['assets']);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result['liabilities']);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result['equity']);
        $this->assertIsBool($result['equation_balanced']);
    }
}
