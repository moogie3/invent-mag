<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AccountingController extends Controller
{
    /**
     * Display the Chart of Accounts.
     */
    public function chartOfAccounts()
    {
        $accounts = Account::orderBy('code')->get()->groupBy('type');
        return view('admin.accounting.chart-of-accounts', compact('accounts'));
    }

    /**
     * Display the General Journal.
     */
    public function journal(Request $request)
    {
        $entries = JournalEntry::with('transactions.account')
            ->latest('date')
            ->latest('id')
            ->paginate(20);

        return view('admin.accounting.journal', compact('entries'));
    }

    /**
     * Display the General Ledger for a specific account.
     */
    public function generalLedger(Request $request)
    {
        $accounts = Account::orderBy('name')->get();
        $selectedAccountId = $request->input('account_id');
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $transactions = collect();
        $openingBalance = 0;
        $closingBalance = 0;
        $selectedAccount = null;

        if ($selectedAccountId) {
            $selectedAccount = Account::findOrFail($selectedAccountId);

            // Calculate Opening Balance
            $openingBalance = Transaction::where('account_id', $selectedAccountId)
                ->whereHas('journalEntry', function ($query) use ($startDate) {
                    $query->where('date', '<', $startDate);
                })
                ->get()
                ->sum(function ($transaction) {
                    return $transaction->type === 'debit' ? $transaction->amount : -$transaction->amount;
                });

            // Get Transactions for the period
            $transactions = Transaction::with('journalEntry')
                ->where('account_id', $selectedAccountId)
                ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate]);
                })
                ->latest('journal_entry_id')
                ->paginate(50);
            
            $periodChange = $transactions->sum(function ($transaction) {
                return $transaction->type === 'debit' ? $transaction->amount : -$transaction->amount;
            });

            $closingBalance = $openingBalance + $periodChange;
        }

        return view('admin.accounting.general-ledger', compact(
            'accounts',
            'selectedAccount',
            'transactions',
            'openingBalance',
            'closingBalance',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Display a Trial Balance report.
     */
    public function trialBalance(Request $request)
    {
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $accounts = Account::with(['transactions' => function ($query) use ($endDate) {
            $query->whereHas('journalEntry', function ($q) use ($endDate) {
                $q->where('date', '<=', $endDate);
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
                'code' => $account->code,
                'name' => $account->name,
                'debit' => $debitBalance,
                'credit' => $creditBalance,
            ];

            $totalDebits += $debitBalance;
            $totalCredits += $creditBalance;
        }

        return view('admin.accounting.trial-balance', compact(
            'reportData',
            'totalDebits',
            'totalCredits',
            'endDate'
        ));
    }
}