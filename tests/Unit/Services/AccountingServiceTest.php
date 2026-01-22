<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Sales;
use App\Services\AccountingService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\AccountSeeder;

class AccountingServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected AccountingService $accountingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->accountingService = new AccountingService();
        // Seed the accounts
        $this->seed(AccountSeeder::class);
    }

    #[Test]
    public function it_creates_a_valid_journal_entry()
    {
        $sale = Sales::factory()->create();
        $date = Carbon::now();

        $tenantId = app('currentTenant')->id;
        $arAccount = Account::where('code', '1130-' . $tenantId)->first();
        $revenueAccount = Account::where('code', '4100-' . $tenantId)->first();

        $transactions = [
            ['account_name' => $arAccount->name, 'type' => 'debit', 'amount' => 100.00],
            ['account_name' => $revenueAccount->name, 'type' => 'credit', 'amount' => 100.00],
        ];

        $journalEntry = $this->accountingService->createJournalEntry(
            "Test Entry",
            $date,
            $transactions,
            $sale
        );

        $this->assertInstanceOf(JournalEntry::class, $journalEntry);
        $this->assertDatabaseHas('journal_entries', [
            'id' => $journalEntry->id,
            'description' => 'Test Entry',
            'sourceable_id' => $sale->id,
            'sourceable_type' => Sales::class,
        ]);

        $this->assertDatabaseHas('transactions', [
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $arAccount->id,
            'type' => 'debit',
            'amount' => 100.00,
        ]);

        $this->assertDatabaseHas('transactions', [
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $revenueAccount->id,
            'type' => 'credit',
            'amount' => 100.00,
        ]);
    }

    #[Test]
    public function it_throws_an_exception_for_unbalanced_transactions()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Debits (100) do not equal credits (90).');

        $sale = Sales::factory()->create();
        $date = Carbon::now();

        $transactions = [
            ['account_name' => 'Accounts Receivable', 'type' => 'debit', 'amount' => 100.00],
            ['account_name' => 'Sales Revenue', 'type' => 'credit', 'amount' => 90.00], // Unbalanced
        ];

        try {
            $this->accountingService->createJournalEntry("Unbalanced Entry", $date, $transactions, $sale);
        } finally {
            $this->assertDatabaseCount('journal_entries', 0);
            $this->assertDatabaseCount('transactions', 0);
        }
    }

    #[Test]
    public function it_throws_an_exception_for_invalid_account_name()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Account 'Non-existent Account' not found for the current tenant.");

        $sale = Sales::factory()->create();
        $date = Carbon::now();

        $transactions = [
            ['account_name' => 'Non-existent Account', 'type' => 'debit', 'amount' => 100.00],
            ['account_name' => 'Sales Revenue', 'type' => 'credit', 'amount' => 100.00],
        ];

        try {
            $this->accountingService->createJournalEntry("Invalid Account Entry", $date, $transactions, $sale);
        } finally {
            $this->assertDatabaseCount('journal_entries', 0);
            $this->assertDatabaseCount('transactions', 0);
        }
    }
}
