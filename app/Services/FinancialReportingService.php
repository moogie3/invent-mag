<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Support\Collection;

class FinancialReportingService
{
    /**
     * Generate Income Statement (Profit & Loss) report.
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
     */
    protected function calculateRevenueAccounts(string $startDate, string $endDate): array
    {
        $tenantId = app('currentTenant')->id ?? null;

        $accountsQuery = Account::where('type', 'revenue');
        if ($tenantId) {
            $accountsQuery->where('tenant_id', $tenantId);
        }

        $accounts = $accountsQuery->withSum(['transactions as total_credits' => function ($query) use ($startDate, $endDate, $tenantId) {
            $query->where('type', 'credit')
                ->whereHas('journalEntry', function ($q) use ($startDate, $endDate, $tenantId) {
                    if ($tenantId) {
                        $q->where('tenant_id', $tenantId);
                    }
                    $q->whereBetween('date', [$startDate, $endDate])
                        ->where('status', JournalEntry::STATUS_POSTED);
                });
        }], 'amount')
            ->withSum(['transactions as total_debits' => function ($query) use ($startDate, $endDate, $tenantId) {
                $query->where('type', 'debit')
                    ->whereHas('journalEntry', function ($q) use ($startDate, $endDate, $tenantId) {
                        if ($tenantId) {
                            $q->where('tenant_id', $tenantId);
                        }
                        $q->whereBetween('date', [$startDate, $endDate])
                            ->where('status', JournalEntry::STATUS_POSTED);
                    });
            }], 'amount')
            ->get();

        $totalRevenue = 0;

        foreach ($accounts as $account) {
            $credits = (float) ($account->total_credits ?? 0);
            $debits = (float) ($account->total_debits ?? 0);
            // Revenue accounts normally have credit balances
            $balance = $credits - $debits;

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
     */
    protected function calculateExpenseAccounts(string $startDate, string $endDate): array
    {
        $tenantId = app('currentTenant')->id ?? null;

        $accountsQuery = Account::where('type', 'expense');
        if ($tenantId) {
            $accountsQuery->where('tenant_id', $tenantId);
        }

        $accounts = $accountsQuery->withSum(['transactions as total_debits' => function ($query) use ($startDate, $endDate, $tenantId) {
            $query->where('type', 'debit')
                ->whereHas('journalEntry', function ($q) use ($startDate, $endDate, $tenantId) {
                    if ($tenantId) {
                        $q->where('tenant_id', $tenantId);
                    }
                    $q->whereBetween('date', [$startDate, $endDate])
                        ->where('status', JournalEntry::STATUS_POSTED);
                });
        }], 'amount')
            ->withSum(['transactions as total_credits' => function ($query) use ($startDate, $endDate, $tenantId) {
                $query->where('type', 'credit')
                    ->whereHas('journalEntry', function ($q) use ($startDate, $endDate, $tenantId) {
                        if ($tenantId) {
                            $q->where('tenant_id', $tenantId);
                        }
                        $q->whereBetween('date', [$startDate, $endDate])
                            ->where('status', JournalEntry::STATUS_POSTED);
                    });
            }], 'amount')
            ->get();

        $totalExpenses = 0;

        foreach ($accounts as $account) {
            $debits = (float) ($account->total_debits ?? 0);
            $credits = (float) ($account->total_credits ?? 0);
            // Expense accounts normally have debit balances
            $balance = $debits - $credits;

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
     */
    protected function getAccountsWithBalances(string $endDate): Collection
    {
        $tenantId = app('currentTenant')->id ?? null;

        $accountsQuery = Account::query();
        if ($tenantId) {
            $accountsQuery->where('tenant_id', $tenantId);
        }

        return $accountsQuery->withSum(['transactions as total_debits' => function ($query) use ($endDate, $tenantId) {
            $query->where('type', 'debit')
                ->whereHas('journalEntry', function ($q) use ($endDate, $tenantId) {
                    if ($tenantId) {
                        $q->where('tenant_id', $tenantId);
                    }
                    $q->where('date', '<=', $endDate)
                        ->where('status', JournalEntry::STATUS_POSTED);
                });
        }], 'amount')
            ->withSum(['transactions as total_credits' => function ($query) use ($endDate, $tenantId) {
                $query->where('type', 'credit')
                    ->whereHas('journalEntry', function ($q) use ($endDate, $tenantId) {
                        if ($tenantId) {
                            $q->where('tenant_id', $tenantId);
                        }
                        $q->where('date', '<=', $endDate)
                            ->where('status', JournalEntry::STATUS_POSTED);
                    });
            }], 'amount')
            ->get();
    }

    /**
     * Calculate the balance for a single account.
     */
    protected function calculateAccountBalance(Account $account): float
    {
        $debits = (float) ($account->total_debits ?? 0);
        $credits = (float) ($account->total_credits ?? 0);

        // Assets and Expenses have normal debit balances
        if (in_array($account->type, ['asset', 'expense'])) {
            return $debits - $credits;
        }

        // Liabilities, Equity, and Revenue have normal credit balances
        return $credits - $debits;
    }
}
