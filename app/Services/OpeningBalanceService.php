<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\OpeningBalance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OpeningBalanceService
{
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Get all balance sheet accounts for opening balance setup.
     */
    public function getAccountsForSetup(): Collection
    {
        $openingBalanceEquityAccount = $this->getOpeningBalanceEquityAccount();
        $excludeIds = $openingBalanceEquityAccount ? [$openingBalanceEquityAccount->id] : [];

        return Account::whereIn('type', ['asset', 'liability', 'equity'])
            ->where('level', '>=', 1)
            ->where('is_active', true)
            ->whereNotIn('id', $excludeIds)
            ->orderBy('type')
            ->orderBy('code')
            ->get();
    }

    /**
     * Get existing opening balances for an account.
     */
    public function getOpeningBalanceForAccount(int $accountId): ?OpeningBalance
    {
        return OpeningBalance::where('account_id', $accountId)
            ->latest('balance_date')
            ->first();
    }

    /**
     * Get all existing opening balances.
     */
    public function getAllOpeningBalances(): Collection
    {
        return OpeningBalance::with('account')
            ->orderBy('balance_date', 'desc')
            ->get();
    }

    /**
     * Get the opening balance equity account.
     */
    public function getOpeningBalanceEquityAccount(): ?Account
    {
        return Account::where('name', 'like', '%Opening Balance Equity%')
            ->orWhere('name', 'like', '%Ekuitas Saldo Awal%')
            ->first();
    }

    /**
     * Validate opening balances before saving.
     *
     * @return array ['valid' => bool, 'totalDebit' => float, 'totalCredit' => float, 'errors' => array]
     */
    public function validateBalances(array $balances): array
    {
        $totalDebit = 0;
        $totalCredit = 0;
        $errors = [];

        foreach ($balances as $accountId => $data) {
            // Handle both 'amount' (from API) and 'debit'/'credit' (from form)
            $debitAmount = floatval($data['debit'] ?? $data['amount'] ?? 0);
            $creditAmount = floatval($data['credit'] ?? 0);
            $type = $data['type'] ?? 'debit';

            // Use the entered amount based on type
            $amount = $type === 'debit' ? $debitAmount : $creditAmount;

            if ($amount < 0) {
                $errors[] = "Account ID $accountId: Amount cannot be negative.";

                continue;
            }

            if ($type === 'debit') {
                $totalDebit += $amount;
            } else {
                $totalCredit += $amount;
            }
        }

        $isValid = count($errors) === 0 && abs($totalDebit - $totalCredit) < 0.01;

        if (! $isValid && count($errors) === 0) {
            $errors[] = 'Total Debit ('.number_format($totalDebit, 2).') must equal Total Credit ('.number_format($totalCredit, 2).')';
        }

        return [
            'valid' => $isValid,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'difference' => $totalDebit - $totalCredit,
            'errors' => $errors,
        ];
    }

    /**
     * Save opening balances and create journal entries.
     *
     * @return array ['success' => bool, 'message' => string, 'journalEntry' => JournalEntry|null]
     */
    public function saveOpeningBalances(array $balances, string $balanceDate, ?string $notes = null): array
    {
        $validation = $this->validateBalances($balances);

        if (! $validation['valid']) {
            return [
                'success' => false,
                'message' => implode(' ', $validation['errors']),
                'journalEntry' => null,
            ];
        }

        if ($validation['totalDebit'] == 0 && $validation['totalCredit'] == 0) {
            return [
                'success' => false,
                'message' => 'Please enter at least one opening balance.',
                'journalEntry' => null,
            ];
        }

        $openingBalanceEquity = $this->getOpeningBalanceEquityAccount();

        if (! $openingBalanceEquity) {
            return [
                'success' => false,
                'message' => 'Opening Balance Equity account not found. Please reset your Chart of Accounts.',
                'journalEntry' => null,
            ];
        }

        try {
            DB::transaction(function () use ($balances, $balanceDate, $notes, $validation, $openingBalanceEquity) {
                // Delete existing opening balances for the same date
                OpeningBalance::where('balance_date', $balanceDate)->delete();

                // Delete existing opening balance journal entries for the same date
                JournalEntry::where('date', $balanceDate)
                    ->where('entry_type', JournalEntry::ENTRY_TYPE_OPENING)
                    ->delete();

                $transactions = [];
                $balanceDateCarbon = Carbon::parse($balanceDate);

                // Create opening balance records and prepare transactions
                foreach ($balances as $accountId => $data) {
                    // Handle both 'amount' (from API) and 'debit'/'credit' (from form)
                    $debitAmount = floatval($data['debit'] ?? $data['amount'] ?? 0);
                    $creditAmount = floatval($data['credit'] ?? 0);
                    $type = $data['type'] ?? 'debit';

                    // Use the entered amount based on type
                    $amount = $type === 'debit' ? $debitAmount : $creditAmount;

                    if ($amount <= 0) {
                        continue;
                    }

                    $account = Account::find($accountId);
                    if (! $account) {
                        continue;
                    }

                    // Save opening balance record
                    OpeningBalance::create([
                        'tenant_id' => app('currentTenant')->id,
                        'account_id' => $accountId,
                        'debit_amount' => $type === 'debit' ? $amount : 0,
                        'credit_amount' => $type === 'credit' ? $amount : 0,
                        'balance' => $type === 'debit' ? $amount : -$amount,
                        'balance_date' => $balanceDate,
                        'notes' => $notes,
                        'created_by' => Auth::id(),
                    ]);

                    // Add to journal entry transactions
                    $transactions[] = [
                        'account_code' => $account->code,
                        'type' => $type,
                        'amount' => $amount,
                    ];

                    // Also update the account's opening_balance field
                    $account->update([
                        'opening_balance' => $type === 'debit' ? $amount : -$amount,
                        'opening_balance_date' => $balanceDate,
                    ]);
                }

                // Add balancing entry to Opening Balance Equity
                $difference = $validation['totalDebit'] - $validation['totalCredit'];
                $balancingType = $difference >= 0 ? 'credit' : 'debit';
                $balancingAmount = abs($difference);

                if ($balancingAmount > 0) {
                    $transactions[] = [
                        'account_code' => $openingBalanceEquity->code,
                        'type' => $balancingType,
                        'amount' => $balancingAmount,
                    ];
                }

                // Create the journal entry
                if (! empty($transactions)) {
                    $this->accountingService->createOpeningBalanceEntry(
                        $balanceDateCarbon,
                        $transactions
                    );
                }
            });

            return [
                'success' => true,
                'message' => 'Opening balances saved successfully.',
                'journalEntry' => null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error saving opening balances: '.$e->getMessage(),
                'journalEntry' => null,
            ];
        }
    }

    /**
     * Delete opening balances for a specific date.
     */
    public function deleteOpeningBalances(string $balanceDate): array
    {
        try {
            DB::transaction(function () use ($balanceDate) {
                // Delete opening balance journal entries
                JournalEntry::where('date', $balanceDate)
                    ->where('entry_type', JournalEntry::ENTRY_TYPE_OPENING)
                    ->delete();

                // Delete opening balance records
                OpeningBalance::where('balance_date', $balanceDate)->delete();

                // Reset account opening balance fields
                Account::where('opening_balance_date', $balanceDate)->update([
                    'opening_balance' => 0,
                    'opening_balance_date' => null,
                ]);
            });

            return [
                'success' => true,
                'message' => 'Opening balances deleted successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error deleting opening balances: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get summary of opening balances.
     */
    public function getSummary(?string $balanceDate = null): array
    {
        $query = OpeningBalance::with('account');

        if ($balanceDate) {
            $query->where('balance_date', $balanceDate);
        }

        $balances = $query->get();

        $totalDebit = $balances->where('debit_amount', '>', 0)->sum('debit_amount');
        $totalCredit = $balances->where('credit_amount', '>', 0)->sum('credit_amount');

        return [
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'difference' => $totalDebit - $totalCredit,
            'isBalanced' => abs($totalDebit - $totalCredit) < 0.01,
            'count' => $balances->count(),
        ];
    }
}
