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

class AccountingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AccountingService $accountingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accountingService = new AccountingService();
        // Seed the accounts
        $this->seed(\Database\Seeders\AccountSeeder::class);
    }

    public function it_creates_a_valid_journal_entry()
    {
        $sale = Sales::factory()->create();
        $date = Carbon::now();

        $transactions = [
            ['account_name' => 'accounting.accounts.accounts_receivable.name', 'type' => 'debit', 'amount' => 100.00],
            ['account_name' => 'accounting.accounts.sales_revenue.name', 'type' => 'credit', 'amount' => 100.00],
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
            'account_id' => Account::where('name', 'accounting.accounts.accounts_receivable.name')->first()->id,
            'type' => 'debit',
            'amount' => 100.00,
        ]);

        $this->assertDatabaseHas('transactions', [
            'journal_entry_id' => $journalEntry->id,
            'account_id' => Account::where('name', 'accounting.accounts.sales_revenue.name')->first()->id,
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
        $this->expectExceptionMessage("Account 'Non-existent Account' not found.");

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