<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;
use App\Services\GeneralLedgerService;
use Carbon\Carbon;
use Database\Seeders\AccountSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class GeneralLedgerServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected GeneralLedgerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(AccountSeeder::class);
        $this->service = new GeneralLedgerService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_can_get_general_ledger_for_account()
    {
        $tenantId = app('currentTenant')->id;
        $account = Account::where('code', 'like', '1110-'.$tenantId)->first();

        $result = $this->service->getGeneralLedger(
            $account->id,
            Carbon::now()->startOfMonth()->format('Y-m-d'),
            Carbon::now()->endOfMonth()->format('Y-m-d')
        );

        $this->assertArrayHasKey('account', $result);
        $this->assertArrayHasKey('transactions', $result);
        $this->assertArrayHasKey('opening_balance', $result);
        $this->assertArrayHasKey('closing_balance', $result);
    }

    public function test_it_can_get_general_ledger_for_export()
    {
        $tenantId = app('currentTenant')->id;
        $account = Account::where('code', 'like', '1110-'.$tenantId)->first();

        $result = $this->service->getGeneralLedgerForExport(
            $account->id,
            Carbon::now()->startOfMonth()->format('Y-m-d'),
            Carbon::now()->endOfMonth()->format('Y-m-d')
        );

        $this->assertArrayHasKey('account', $result);
        $this->assertArrayHasKey('transactions', $result);
        $this->assertTrue($result['transactions'] instanceof Collection);
    }

    public function test_it_calculates_opening_balance_correctly()
    {
        $tenantId = app('currentTenant')->id;
        $account = Account::where('code', 'like', '1110-'.$tenantId)->first();

        $openingBalance = $this->service->calculateOpeningBalance(
            $account->id,
            Carbon::now()->startOfMonth()->format('Y-m-d')
        );

        $this->assertIsNumeric($openingBalance);
    }

    public function test_it_calculates_period_change()
    {
        $tenantId = app('currentTenant')->id;
        $account = Account::where('code', 'like', '1110-'.$tenantId)->first();

        $journalEntry = JournalEntry::factory()->create([
            'date' => Carbon::now()->format('Y-m-d'),
            'description' => 'Test Entry',
        ]);

        Transaction::factory()->create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $account->id,
            'type' => 'debit',
            'amount' => 100,
        ]);

        Transaction::factory()->create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $account->id,
            'type' => 'credit',
            'amount' => 50,
        ]);

        $transactions = Transaction::where('account_id', $account->id)
            ->where('journal_entry_id', $journalEntry->id)
            ->get();
        $periodChange = $this->service->calculatePeriodChange($transactions);

        $this->assertEquals(50, $periodChange);
    }

    public function test_it_can_get_journal_entries()
    {
        $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');

        $result = $this->service->getJournalEntries($startDate, $endDate);

        $this->assertArrayHasKey('entries', $result);
        $this->assertArrayHasKey('start_date', $result);
        $this->assertArrayHasKey('end_date', $result);
    }

    public function test_it_can_get_journal_entries_for_export()
    {
        $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');

        $result = $this->service->getJournalEntriesForExport($startDate, $endDate);

        $this->assertTrue($result instanceof Collection);
    }

    public function test_opening_balance_includes_account_opening_balance()
    {
        $tenantId = app('currentTenant')->id;

        $account = Account::factory()->create([
            'tenant_id' => $tenantId,
            'opening_balance' => 500,
        ]);

        $result = $this->service->calculateOpeningBalance(
            $account->id,
            Carbon::now()->startOfMonth()->format('Y-m-d')
        );

        $this->assertEquals(500, $result);
    }

    public function test_closing_balance_equals_opening_plus_period_change()
    {
        $tenantId = app('currentTenant')->id;
        $account = Account::where('code', 'like', '1110-'.$tenantId)->first();

        $result = $this->service->getGeneralLedger(
            $account->id,
            Carbon::now()->startOfMonth()->format('Y-m-d'),
            Carbon::now()->endOfMonth()->format('Y-m-d')
        );

        $expectedClosing = $result['opening_balance'] +
            $result['transactions']->sum(function ($t) {
                return $t->type === 'debit' ? $t->amount : -$t->amount;
            });

        $this->assertEquals($expectedClosing, $result['closing_balance']);
    }
}
