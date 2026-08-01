<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use App\Services\BankReconciliationService;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class BankReconciliationControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');

        // Ensure the cash account passes the create-form filter (asset, level >= 2).
        $this->cashAccount = Account::where('code', '1000'.$this->tenant->id)->firstOrFail();
        $this->cashAccount->update(['level' => 2]);
    }

    private function makeStatementFile(string $content = 'Tanggal;Keterangan;Debet;Kredit'."\n".'01/06/2026;SALES DEPOSIT;;1000000'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('statement.csv', $content);
    }

    private function createCashJournalEntry(Carbon $date, float $amount = 1000000): JournalEntry
    {
        $revenueAccount = Account::where('code', '4000'.$this->tenant->id)->firstOrFail();

        return app(AccountingService::class)->createJournalEntry(
            'Cash deposit received',
            $date,
            [
                ['account_id' => $this->cashAccount->id, 'type' => 'credit', 'amount' => $amount],
                ['account_id' => $revenueAccount->id, 'type' => 'debit', 'amount' => $amount],
            ]
        );
    }

    #[Test]
    public function it_can_display_index_page()
    {
        $response = $this->get(route('admin.bank-reconciliation.index'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.bank-reconciliation.index');
        $response->assertViewHas('reconciliations');
        $response->assertViewHas('totalOpen');
        $response->assertViewHas('totalCompleted');
    }

    #[Test]
    public function it_can_display_create_form()
    {
        $response = $this->get(route('admin.bank-reconciliation.create'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.bank-reconciliation.create');
        $response->assertViewHas('accounts');
        $response->assertViewHas('banks');
        $response->assertViewHas('defaultAccountId', $this->cashAccount->id);
    }

    #[Test]
    public function it_can_import_a_statement_and_auto_match()
    {
        $entry = $this->createCashJournalEntry(Carbon::create(2026, 6, 1));

        $response = $this->post(route('admin.bank-reconciliation.store'), [
            'account_id' => $this->cashAccount->id,
            'bank' => 'bca',
            'statement' => $this->makeStatementFile(),
            'opening_balance' => 5000000,
            'closing_balance' => 6000000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bank_reconciliations', [
            'account_id' => $this->cashAccount->id,
            'status' => BankReconciliation::STATUS_OPEN,
            'statement_lines_count' => 1,
        ]);

        $reconciliation = BankReconciliation::where('account_id', $this->cashAccount->id)->firstOrFail();

        $this->assertDatabaseHas('bank_reconciliation_items', [
            'bank_reconciliation_id' => $reconciliation->id,
            'status' => BankReconciliationItem::STATUS_MATCHED,
            'journal_entry_id' => $entry->id,
        ]);

        $this->assertEquals(1000000, $reconciliation->matched_amount);
    }

    #[Test]
    public function it_validates_the_statement_file_is_required()
    {
        $response = $this->post(route('admin.bank-reconciliation.store'), [
            'account_id' => $this->cashAccount->id,
            'bank' => 'bca',
            'opening_balance' => 5000000,
            'closing_balance' => 6000000,
        ]);

        $response->assertSessionHasErrors('statement');
    }

    #[Test]
    public function it_redirects_back_with_error_on_unparseable_statement()
    {
        $response = $this->post(route('admin.bank-reconciliation.store'), [
            'account_id' => $this->cashAccount->id,
            'bank' => 'bca',
            'statement' => $this->makeStatementFile("no usable header row here\njust some text"),
            'opening_balance' => 5000000,
            'closing_balance' => 6000000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function it_can_manually_match_and_unmatch_a_line()
    {
        $entry = $this->createCashJournalEntry(Carbon::create(2026, 6, 5));
        $reconciliation = app(BankReconciliationService::class)->importStatement(
            $this->cashAccount->id,
            'bca',
            "Tanggal;Keterangan;Debet;Kredit\n01/06/2026;MANUAL TEST;;1000000",
            0,
            1000000
        );

        $item = $reconciliation->items()->firstOrFail();
        $this->assertEquals(BankReconciliationItem::STATUS_MATCHED, $item->status);

        $response = $this->post(route('admin.bank-reconciliation.unmatch', [$reconciliation->id, $item->id]));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('bank_reconciliation_items', [
            'id' => $item->id,
            'status' => BankReconciliationItem::STATUS_UNMATCHED,
            'journal_entry_id' => null,
        ]);

        $response = $this->post(route('admin.bank-reconciliation.match', [$reconciliation->id, $item->id]), [
            'journal_entry_id' => $entry->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('bank_reconciliation_items', [
            'id' => $item->id,
            'status' => BankReconciliationItem::STATUS_MANUALLY_MATCHED,
            'journal_entry_id' => $entry->id,
        ]);
    }

    #[Test]
    public function it_can_display_show_page()
    {
        $this->createCashJournalEntry(Carbon::create(2026, 6, 1));
        $reconciliation = app(BankReconciliationService::class)->importStatement(
            $this->cashAccount->id,
            'bca',
            "Tanggal;Keterangan;Debet;Kredit\n01/06/2026;SALES DEPOSIT;;1000000",
            0,
            1000000
        );

        $response = $this->get(route('admin.bank-reconciliation.show', $reconciliation->id));

        $response->assertOk();
        $response->assertViewIs('admin.reports.bank-reconciliation.show');
        $response->assertViewHas('reconciliation');
        $response->assertViewHas('candidates');
    }

    #[Test]
    public function it_can_complete_a_reconciliation()
    {
        $this->createCashJournalEntry(Carbon::create(2026, 6, 1));
        $reconciliation = app(BankReconciliationService::class)->importStatement(
            $this->cashAccount->id,
            'bca',
            "Tanggal;Keterangan;Debet;Kredit\n01/06/2026;SALES DEPOSIT;;1000000",
            0,
            1000000
        );

        $response = $this->post(route('admin.bank-reconciliation.complete', $reconciliation->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bank_reconciliations', [
            'id' => $reconciliation->id,
            'status' => BankReconciliation::STATUS_COMPLETED,
        ]);
    }

    #[Test]
    public function it_locks_matched_entries_after_completion()
    {
        $entry = $this->createCashJournalEntry(Carbon::create(2026, 6, 1));
        $reconciliation = app(BankReconciliationService::class)->importStatement(
            $this->cashAccount->id,
            'bca',
            "Tanggal;Keterangan;Debet;Kredit\n01/06/2026;SALES DEPOSIT;;1000000",
            0,
            1000000
        );

        app(BankReconciliationService::class)->complete($reconciliation);

        $this->assertTrue(app(BankReconciliationService::class)->isJournalEntryReconciled($entry));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot be reversed');

        app(AccountingService::class)->reverseJournalEntry($entry);
    }

    #[Test]
    public function it_can_delete_a_reconciliation()
    {
        $reconciliation = app(BankReconciliationService::class)->importStatement(
            $this->cashAccount->id,
            'bca',
            "Tanggal;Keterangan;Debet;Kredit\n01/06/2026;SALES DEPOSIT;;1000000",
            0,
            1000000
        );

        $response = $this->delete(route('admin.bank-reconciliation.destroy', $reconciliation->id));

        $response->assertRedirect(route('admin.bank-reconciliation.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('bank_reconciliations', ['id' => $reconciliation->id]);
    }

    #[Test]
    public function it_blocks_completion_when_variance_is_not_zero()
    {
        $this->createCashJournalEntry(Carbon::create(2026, 6, 1));
        $reconciliation = app(BankReconciliationService::class)->importStatement(
            $this->cashAccount->id,
            'bca',
            "Tanggal;Keterangan;Debet;Kredit\n01/06/2026;SALES DEPOSIT;;1000000",
            0,
            1500000
        );

        $this->assertNotEquals(0, $reconciliation->variance);

        $response = $this->post(route('admin.bank-reconciliation.complete', $reconciliation->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('bank_reconciliations', [
            'id' => $reconciliation->id,
            'status' => BankReconciliation::STATUS_OPEN,
        ]);
    }

    #[Test]
    public function complete_throws_when_variance_is_not_zero()
    {
        $this->createCashJournalEntry(Carbon::create(2026, 6, 1));
        $reconciliation = app(BankReconciliationService::class)->importStatement(
            $this->cashAccount->id,
            'bca',
            "Tanggal;Keterangan;Debet;Kredit\n01/06/2026;SALES DEPOSIT;;1000000",
            0,
            1500000
        );

        $this->expectException(\DomainException::class);
        app(BankReconciliationService::class)->complete($reconciliation);
    }

    #[Test]
    public function matched_entries_are_marked_reconciled_after_completion()
    {
        $entry = $this->createCashJournalEntry(Carbon::create(2026, 6, 1));
        $reconciliation = app(BankReconciliationService::class)->importStatement(
            $this->cashAccount->id,
            'bca',
            "Tanggal;Keterangan;Debet;Kredit\n01/06/2026;SALES DEPOSIT;;1000000",
            0,
            1000000
        );

        $this->assertFalse($entry->fresh()->isReconciled());

        app(BankReconciliationService::class)->complete($reconciliation);

        $this->assertTrue($entry->fresh()->isReconciled());
    }

    #[Test]
    public function general_journal_shows_reconciled_badge()
    {
        $this->createCashJournalEntry(Carbon::create(2026, 6, 1));
        $reconciliation = app(BankReconciliationService::class)->importStatement(
            $this->cashAccount->id,
            'bca',
            "Tanggal;Keterangan;Debet;Kredit\n01/06/2026;SALES DEPOSIT;;1000000",
            0,
            1000000
        );
        app(BankReconciliationService::class)->complete($reconciliation);

        $response = $this->get(route('admin.accounting.journal', [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]));

        $response->assertOk();
        $response->assertSee(__('messages.reconciled'));
    }
}
