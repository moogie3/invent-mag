<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\AccountingReportService;
use App\Services\GeneralLedgerService;
use App\Services\OpeningBalanceService;
use Carbon\Carbon;
use Database\Seeders\AccountSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * The Opening Balance page (new-tenant onboarding) writes each figure twice:
 * into Account.opening_balance AND into an opening journal entry.
 *
 * Trial Balance sums only the journal. GeneralLedgerService used to add the
 * field on top of the journal sum, so the General Ledger reported double the
 * real opening balance while the Trial Balance reported it once — the two
 * core statements disagreed on day one of a new tenant's books.
 *
 * AccountSeeder never sets the field, which is why the seeded path was
 * always correct and only the page path was wrong.
 */
class OpeningBalanceGlTbConsistencyTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(AccountSeeder::class);
    }

    /** Save an opening balance through the real page path (the service). */
    private function saveOpening(int $accountId, int $equityId, float $amount, string $date): void
    {
        $result = app(OpeningBalanceService::class)->saveOpeningBalances(
            [
                $accountId => ['type' => 'debit', 'debit' => $amount, 'credit' => 0],
                $equityId => ['type' => 'credit', 'debit' => 0, 'credit' => $amount],
            ],
            $date,
            'test'
        );

        $this->assertTrue($result['success'], 'opening balance save failed: '.$result['message']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function general_ledger_and_trial_balance_agree_after_saving_an_opening_balance(): void
    {
        $tenantId = app('currentTenant')->id;
        $asset = Account::where('type', 'asset')->where('code', 'like', '11%')->first();
        $equity = Account::where('name', 'like', '%Opening Balance Equity%')->first()
            ?? Account::where('type', 'equity')->first();
        $this->assertNotNull($asset);
        $this->assertNotNull($equity);

        $this->saveOpening($asset->id, $equity->id, 1000, '2026-01-01');

        // The page must not leave the books in a state where the two
        // statements can diverge — both representations are written, but
        // only one may be counted.
        $asset->refresh();
        $this->assertEquals(1000, (float) $asset->opening_balance);

        $gl = new GeneralLedgerService;
        $glOpening = $gl->calculateOpeningBalance($asset->id, '2026-02-01');

        $tb = app(AccountingReportService::class)->generateTrialBalance('2026-02-01');
        $tbRow = collect($tb['report_data'])->firstWhere('code', $asset->code);
        $this->assertNotNull($tbRow, 'asset missing from trial balance');
        $tbBalance = $tbRow['debit'] - $tbRow['credit'];

        $this->assertEqualsWithDelta(
            $tbBalance,
            $glOpening,
            0.01,
            "General Ledger ({$glOpening}) and Trial Balance ({$tbBalance}) disagree on the opening balance"
        );
        $this->assertEqualsWithDelta(1000, $glOpening, 0.01, 'opening balance counted twice');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function opening_balance_before_its_own_date_is_zero_not_doubled(): void
    {
        $tenantId = app('currentTenant')->id;
        $asset = Account::where('type', 'asset')->where('code', 'like', '11%')->first();
        $equity = Account::where('name', 'like', '%Opening Balance Equity%')->first()
            ?? Account::where('type', 'equity')->first();

        $this->saveOpening($asset->id, $equity->id, 1000, '2026-01-01');

        $gl = new GeneralLedgerService;

        // Before the opening date the books are empty — the field must not
        // leak backwards, or the period would double-count on reaching it.
        $this->assertEqualsWithDelta(
            0.0,
            $gl->calculateOpeningBalance($asset->id, '2025-12-01'),
            0.01,
            'balance must be 0 before the opening date'
        );

        // calculateOpeningBalance() sums transactions dated STRICTLY BEFORE the
        // start date, so an entry dated 2026-01-01 is in-period (not "before")
        // for a period starting 2026-01-01: opening 0, the entry appears as a
        // transaction, closing 1000. For a period starting 2026-02-01 the same
        // entry is already behind us: opening 1000, no in-period movement.
        //
        // Either way the closing balance must be 1000 and must equal the Trial
        // Balance — that invariant, not the opening figure alone, is what
        // proves nothing is counted twice or lost.
        foreach (['2025-12-01', '2026-01-01', '2026-02-01'] as $start) {
            $ledger = $gl->getGeneralLedger($asset->id, $start, '2026-12-31');

            $this->assertEqualsWithDelta(
                1000,
                $ledger['closing_balance'],
                0.01,
                "closing balance for period starting {$start} must be 1000"
            );
        }

        // The opening figure itself is 0 on the opening date and 1000 after —
        // never 2000 (the pre-fix double count).
        $this->assertEqualsWithDelta(
            0.0,
            $gl->calculateOpeningBalance($asset->id, '2026-01-01'),
            0.01,
            'entry dated the start date belongs to the period, not the opening balance'
        );
        $this->assertEqualsWithDelta(
            1000,
            $gl->calculateOpeningBalance($asset->id, '2026-02-01'),
            0.01,
            'opening balance after the entry date must be 1000, not 2000'
        );

        // And the closing balance must agree with the Trial Balance.
        $tb = app(AccountingReportService::class)->generateTrialBalance('2026-02-01');
        $tbRow = collect($tb['report_data'])->firstWhere('code', $asset->code);
        $this->assertNotNull($tbRow);
        $this->assertEqualsWithDelta(
            $tbRow['debit'] - $tbRow['credit'],
            $gl->calculateOpeningBalance($asset->id, '2026-02-01'),
            0.01,
            'GL opening balance and Trial Balance must agree'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function opening_entry_is_posted_and_reversible_by_the_page(): void
    {
        $tenantId = app('currentTenant')->id;
        $asset = Account::where('type', 'asset')->where('code', 'like', '11%')->first();
        $equity = Account::where('name', 'like', '%Opening Balance Equity%')->first()
            ?? Account::where('type', 'equity')->first();

        $this->saveOpening($asset->id, $equity->id, 1000, '2026-01-01');

        $this->assertSame(
            1,
            JournalEntry::where('entry_type', JournalEntry::ENTRY_TYPE_OPENING)
                ->whereDate('date', '2026-01-01')
                ->count(),
            'opening balance must post a journal entry so Trial Balance can see it'
        );

        $gl = new GeneralLedgerService;
        $this->assertEqualsWithDelta(1000, $gl->calculateOpeningBalance($asset->id, '2026-02-01'), 0.01);

        // Deleting via the page removes the entry AND the field together, so
        // the GL must return to zero rather than keep a stale field value.
        $deleted = app(OpeningBalanceService::class)->deleteOpeningBalances('2026-01-01');
        $this->assertTrue($deleted['success'], $deleted['message']);

        $asset->refresh();
        $this->assertEqualsWithDelta(0, (float) $asset->opening_balance, 0.01);
        $this->assertEqualsWithDelta(
            0,
            $gl->calculateOpeningBalance($asset->id, '2026-02-01'),
            0.01,
            'GL must drop to 0 after the opening balance is deleted'
        );
    }

    /**
     * Re-saving on the same date must replace, not stack: the save path
     * pre-deletes by date, and if that match fails (date-cast column compared
     * against a bare 'Y-m-d' string) a second save silently posts a second
     * opening entry — double-counting in the Trial Balance.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function re_saving_on_the_same_date_replaces_the_opening_entry(): void
    {
        $asset = Account::where('type', 'asset')->where('code', 'like', '11%')->first();
        $equity = Account::where('name', 'like', '%Opening Balance Equity%')->first()
            ?? Account::where('type', 'equity')->first();

        $this->saveOpening($asset->id, $equity->id, 1000, '2026-01-01');
        $this->saveOpening($asset->id, $equity->id, 1500, '2026-01-01');

        $this->assertSame(
            1,
            JournalEntry::where('entry_type', JournalEntry::ENTRY_TYPE_OPENING)
                ->whereDate('date', '2026-01-01')
                ->count(),
            'a second save on the same date must not post a second opening entry'
        );

        $gl = new GeneralLedgerService;
        $this->assertEqualsWithDelta(
            1500,
            $gl->calculateOpeningBalance($asset->id, '2026-02-01'),
            0.01,
            're-save must leave the latest figure, not the sum of both saves'
        );
    }

    /**
     * The setup page prefills from these queries, so a date match that misses
     * leaves every input blank after a reload even though the data was saved.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function saved_balances_are_found_again_by_date_for_the_page_prefill(): void
    {
        $asset = Account::where('type', 'asset')->where('code', 'like', '11%')->first();
        $equity = Account::where('name', 'like', '%Opening Balance Equity%')->first()
            ?? Account::where('type', 'equity')->first();

        $this->saveOpening($asset->id, $equity->id, 1000, '2026-01-01');

        $service = app(OpeningBalanceService::class);

        // Two rows are expected: one for the asset, one for the equity.
        $this->assertSame(
            2,
            $service->getAllOpeningBalances('2026-01-01')->count(),
            'the setup form must find the saved balances for its own date'
        );
        $this->assertSame(
            0,
            $service->getAllOpeningBalances('2026-02-01')->count(),
            'a different date must not pick up this entry'
        );
        $this->assertSame(
            2,
            $service->getSummary('2026-01-01')['count'],
            'the summary card must count the saved balances for its own date'
        );
        $this->assertEqualsWithDelta(
            1000,
            $service->getSummary('2026-01-01')['totalDebit'],
            0.01,
            'the summary card must total the saved debit for its own date'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function account_field_without_a_journal_entry_is_still_honoured(): void
    {        // Design intent (see GeneralLedgerServiceTest): a bare
        // Account.opening_balance with no opening entry is a legitimate
        // source and must still surface — the fix only suppresses the field
        // when the journal already carries the figure.
        $account = Account::factory()->create([
            'tenant_id' => app('currentTenant')->id,
            'opening_balance' => 500,
        ]);

        $gl = new GeneralLedgerService;
        $this->assertEqualsWithDelta(
            500,
            $gl->calculateOpeningBalance($account->id, Carbon::now()->startOfMonth()->format('Y-m-d')),
            0.01
        );
    }
}
