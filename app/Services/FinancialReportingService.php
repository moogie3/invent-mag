<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinancialReportingService
{
    /**
     * Generate Income Statement (Profit & Loss) report.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function generateIncomeStatement(string $startDate, string $endDate): array
    {
        $revenueData = $this->calculateRevenueAccounts($startDate, $endDate);
        $expenseData = $this->calculateExpenseAccounts($startDate, $endDate);

        return [
            'revenue_accounts' => $revenueData['accounts'],
            'total_revenue' => $revenueData['total'],
            'expense_accounts' => $expenseData['accounts'],
            'total_expenses' => $expenseData['total'],
            'net_income' => $revenueData['total'] - $expenseData['total'],
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    /**
     * Generate Balance Sheet report.
     *
     * @param string $endDate
     * @return array
     */
    public function generateBalanceSheet(string $endDate): array
    {
        $accounts = $this->getAccountsWithBalances($endDate);
        
        $assets = collect();
        $liabilities = collect();
        $equity = collect();
        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;

        foreach ($accounts as $account) {
            $balance = $this->calculateAccountBalance($account);

            if (abs($balance) <= 0.001) {
                continue;
            }

            $account->calculated_balance = $balance;

            switch ($account->type) {
                case 'asset':
                    $assets->push($account);
                    $totalAssets += $balance;
                    break;
                case 'liability':
                    $liabilities->push($account);
                    $totalLiabilities += $balance;
                    break;
                case 'equity':
                    $equity->push($account);
                    $totalEquity += $balance;
                    break;
                case 'revenue':
                    $totalEquity += $balance;
                    break;
                case 'expense':
                    $totalEquity -= $balance;
                    break;
            }
        }

        return [
            'assets' => $assets->sortBy('code'),
            'liabilities' => $liabilities->sortBy('code'),
            'equity' => $equity->sortBy('code'),
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'equation_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.001,
            'end_date' => $endDate,
        ];
    }

    /**
     * Calculate revenue accounts with their balances.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    protected function calculateRevenueAccounts(string $startDate, string $endDate): array
    {
        $accounts = Account::where('type', 'revenue')
            ->with(['transactions' => function ($query) use ($startDate, $endDate) {
                $query->select('account_id', 'type', 'amount')
                    ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('date', [$startDate, $endDate])
                          ->where('status', JournalEntry::STATUS_POSTED);
                    });
            }])
            ->get();

        $totalRevenue = 0;

        foreach ($accounts as $account) {
            $balance = $account->transactions->sum(function ($transaction) {
                return $transaction->type === 'credit' ? $transaction->amount : -$transaction->amount;
            });
            $account->calculated_balance = $balance;
            $totalRevenue += $balance;
        }

        return [
            'accounts' => $accounts,
            'total' => $totalRevenue,
        ];
    }

    /**
     * Calculate expense accounts with their balances.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    protected function calculateExpenseAccounts(string $startDate, string $endDate): array
    {
        $accounts = Account::where('type', 'expense')
            ->with(['transactions' => function ($query) use ($startDate, $endDate) {
                $query->select('account_id', 'type', 'amount')
                    ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('date', [$startDate, $endDate])
                          ->where('status', JournalEntry::STATUS_POSTED);
                    });
            }])
            ->get();

        $totalExpenses = 0;

        foreach ($accounts as $account) {
            $balance = $account->transactions->sum(function ($transaction) {
                return $transaction->type === 'debit' ? $transaction->amount : -$transaction->amount;
            });
            $account->calculated_balance = $balance;
            $totalExpenses += $balance;
        }

        return [
            'accounts' => $accounts,
            'total' => $totalExpenses,
        ];
    }

    /**
     * Get all accounts with their transaction balances up to a date.
     *
     * @param string $endDate
     * @return Collection
     */
    protected function getAccountsWithBalances(string $endDate): Collection
    {
        return Account::with(['transactions' => function ($query) use ($endDate) {
            $query->select('account_id', 'type', 'amount')
                ->whereHas('journalEntry', function ($q) use ($endDate) {
                    $q->where('date', '<=', $endDate)
                      ->where('status', JournalEntry::STATUS_POSTED);
                });
        }])->get();
    }

    /**
     * Calculate the balance for a single account.
     *
     * @param Account $account
     * @return float
     */
    protected function calculateAccountBalance(Account $account): float
    {
        $debits = $account->transactions->sum(fn($t) => $t->type === 'debit' ? $t->amount : 0);
        $credits = $account->transactions->sum(fn($t) => $t->type === 'credit' ? $t->amount : 0);

        if (in_array($account->type, ['asset', 'expense'])) {
            return $debits - $credits;
        }

        return $credits - $debits;
    }
}
