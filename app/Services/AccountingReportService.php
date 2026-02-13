<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AccountingReportService
{
    /**
     * Generate trial balance report.
     *
     * @param string $endDate
     * @return array
     */
    public function generateTrialBalance(string $endDate): array
    {
        $accounts = Account::with(['transactions' => function ($query) use ($endDate) {
            $query->whereHas('journalEntry', function ($q) use ($endDate) {
                $q->where('date', '<=', $endDate)
                  ->where('status', JournalEntry::STATUS_POSTED);
            });
        }])->orderBy('code')->get();

        $reportData = [];
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($accounts as $account) {
            $debits = $account->transactions->where('type', 'debit')->sum('amount');
            $credits = $account->transactions->where('type', 'credit')->sum('amount');
            $balance = $debits - $credits;

            if ($balance == 0 && $account->transactions->isEmpty()) {
                continue;
            }

            $debitBalance = 0;
            $creditBalance = 0;

            // Asset and Expense accounts normally have a debit balance
            if (in_array($account->type, ['asset', 'expense'])) {
                if ($balance > 0) {
                    $debitBalance = $balance;
                } else {
                    $creditBalance = -$balance;
                }
            }
            // Liability, Equity, and Revenue accounts normally have a credit balance
            else {
                if ($balance < 0) {
                    $creditBalance = -$balance;
                } else {
                    $debitBalance = $balance;
                }
            }

            $reportData[] = [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'debit' => $debitBalance,
                'credit' => $creditBalance,
            ];

            $totalDebits += $debitBalance;
            $totalCredits += $creditBalance;
        }

        return [
            'report_data' => $reportData,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'is_balanced' => round($totalDebits, 2) === round($totalCredits, 2),
            'end_date' => $endDate,
        ];
    }

    /**
     * Get accounts for chart of accounts.
     *
     * @return Collection
     */
    public function getChartOfAccounts(): Collection
    {
        return Account::with('children')
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get();
    }

    /**
     * Flatten account hierarchy for export.
     *
     * @param Collection $accounts
     * @param int $level
     * @return array
     */
    public function flattenAccountHierarchy(Collection $accounts, int $level = 0): array
    {
        $flatList = [];

        foreach ($accounts as $account) {
            $flatList[] = [
                'code' => $account->code,
                'name' => str_repeat('-', $level) . ' ' . $account->name,
                'type' => $account->type,
                'level' => $account->level,
            ];

            if ($account->children->isNotEmpty()) {
                $flatList = array_merge(
                    $flatList,
                    $this->flattenAccountHierarchy($account->children, $level + 1)
                );
            }
        }

        return $flatList;
    }
}
