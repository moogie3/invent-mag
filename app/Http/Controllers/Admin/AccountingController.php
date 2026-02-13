<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\AccountService;
use App\Services\AccountingExportService;
use App\Services\AccountingReportService;
use App\Services\ChartOfAccountsService;
use App\Services\GeneralLedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccountingController extends Controller
{
    protected $accountService;
    protected $generalLedgerService;
    protected $accountingReportService;
    protected $accountingExportService;
    protected $chartOfAccountsService;

    public function __construct(
        AccountService $accountService,
        GeneralLedgerService $generalLedgerService,
        AccountingReportService $accountingReportService,
        AccountingExportService $accountingExportService,
        ChartOfAccountsService $chartOfAccountsService
    ) {
        $this->accountService = $accountService;
        $this->generalLedgerService = $generalLedgerService;
        $this->accountingReportService = $accountingReportService;
        $this->accountingExportService = $accountingExportService;
        $this->chartOfAccountsService = $chartOfAccountsService;
    }

    /**
     * Display accounting settings.
     */
    public function accounting()
    {
        $settingsData = $this->chartOfAccountsService->getAccountingSettings();
        
        return view('admin.accounting.accounting-setting', [
            'accounts' => $settingsData['accounts'],
            'settings' => $settingsData['settings'],
        ]);
    }

    /**
     * Update accounting settings.
     */
    public function updateAccounting(Request $request)
    {
        $validatedData = $request->validate(
            $this->chartOfAccountsService->validateSettingsData($request->all())
        );

        $result = $this->chartOfAccountsService->updateAccountingSettings($validatedData);

        return redirect()->route('admin.setting.accounting')
            ->with('success', $result['message']);
    }

    /**
     * Reset chart of accounts to default.
     */
    public function resetToDefault(Request $request)
    {
        $result = $this->chartOfAccountsService->resetToDefault();

        if ($result['success']) {
            return redirect()->route('admin.setting.accounting')
                ->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
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
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $journalData = $this->generalLedgerService->getJournalEntries($startDate, $endDate);

        return view('admin.accounting.journal', [
            'entries' => $journalData['entries'],
            'startDate' => $journalData['start_date'],
            'endDate' => $journalData['end_date'],
        ]);
    }

    /**
     * Display the General Ledger for a specific account.
     */
    public function generalLedger(Request $request)
    {
        $accounts = $this->accountService->getAllAccountsOrdered();
        $selectedAccountId = $request->input('account_id');
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $ledgerData = [
            'transactions' => collect(),
            'opening_balance' => 0,
            'closing_balance' => 0,
            'account' => null,
        ];

        if ($selectedAccountId) {
            $ledgerData = $this->generalLedgerService->getGeneralLedger(
                $selectedAccountId,
                $startDate,
                $endDate
            );
        }

        return view('admin.accounting.general-ledger', [
            'accounts' => $accounts,
            'selectedAccount' => $ledgerData['account'],
            'transactions' => $ledgerData['transactions'],
            'openingBalance' => $ledgerData['opening_balance'],
            'closingBalance' => $ledgerData['closing_balance'],
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Display a Trial Balance report.
     */
    public function trialBalance(Request $request)
    {
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $reportData = $this->accountingReportService->generateTrialBalance($endDate);

        return view('admin.accounting.trial-balance', [
            'reportData' => $reportData['report_data'],
            'totalDebits' => $reportData['total_debits'],
            'totalCredits' => $reportData['total_credits'],
            'endDate' => $reportData['end_date'],
        ]);
    }

    /**
     * Display accounts index.
     */
    public function accountsIndex()
    {
        $accounts = $this->accountService->getAccountsWithChildren();
        return view('admin.accounting.accounts.index', compact('accounts'));
    }

    /**
     * Display account create form.
     */
    public function accountsCreate()
    {
        $accounts = $this->accountService->getAllAccountsOrdered();
        return view('admin.accounting.accounts.create', compact('accounts'));
    }

    /**
     * Store a new account.
     */
    public function accountsStore(Request $request)
    {
        try {
            $this->accountService->createAccount($request->all());
            return redirect()->route('admin.accounting.accounts.index')
                ->with('success', 'Account created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        }
    }

    /**
     * Display account edit form.
     */
    public function accountsEdit(Account $account)
    {
        $accounts = $this->accountService->getAllAccountsExcept($account->id);
        return view('admin.accounting.accounts.edit', compact('account', 'accounts'));
    }

    /**
     * Update an account.
     */
    public function accountsUpdate(Request $request, Account $account)
    {
        try {
            $this->accountService->updateAccount($account, $request->all());
            return redirect()->route('admin.accounting.accounts.index')
                ->with('success', 'Account updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        }
    }

    /**
     * Delete an account.
     */
    public function accountsDestroy(Account $account)
    {
        $result = $this->accountService->deleteAccount($account);

        $messageType = $result['success'] ? 'success' : 'error';
        
        return redirect()->route('admin.accounting.accounts.index')
            ->with($messageType, $result['message']);
    }

    /**
     * Export Chart of Accounts.
     */
    public function exportAll(Request $request)
    {
        $request->validate([
            'export_option' => 'required|string|in:pdf,csv',
        ]);

        try {
            $accounts = $this->accountService->getAccountsWithChildren();

            if ($request->export_option === 'pdf') {
                return $this->accountingExportService->exportChartOfAccountsToPdf(
                    $accounts,
                    'chart-of-accounts.pdf'
                );
            }

            if ($request->export_option === 'csv') {
                $accountList = $this->accountingReportService->flattenAccountHierarchy($accounts);
                return $this->accountingExportService->exportChartOfAccountsToCsv(
                    $accountList,
                    'chart-of-accounts.csv'
                );
            }
        } catch (\Exception $e) {
            Log::error('Error exporting accounts: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error exporting accounts. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Export General Journal.
     */
    public function exportJournal(Request $request)
    {
        $request->validate([
            'export_option' => 'required|string|in:pdf,csv',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        try {
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
            $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

            $entries = $this->generalLedgerService->getJournalEntriesForExport($startDate, $endDate);

            if ($request->export_option === 'pdf') {
                return $this->accountingExportService->exportJournalToPdf(
                    $entries,
                    $startDate,
                    $endDate,
                    'journal.pdf'
                );
            }

            if ($request->export_option === 'csv') {
                return $this->accountingExportService->exportJournalToCsv($entries, 'journal.csv');
            }
        } catch (\Exception $e) {
            Log::error('Error exporting journal: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error exporting journal. Please try again.',
                'error_details' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Export Trial Balance.
     */
    public function exportTrialBalance(Request $request)
    {
        $request->validate([
            'export_option' => 'required|string|in:pdf,csv',
            'end_date' => 'nullable|date',
        ]);

        try {
            $endDate = $request->input('end_date', Carbon::now()->toDateString());
            $reportData = $this->accountingReportService->generateTrialBalance($endDate);

            if ($request->export_option === 'pdf') {
                return $this->accountingExportService->exportTrialBalanceToPdf(
                    $reportData['report_data'],
                    $reportData['total_debits'],
                    $reportData['total_credits'],
                    $endDate,
                    'trial-balance.pdf'
                );
            }

            if ($request->export_option === 'csv') {
                return $this->accountingExportService->exportTrialBalanceToCsv(
                    $reportData['report_data'],
                    $reportData['total_debits'],
                    $reportData['total_credits'],
                    $endDate,
                    'trial-balance.csv'
                );
            }
        } catch (\Exception $e) {
            Log::error('Error exporting trial balance: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error exporting trial balance. Please try again.',
                'error_details' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Export General Ledger.
     */
    public function exportGeneralLedger(Request $request)
    {
        $request->validate([
            'export_option' => 'required|string|in:pdf,csv',
            'account_id' => 'required|integer|exists:accounts,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        try {
            $accountId = $request->input('account_id');
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
            $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

            $ledgerData = $this->generalLedgerService->getGeneralLedgerForExport(
                $accountId,
                $startDate,
                $endDate
            );

            if ($request->export_option === 'pdf') {
                return $this->accountingExportService->exportGeneralLedgerToPdf(
                    $ledgerData['account'],
                    $ledgerData['transactions'],
                    $ledgerData['opening_balance'],
                    $ledgerData['closing_balance'],
                    $startDate,
                    $endDate,
                    'general-ledger.pdf'
                );
            }

            if ($request->export_option === 'csv') {
                return $this->accountingExportService->exportGeneralLedgerToCsv(
                    $ledgerData['account'],
                    $ledgerData['transactions'],
                    $ledgerData['opening_balance'],
                    $ledgerData['closing_balance'],
                    $startDate,
                    $endDate,
                    'general-ledger.csv'
                );
            }
        } catch (\Exception $e) {
            Log::error('Error exporting general ledger: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error exporting general ledger. Please try again.',
                'error_details' => 'Internal server error',
            ], 500);
        }
    }
}
