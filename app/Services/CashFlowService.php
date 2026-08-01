<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Support\Collection;

class CashFlowService
{
    public function __construct(
        protected FinancialReportingService $financialReportingService
    ) {}

    public function generateCashFlowStatement(string $startDate, string $endDate): array
    {
        $tenantId = app('currentTenant')->id ?? null;

        $incomeData = $this->financialReportingService->generateIncomeStatement($startDate, $endDate);
        $netIncome = $incomeData['net_income'];

        $startAccounts = $this->getAccountsWithBalancesAt($startDate, $tenantId);
        $endAccounts = $this->getAccountsWithBalancesAt($endDate, $tenantId);

        $startMap = $startAccounts->keyBy('id');
        $endMap = $endAccounts->keyBy('id');

        $nonCashItems = $this->collectNonCashItems($endAccounts, $startDate, $endDate, $tenantId);
        $workingCapitalChanges = $this->collectWorkingCapitalChanges($endAccounts, $startMap, $endMap);
        $investingActivities = $this->collectInvestingActivities($endAccounts, $startMap, $endMap);
        $financingActivities = $this->collectFinancingActivities($endAccounts, $startMap, $endMap);

        $nonCashTotal = array_sum(array_column($nonCashItems, 'amount'));
        $workingCapitalTotal = array_sum(array_column($workingCapitalChanges, 'amount'));
        $operatingCashFlow = $netIncome + $nonCashTotal + $workingCapitalTotal;

        $investingCashFlow = array_sum(array_column($investingActivities, 'amount'));
        $financingCashFlow = array_sum(array_column($financingActivities, 'amount'));

        $netChangeInCash = $operatingCashFlow + $investingCashFlow + $financingCashFlow;

        $openingCashBalance = $this->getCashBalance($startMap);
        $closingCashBalance = $this->getCashBalance($endMap);

        return [
            'net_income' => $netIncome,
            'non_cash_items' => $nonCashItems,
            'non_cash_total' => $nonCashTotal,
            'working_capital_changes' => $workingCapitalChanges,
            'working_capital_total' => $workingCapitalTotal,
            'operating_cash_flow' => $operatingCashFlow,
            'investing_activities' => $investingActivities,
            'investing_cash_flow' => $investingCashFlow,
            'financing_activities' => $financingActivities,
            'financing_cash_flow' => $financingCashFlow,
            'net_change_in_cash' => $netChangeInCash,
            'opening_cash_balance' => $openingCashBalance,
            'closing_cash_balance' => $closingCashBalance,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    protected function getAccountsWithBalancesAt(string $date, ?int $tenantId): Collection
    {
        $query = Account::query();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query
            ->withSum(['transactions as total_debits' => function ($q) use ($date, $tenantId) {
                $q->where('type', 'debit')
                    ->whereHas('journalEntry', function ($jq) use ($date, $tenantId) {
                        if ($tenantId) {
                            $jq->where('tenant_id', $tenantId);
                        }
                        $jq->where('date', '<=', $date)
                            ->where('status', JournalEntry::STATUS_POSTED);
                    });
            }], 'amount')
            ->withSum(['transactions as total_credits' => function ($q) use ($date, $tenantId) {
                $q->where('type', 'credit')
                    ->whereHas('journalEntry', function ($jq) use ($date, $tenantId) {
                        if ($tenantId) {
                            $jq->where('tenant_id', $tenantId);
                        }
                        $jq->where('date', '<=', $date)
                            ->where('status', JournalEntry::STATUS_POSTED);
                    });
            }], 'amount')
            ->get();
    }

    protected function accountBalance(Account $account): float
    {
        $debits = (float) ($account->total_debits ?? 0);
        $credits = (float) ($account->total_credits ?? 0);

        if (in_array($account->type, ['asset', 'expense'])) {
            return $debits - $credits;
        }

        return $credits - $debits;
    }

    protected function codePrefix(string $code): int
    {
        return (int) explode('-', $code)[0];
    }

    protected function inRanges(?string $code, array $ranges): bool
    {
        if (empty($code)) {
            return false;
        }
        $prefix = $this->codePrefix($code);
        foreach ($ranges as [$lo, $hi]) {
            if ($prefix >= $lo && $prefix <= $hi) {
                return true;
            }
        }

        return false;
    }

    protected function collectNonCashItems(
        Collection $endAccounts,
        string $startDate,
        string $endDate,
        ?int $tenantId
    ): array {
        $query = Account::where('type', 'expense');
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $deprAccounts = $query
            ->withSum(['transactions as period_debits' => function ($q) use ($startDate, $endDate, $tenantId) {
                $q->where('type', 'debit')
                    ->whereHas('journalEntry', function ($jq) use ($startDate, $endDate, $tenantId) {
                        if ($tenantId) {
                            $jq->where('tenant_id', $tenantId);
                        }
                        $jq->whereBetween('date', [$startDate, $endDate])
                            ->where('status', JournalEntry::STATUS_POSTED);
                    });
            }], 'amount')
            ->withSum(['transactions as period_credits' => function ($q) use ($startDate, $endDate, $tenantId) {
                $q->where('type', 'credit')
                    ->whereHas('journalEntry', function ($jq) use ($startDate, $endDate, $tenantId) {
                        if ($tenantId) {
                            $jq->where('tenant_id', $tenantId);
                        }
                        $jq->whereBetween('date', [$startDate, $endDate])
                            ->where('status', JournalEntry::STATUS_POSTED);
                    });
            }], 'amount')
            ->get();

        $items = [];
        foreach ($deprAccounts as $account) {
            if (! $this->inRanges($account->code, [[6200, 6299]])) {
                continue;
            }
            $activity = (float) ($account->period_debits ?? 0) - (float) ($account->period_credits ?? 0);
            if (abs($activity) < 0.001) {
                continue;
            }
            $items[] = [
                'account_id' => $account->id,
                'account_name' => __($account->name),
                'account_code' => $account->code,
                'amount' => $activity,
            ];
        }

        return $items;
    }

    protected function collectWorkingCapitalChanges(
        Collection $endAccounts,
        Collection $startMap,
        Collection $endMap
    ): array {
        $items = [];

        foreach ($endAccounts as $account) {
            $code = $account->code ?? '';

            $isCash = $this->inRanges($code, [[1110, 1110], [1120, 1120]]);
            $isCurrentAsset = $this->inRanges($code, [[1100, 1199]]) && ! $isCash;
            $isCurrentLiability = $this->inRanges($code, [[2100, 2199]]);

            if (! $isCurrentAsset && ! $isCurrentLiability) {
                continue;
            }

            $endBalance = $this->accountBalance($account);
            $startAccount = $startMap->get($account->id);
            $startBalance = $startAccount ? $this->accountBalance($startAccount) : 0.0;
            $delta = $endBalance - $startBalance;

            if (abs($delta) < 0.001) {
                continue;
            }

            $cfImpact = $isCurrentAsset ? -$delta : $delta;

            $items[] = [
                'account_id' => $account->id,
                'account_name' => __($account->name),
                'account_code' => $code,
                'delta' => $delta,
                'amount' => $cfImpact,
                'type' => $isCurrentAsset ? 'asset' : 'liability',
            ];
        }

        return $items;
    }

    protected function collectInvestingActivities(
        Collection $endAccounts,
        Collection $startMap,
        Collection $endMap
    ): array {
        $items = [];

        foreach ($endAccounts as $account) {
            if (! $this->inRanges($account->code, [[1200, 1299]])) {
                continue;
            }
            if ($account->is_contra) {
                continue;
            }
            if ($account->level < 2) {
                continue;
            }

            $endBalance = $this->accountBalance($account);
            $startAccount = $startMap->get($account->id);
            $startBalance = $startAccount ? $this->accountBalance($startAccount) : 0.0;
            $delta = $endBalance - $startBalance;

            if (abs($delta) < 0.001) {
                continue;
            }

            $cfImpact = -$delta;

            $items[] = [
                'account_id' => $account->id,
                'account_name' => __($account->name),
                'account_code' => $account->code,
                'delta' => $delta,
                'amount' => $cfImpact,
            ];
        }

        return $items;
    }

    protected function collectFinancingActivities(
        Collection $endAccounts,
        Collection $startMap,
        Collection $endMap
    ): array {
        $items = [];

        foreach ($endAccounts as $account) {
            $code = $account->code ?? '';

            $isLongTermLiability = $this->inRanges($code, [[2200, 2299]]);
            $isEquity = $this->inRanges($code, [[3000, 3999]]);
            $isRetainedEarnings = $this->inRanges($code, [[3200, 3299]]);

            if (! $isLongTermLiability && ! $isEquity) {
                continue;
            }
            if ($isRetainedEarnings) {
                continue;
            }
            if ($account->level < 1) {
                continue;
            }

            $endBalance = $this->accountBalance($account);
            $startAccount = $startMap->get($account->id);
            $startBalance = $startAccount ? $this->accountBalance($startAccount) : 0.0;
            $delta = $endBalance - $startBalance;

            if (abs($delta) < 0.001) {
                continue;
            }

            $cfImpact = $delta;

            $items[] = [
                'account_id' => $account->id,
                'account_name' => __($account->name),
                'account_code' => $code,
                'delta' => $delta,
                'amount' => $cfImpact,
                'type' => $isLongTermLiability ? 'liability' : 'equity',
            ];
        }

        return $items;
    }

    protected function getCashBalance(Collection $accountMap): float
    {
        $total = 0.0;
        foreach ($accountMap as $account) {
            if ($this->inRanges($account->code, [[1110, 1120]])) {
                $total += $this->accountBalance($account);
            }
        }

        return $total;
    }
}
