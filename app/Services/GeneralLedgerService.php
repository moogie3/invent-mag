<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class GeneralLedgerService
{
    /**
     * Get general ledger data for an account.
     *
     * @param int $accountId
     * @param string $startDate
     * @param string $endDate
     * @param int $perPage
     * @return array
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
     *
     * @param int $accountId
     * @param string $startDate
     * @param string $endDate
     * @return array
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
     *
     * @param int $accountId
     * @param string $startDate
     * @return float
     */
    public function calculateOpeningBalance(int $accountId, string $startDate): float
    {
        return Transaction::where('account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($startDate) {
                $query->where('date', '<', $startDate);
            })
            ->get()
            ->sum(function ($transaction) {
                return $transaction->type === 'debit' ? $transaction->amount : -$transaction->amount;
            });
    }

    /**
     * Calculate period change from transactions.
     *
     * @param Collection $transactions
     * @return float
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
     * @param int $accountId
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getTransactionsQuery(int $accountId, string $startDate, string $endDate)
    {
        return Transaction::with('journalEntry')
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            })
            ->latest('journal_entry_id');
    }

    /**
     * Get journal entries for a date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @param int $perPage
     * @return array
     */
    public function getJournalEntries(string $startDate, string $endDate, int $perPage = 20): array
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

        $entries = $query->paginate($perPage)->withQueryString();

        return [
            'entries' => $entries,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    /**
     * Get journal entries for export.
     *
     * @param string $startDate
     * @param string $endDate
     * @return Collection
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
