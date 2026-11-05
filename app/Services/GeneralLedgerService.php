<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GeneralLedgerService
{
    /**
     * Get general ledger data for an account.
     */
    public function getGeneralLedger(int $accountId, string $startDate, string $endDate, int $perPage = 50): array
    {
        $account = Account::findOrFail($accountId);

        $openingBalance = $this->calculateOpeningBalance($accountId, $startDate);

        $transactionsQuery = $this->getTransactionsQuery($accountId, $startDate, $endDate);
        $transactions = $transactionsQuery->paginate($perPage);

        $periodChange = $this->calculatePeriodChange($transactions->getCollection());
        $closingBalance = $openingBalance + $periodChange;

        return [
            'account' => $account,
            'transactions' => $transactions,
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    /**
     * Get general ledger data for export (all transactions, no pagination).
     */
    public function getGeneralLedgerForExport(int $accountId, string $startDate, string $endDate): array
    {
        $account = Account::findOrFail($accountId);

        $openingBalance = $this->calculateOpeningBalance($accountId, $startDate);

        $transactions = $this->getTransactionsQuery($accountId, $startDate, $endDate)->get();

        $closingBalance = $openingBalance + $this->calculatePeriodChange($transactions);

        return [
            'account' => $account,
            'transactions' => $transactions,
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    /**
     * Calculate opening balance for an account before a given date.
     */
    public function calculateOpeningBalance(int $accountId, string $startDate): float
    {
        $account = Account::find($accountId);

        // Get the opening balance from the account record
        $openingBalanceFromAccount = $account ? floatval($account->opening_balance) : 0;

        // Use DB query aggregate to sum directly instead of hydrating all historical models into RAM
        $tenantId = app('currentTenant')->id ?? null;

        $aggQuery = DB::table('transactions')
            ->join('journal_entries', 'transactions.journal_entry_id', '=', 'journal_entries.id')
            ->where('transactions.account_id', $accountId)
            ->where('journal_entries.date', '<', $startDate);

        if ($tenantId) {
            $aggQuery->where('transactions.tenant_id', $tenantId)
                ->where('journal_entries.tenant_id', $tenantId);
        }

        $aggregates = $aggQuery->selectRaw("
            SUM(CASE WHEN transactions.type = 'debit' THEN transactions.amount ELSE 0 END) as total_debit,
            SUM(CASE WHEN transactions.type = 'credit' THEN transactions.amount ELSE 0 END) as total_credit
        ")->first();

        $transactionsBalance = ($aggregates->total_debit ?? 0) - ($aggregates->total_credit ?? 0);

        // The Opening Balance page writes the same figure twice: into
        // Account.opening_balance AND into an opening journal entry.
        // Trial Balance sums only the journal, so adding the denormalized
        // field on top of the journal sum here counted it twice — the GL
        // reported 2,000 where the Trial Balance reported 1,000 for the
        // same opening balance. (AccountSeeder never sets the field, which
        // is why the seeded path was always correct.)
        //
        // The check is deliberately date-independent: when the account has
        // an opening entry at all, the journal is the source of truth and
        // the field must not be added — otherwise a start date BEFORE the
        // opening date would still double-count once the period reaches it.
        $hasOpeningEntry = DB::table('transactions')
            ->join('journal_entries', 'transactions.journal_entry_id', '=', 'journal_entries.id')
            ->where('transactions.account_id', $accountId)
            ->where('journal_entries.entry_type', JournalEntry::ENTRY_TYPE_OPENING)
            ->when($tenantId, function ($query) use ($tenantId) {
                $query->where('transactions.tenant_id', $tenantId)
                    ->where('journal_entries.tenant_id', $tenantId);
            })
            ->exists();

        if ($hasOpeningEntry) {
            $openingBalanceFromAccount = 0;
        }

        // Return the opening balance from account + transactions before start date
        return $openingBalanceFromAccount + $transactionsBalance;
    }

    /**
     * Calculate period change from transactions.
     */
    public function calculatePeriodChange(Collection $transactions): float
    {
        return $transactions->sum(function ($transaction) {
            return $transaction->type === 'debit' ? $transaction->amount : -$transaction->amount;
        });
    }

    /**
     * Get transactions query for an account within a date range.
     *
     * @return Builder
     */
    protected function getTransactionsQuery(int $accountId, string $startDate, string $endDate)
    {
        return Transaction::with(['journalEntry' => fn ($q) => $q->withReconciledStatus()])
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            })
            ->latest('journal_entry_id');
    }

    /**
     * Get journal entries for a date range.
     */
    public function getJournalEntries(string $startDate, string $endDate, int $perPage = 20): array
    {
        $query = JournalEntry::with('transactions.account')
            ->withReconciledStatus()
            ->latest('date')
            ->latest('id');

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        $entries = $query->paginate($perPage)->withQueryString();

        return [
            'entries' => $entries,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    /**
     * Get journal entries for export.
     */
    public function getJournalEntriesForExport(string $startDate, string $endDate): Collection
    {
        $query = JournalEntry::with('transactions.account')
            ->latest('date')
            ->latest('id');

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        return $query->get();
    }
}
