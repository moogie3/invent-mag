<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\AccountingAuditService;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ManualJournalEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;
        $tenantName = app('currentTenant')->name;

        // Get accounts for transactions
        $cashAccount = Account::where('tenant_id', $tenantId)
            ->where('code', 'like', '1110-%')
            ->first();
        $expenseAccount = Account::where('tenant_id', $tenantId)
            ->where('code', 'like', '5000-%')
            ->first();
        $revenueAccount = Account::where('tenant_id', $tenantId)
            ->where('code', 'like', '4100-%')
            ->first();
        $arAccount = Account::where('tenant_id', $tenantId)
            ->where('code', 'like', '1130-%')
            ->first();

        if (! $cashAccount || ! $expenseAccount) {
            $this->command->warn("Skipping ManualJournalEntrySeeder for tenant {$tenantName}: Required accounts not found. Please run AccountSeeder first.");

            return;
        }

        // This seeder books six entries that belong to it and nothing else.
        // They carry no sourceable and entry_type 'standard', which is what
        // distinguishes them from AccountSeeder's opening balance entries
        // (entry_type 'opening', also sourceable-less) and from the sales and
        // purchase journals (standard, but sourceable-backed). Without this a
        // second db:seed booked six more journal entries and twelve more
        // transactions on top of the originals: each entry balanced on its
        // own, so nothing looked wrong, but the general ledger had silently
        // doubled for this tenant.
        $ownDescriptions = [
            'Office Supplies Purchase',
            'Utility Expense Accrual',
            'Monthly Rent Payment',
            'Sales Revenue - Invoice #001',
            'Monthly Salary Accrual',
            'Erroneous Entry - To Be Voided',
        ];

        $alreadySeeded = JournalEntry::where('tenant_id', $tenantId)
            ->whereNull('sourceable_type')
            ->where('entry_type', JournalEntry::ENTRY_TYPE_STANDARD)
            ->whereIn('description', $ownDescriptions)
            ->exists();

        if ($alreadySeeded) {
            $this->command->info('Manual journal entries already seeded for tenant '.$tenantName.', skipping.');

            return;
        }

        $auditService = new AccountingAuditService;
        $accountingService = new AccountingService($auditService);

        // Create sample draft entries
        $this->createDraftEntries($accountingService, $cashAccount, $expenseAccount, $tenantId);

        // Create sample posted entries
        $this->createPostedEntries($accountingService, $cashAccount, $expenseAccount, $revenueAccount, $arAccount, $tenantId);

        // Create a voided entry
        $this->createVoidedEntry($accountingService, $cashAccount, $expenseAccount, $tenantId);

        $this->command->info('Manual journal entries seeded successfully!');
    }

    private function createDraftEntries($accountingService, $cashAccount, $expenseAccount, $tenantId)
    {
        // Draft Entry 1: Office supplies purchase
        $accountingService->createManualJournalEntry(
            'Office Supplies Purchase',
            Carbon::now()->subDays(5),
            [
                ['account_id' => $expenseAccount->id, 'type' => 'debit', 'amount' => 250],
                ['account_id' => $cashAccount->id, 'type' => 'credit', 'amount' => 250],
            ],
            'Purchase of stationery and office supplies',
            false // draft
        );

        // Draft Entry 2: Utility accrual
        $utilityAccount = Account::where('tenant_id', $tenantId)
            ->where('code', 'like', '6230-%')
            ->first();

        if ($utilityAccount) {
            $accountingService->createManualJournalEntry(
                'Utility Expense Accrual',
                Carbon::now()->subDays(3),
                [
                    ['account_id' => $utilityAccount->id, 'type' => 'debit', 'amount' => 500],
                    ['account_id' => $cashAccount->id, 'type' => 'credit', 'amount' => 500],
                ],
                'Monthly electricity and water bill accrual',
                false // draft
            );
        }

        $this->command->info('Created 2 draft entries');
    }

    private function createPostedEntries($accountingService, $cashAccount, $expenseAccount, $revenueAccount, $arAccount, $tenantId)
    {
        // Posted Entry 1: Rent payment
        $rentAccount = Account::where('tenant_id', $tenantId)
            ->where('code', 'like', '6220-%')
            ->first();

        if ($rentAccount) {
            $accountingService->createManualJournalEntry(
                'Monthly Rent Payment',
                Carbon::now()->subDays(10),
                [
                    ['account_id' => $rentAccount->id, 'type' => 'debit', 'amount' => 5000],
                    ['account_id' => $cashAccount->id, 'type' => 'credit', 'amount' => 5000],
                ],
                'Office space rental for February 2026',
                true // auto-post
            );
        }

        // Posted Entry 2: Sales revenue with AR
        if ($arAccount && $revenueAccount) {
            $entry = $accountingService->createManualJournalEntry(
                'Sales Revenue - Invoice #001',
                Carbon::now()->subDays(7),
                [
                    ['account_id' => $arAccount->id, 'type' => 'debit', 'amount' => 15000],
                    ['account_id' => $revenueAccount->id, 'type' => 'credit', 'amount' => 15000],
                ],
                'Product sales to customer on credit',
                true // auto-post
            );
        }

        // Posted Entry 3: Salary accrual
        $salaryAccount = Account::where('tenant_id', $tenantId)
            ->where('code', 'like', '6210-%')
            ->first();
        $salaryPayable = Account::where('tenant_id', $tenantId)
            ->where('code', 'like', '2140-%')
            ->first();

        if ($salaryAccount && $salaryPayable) {
            $accountingService->createManualJournalEntry(
                'Monthly Salary Accrual',
                Carbon::now()->subDays(2),
                [
                    ['account_id' => $salaryAccount->id, 'type' => 'debit', 'amount' => 9500],
                    ['account_id' => $salaryPayable->id, 'type' => 'credit', 'amount' => 9500],
                ],
                'Employee salaries for February 2026',
                true // auto-post
            );
        }

        $this->command->info('Created 3 posted entries');
    }

    private function createVoidedEntry($accountingService, $cashAccount, $expenseAccount, $tenantId)
    {
        // Create a posted entry first, then void it
        $entry = $accountingService->createManualJournalEntry(
            'Erroneous Entry - To Be Voided',
            Carbon::now()->subDays(1),
            [
                ['account_id' => $expenseAccount->id, 'type' => 'debit', 'amount' => 1000],
                ['account_id' => $cashAccount->id, 'type' => 'credit', 'amount' => 1000],
            ],
            'This entry will be voided as an example',
            true // auto-post
        );

        // Void entry
        $accountingService->voidJournalEntry($entry, 'Entry created in error - voiding for demonstration');

        $this->command->info('Created 1 voided entry');
    }
}
