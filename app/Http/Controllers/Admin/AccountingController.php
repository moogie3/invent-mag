<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingController extends Controller
{
    public function accounting()
    {
        $accounts = Account::all()->groupBy('type');
        $user = Auth::user();
        $settings = $user->accounting_settings ?? [];
        $activeCoaTemplate = $settings['active_coa_template'] ?? null;

        return view('admin.settings.accounting', compact('accounts', 'settings', 'activeCoaTemplate'));
    }

    public function updateAccounting(Request $request)
    {
        $validatedData = $request->validate([
            'sales_revenue_account_id' => 'required|exists:accounts,id',
            'accounts_receivable_account_id' => 'required|exists:accounts,id',
            'cost_of_goods_sold_account_id' => 'required|exists:accounts,id',
            'inventory_account_id' => 'required|exists:accounts,id',
            'accounts_payable_account_id' => 'required|exists:accounts,id',
            'cash_account_id' => 'required|exists:accounts,id',
        ]);

        $user = Auth::user();
        $user->accounting_settings = $validatedData;
        $user->save();

        return redirect()->route('admin.setting.accounting')->with('success', 'Accounting settings updated successfully.');
    }

    public function applyCoaTemplate(Request $request)
    {
        set_time_limit(300); // Set max execution time to 5 minutes for this operation
        Log::debug('applyCoaTemplate method called.');

        $request->validate([
            'template' => 'required|string',
        ]);

        $templateName = $request->input('template');
        Log::debug('Template name: ' . $templateName);

        $templatePath = database_path("data/coa_templates/{$templateName}");
        Log::debug('Template path: ' . $templatePath);

        if (!file_exists($templatePath)) {
            Log::error('Template file not found at: ' . $templatePath);
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Template file not found.'], 404);
            }
            return redirect()->back()->with('error', 'Template not found.');
        }

        try {
            $templateJson = file_get_contents($templatePath);
            $template = json_decode($templateJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Error decoding template JSON: ' . json_last_error_msg());
            }
        } catch (\Exception $e) {
            Log::error('Failed to read or decode COA template: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error reading template file.'], 500);
            }
            return redirect()->back()->with('error', 'Error reading template file.');
        }

        try {
            DB::beginTransaction();
            Log::debug('Explicit transaction started. DB_CONNECTION: ' . config('database.default') . ', Transaction Level: ' . DB::transactionLevel());

            try {
                // Temporarily disable foreign key checks to allow deletion and truncation
                if (DB::connection()->getDriverName() === 'sqlite') {
                    DB::statement('PRAGMA foreign_keys = OFF;');
                } else {
                    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                }
                Log::debug('Foreign key checks disabled. Transaction Level: ' . DB::transactionLevel());
            } catch (\Exception $e) {
                Log::error('Failed to disable foreign key checks: ' . $e->getMessage());
                throw $e;
            }

            try {
                // Delete all related transactions and journal entries first
                Transaction::query()->delete();
                Log::debug('All transactions deleted.');
                JournalEntry::query()->delete();
                Log::debug('All journal entries deleted.');
                
                Account::query()->delete();
                Log::debug('Accounts table truncated.');
            } catch (\Exception $e) {
                Log::error('Failed to delete/truncate accounting data: ' . $e->getMessage());
                throw $e;
            }

            try {
                // Re-enable foreign key checks
                if (DB::connection()->getDriverName() === 'sqlite') {
                    DB::statement('PRAGMA foreign_keys = ON;');
                } else {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                }
                Log::debug('Foreign key checks re-enabled. Transaction Level: ' . DB::transactionLevel());
            } catch (\Exception $e) {
                Log::error('Failed to re-enable foreign key checks: ' . $e->getMessage());
                throw $e;
            }

            try {
                $this->createAccountsFromTemplate($template);
            } catch (\Exception $e) {
                Log::error('Error creating accounts from template: ' . $e->getMessage());
                throw $e; // Re-throw to ensure transaction rollback
            }

            // Save the applied template name to user settings
            $user = Auth::user();
            $userSettings = $user->accounting_settings ?? [];
            $userSettings['active_coa_template'] = $templateName;
            $user->accounting_settings = $userSettings;
            $user->save();

            Log::debug('Attempting to commit transaction. Current Transaction Level: ' . DB::transactionLevel());
            Log::debug('PDO inTransaction status: ' . (DB::connection()->getPdo()->inTransaction() ? 'true' : 'false'));
            DB::commit(); // This is where the transaction should be committed
            Log::debug('Explicit transaction committed.');

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Chart of Accounts template applied successfully.']);
            }
            return redirect()->route('admin.setting.accounting')->with('success', 'Chart of Accounts template applied successfully.');
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Failed to apply COA template: ' . $e->getMessage() . ' Stack: ' . $e->getTraceAsString());
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while applying the template: ' . $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }
            return redirect()->back()->with('error', 'An error occurred while applying the template: ' . $e->getMessage());
        }
    }

    private function createAccountsFromTemplate(array $accounts, $parentId = null, $level = 0)
    {
        Log::debug('createAccountsFromTemplate called. Current Transaction Level: ' . DB::transactionLevel());
        foreach ($accounts as $accountData) {
            Log::debug('Before Account::create. Current Transaction Level: ' . DB::transactionLevel());
            try {
                Log::debug('Attempting to create account: ' . json_encode($accountData));
                $account = Account::create([
                    'name' => $accountData['name'],
                    'code' => $accountData['code'],
                    'type' => $accountData['type'],
                    'parent_id' => $parentId,
                    'level' => $level,
                ]);
                Log::debug('Account created successfully. Current Transaction Level: ' . DB::transactionLevel());
            } catch (\Exception $e) {
                Log::error('Error creating account: ' . $e->getMessage() . ' for data: ' . json_encode($accountData) . '. Current Transaction Level: ' . DB::transactionLevel());
                throw $e; // Re-throw to ensure transaction rollback
            }

            if (!empty($accountData['children'])) {
                $this->createAccountsFromTemplate($accountData['children'], $account->id, $level + 1);
            }
        }
    }

    /**
     * Display the Chart of Accounts.
     */
    public function chartOfAccounts()
    {
        return redirect()->route('admin.accounting.accounts.index');
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

    public function accountsIndex()
    {
        $accounts = Account::with('children')->whereNull('parent_id')->orderBy('code')->get();
        return view('admin.accounting.accounts.index', compact('accounts'));
    }

    public function accountsCreate()
    {
        $accounts = Account::orderBy('name')->get();
        return view('admin.accounting.accounts.create', compact('accounts'));
    }

    public function accountsStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:accounts,code',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'parent_id' => 'nullable|exists:accounts,id',
        ]);

        $level = 0;
        if ($request->parent_id) {
            $parent = Account::find($request->parent_id);
            $level = $parent->level + 1;
        }

        Account::create($request->all() + ['level' => $level]);

        return redirect()->route('admin.accounting.accounts.index')->with('success', 'Account created successfully.');
    }

    public function accountsEdit(Account $account)
    {
        $accounts = Account::where('id', '!=', $account->id)->orderBy('name')->get();
        return view('admin.accounting.accounts.edit', compact('account', 'accounts'));
    }

    public function accountsUpdate(Request $request, Account $account)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:accounts,code,' . $account->id,
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'parent_id' => 'nullable|exists:accounts,id',
        ]);

        $level = 0;
        if ($request->parent_id) {
            $parent = Account::find($request->parent_id);
            $level = $parent->level + 1;
        }

        $account->update($request->all() + ['level' => $level]);

        return redirect()->route('admin.accounting.accounts.index')->with('success', 'Account updated successfully.');
    }

    public function accountsDestroy(Account $account)
    {
        // Add logic to prevent deletion if account has transactions
        if ($account->transactions()->exists() || $account->children()->exists()) {
            return redirect()->route('admin.accounting.accounts.index')->with('error', 'Account cannot be deleted because it has transactions or child accounts.');
        }

        $account->delete();

        return redirect()->route('admin.accounting.accounts.index')->with('success', 'Account deleted successfully.');
    }
}