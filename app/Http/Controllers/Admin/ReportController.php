<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockAdjustment;
use Illuminate\Http\Request;
use App\Models\POItem;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Log;
use App\Models\Sales;
use App\Models\Purchase;
use Carbon\Carbon;
use App\DTOs\TransactionDTO;
use App\Models\Transaction; // Assuming a generic Transaction model if not specifically Sales/Purchase
use Illuminate\Support\Facades\DB;
use App\Models\Account; // Required for Income Statement
use App\Services\DashboardService;

class ReportController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Display the adjustment log.
     */
    public function adjustmentLog(Request $request)
    {
        $adjustments = StockAdjustment::with(['product:id,name', 'adjustedBy:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.reports.adjustment-log', compact('adjustments'));
    }

    /**
     * Display recent sales and purchase transactions.
     */
    public function recentTransactions(Request $request)
    {
        $filters = [
            'per_page' => $request->get('per_page', 25),
            'type' => $request->get('type'),
            'status' => $request->get('status'),
            'date_range' => $request->get('date_range', 'all'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'search' => $request->get('search'),
            'sort' => $request->get('sort', 'date'),
            'direction' => $request->get('direction', 'desc'),
        ];

        $transactions = $this->transactionService->getTransactions($filters, $filters['per_page']);
        $summary = $this->transactionService->getTransactionsSummary($filters);

        if ($request->get('export') === 'excel') {
            return $this->exportTransactions($request, $filters);
        }
        return view('admin.reports.recentts', array_merge(compact('transactions', 'summary'), $filters));
    }

    private function exportTransactions(Request $request, array $filters)
    {
        return $this->transactionService->exportTransactions($filters, $request->get('selected') ? explode(',', $request->get('selected')) : null);
    }

    public function bulkMarkAsPaid(Request $request)
    {
        try {
            $transactionIds = $request->input('transaction_ids', []);

            if (empty($transactionIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No transactions selected.'
                ], 400);
            }

            $updatedCount = $this->transactionService->bulkMarkAsPaid($transactionIds);

            return response()->json([
                'success' => true,
                'message' => "Successfully marked {$updatedCount} transaction(s) as paid.",
                'updated_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk mark as paid error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating transactions.'
            ], 500);
        }
    }

    public function markAsPaid(Request $request, $id)
    {
        try {
            $type = $request->input('type');
            $result = $this->transactionService->markTransactionAsPaid($id, $type);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating transaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the Income Statement (Profit & Loss) report.
     */
    public function incomeStatement(Request $request)
    {
        // Placeholder for initial Income Statement implementation
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Fetch revenue accounts and their balances
        $revenueAccounts = Account::where('type', 'revenue')
            ->with(['transactions' => function ($query) use ($startDate, $endDate) {
                $query->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                });
            }])->get();

        $totalRevenue = $revenueAccounts->sum(function ($account) {
            return $account->transactions->sum(function ($transaction) {
                return $transaction->type === 'credit' ? $transaction->amount : -$transaction->amount;
            });
        });

        // Fetch expense accounts and their balances
        $expenseAccounts = Account::where('type', 'expense')
            ->with(['transactions' => function ($query) use ($startDate, $endDate) {
                $query->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                });
            }])->get();

        $totalExpenses = $expenseAccounts->sum(function ($account) {
            return $account->transactions->sum(function ($transaction) {
                return $transaction->type === 'debit' ? $transaction->amount : -$transaction->amount;
            });
        });

        // Simplified for now: Gross Profit calculation might involve specific COGS accounts,
        // but for a general income statement, we sum all revenues and expenses.
        // Net Income = Total Revenue - Total Expenses
        $netIncome = $totalRevenue - $totalExpenses;

        return view('admin.reports.income-statement', compact(
            'revenueAccounts',
            'totalRevenue',
            'expenseAccounts',
            'totalExpenses',
            'netIncome',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Display the Balance Sheet report.
     */
    public function balanceSheet(Request $request)
    {
        $endDate = $request->input('end_date', Carbon::now()->endOfDay()->toDateString());

        // Get all accounts and calculate their balances up to the end_date
        $accounts = Account::with(['transactions' => function ($query) use ($endDate) {
            $query->whereHas('journalEntry', function ($q) use ($endDate) {
                $q->where('date', '<=', $endDate);
            });
        }])->get();

        $assets = collect();
        $liabilities = collect();
        $equity = collect();
        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;

        foreach ($accounts as $account) {
            $balance = 0;
            // Calculate balance based on account type (debit vs credit normal balances)
            $debits = $account->transactions->sum(fn($t) => $t->type === 'debit' ? $t->amount : 0);
            $credits = $account->transactions->sum(fn($t) => $t->type === 'credit' ? $t->amount : 0);

            if (in_array($account->type, ['asset', 'expense'])) { // Assets and Expenses normally have debit balances
                $balance = $debits - $credits;
            } else { // Liabilities, Equity, and Revenue normally have credit balances
                $balance = $credits - $debits;
            }

            // Only include accounts with a non-zero balance for clarity
            if (abs($balance) > 0.001) { // Using a small epsilon for floating point comparison
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
                    // For income statement accounts (revenue, expense), their net effect
                    // rolls into equity for the balance sheet.
                    case 'revenue':
                        $totalEquity += $balance; // Revenue increases equity
                        break;
                    case 'expense':
                        $totalEquity -= $balance; // Expenses decrease equity
                        break;
                }
            }
        }

        // This is a simplified approach. In a real system, the net income/loss from
        // the Income Statement period would be explicitly rolled into Retained Earnings (Equity)
        // at the end of an accounting period. For a point-in-time balance sheet, we directly sum
        // revenues and expenses' impact into equity here.

        // Ensure assets, liabilities, and equity are sorted by account code or name for readability
        $assets = $assets->sortBy('code');
        $liabilities = $liabilities->sortBy('code');
        $equity = $equity->sortBy('code');

        // Check accounting equation for debugging/validation
        $equation_balanced = abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.001;

        return view('admin.reports.balance-sheet', compact(
            'assets',
            'liabilities',
            'equity',
            'totalAssets',
            'totalLiabilities',
            'totalEquity',
            'endDate',
            'equation_balanced'
        ));
    }

    public function agedReceivables(Request $request)
    {
        $now = Carbon::now();
        $unpaidSales = Sales::with('customer')
            ->whereIn('status', ['Unpaid', 'Partial'])
            ->whereNotNull('due_date')
            ->get()
            ->map(function ($sale) use ($now) {
                $daysOverdue = $now->diffInDays($sale->due_date, false);
                $sale->days_overdue = $daysOverdue < 0 ? round(abs($daysOverdue)) : 0;
                return $sale;
            });

        $aging = [
            'current' => $unpaidSales->where('days_overdue', 0),
            '1-30' => $unpaidSales->where('days_overdue', '>', 0)->where('days_overdue', '<=', 30),
            '31-60' => $unpaidSales->where('days_overdue', '>', 30)->where('days_overdue', '<=', 60),
            '61-90' => $unpaidSales->where('days_overdue', '>', 60)->where('days_overdue', '<=', 90),
            '90+' => $unpaidSales->where('days_overdue', '>', 90),
        ];

        return view('admin.reports.aged-receivables', compact('aging'));
    }

    public function agedPayables(Request $request)
    {
        $now = Carbon::now();
        $unpaidPurchases = Purchase::with('supplier')
            ->whereIn('status', ['Unpaid', 'Partial'])
            ->whereNotNull('due_date')
            ->get()
            ->map(function ($purchase) use ($now) {
                $daysOverdue = $now->diffInDays($purchase->due_date, false);
                $purchase->days_overdue = $daysOverdue < 0 ? round(abs($daysOverdue)) : 0;
                return $purchase;
            });

        $aging = [
            'current' => $unpaidPurchases->where('days_overdue', 0),
            '1-30' => $unpaidPurchases->where('days_overdue', '>', 0)->where('days_overdue', '<=', 30),
            '31-60' => $unpaidPurchases->where('days_overdue', '>', 30)->where('days_overdue', '<=', 60),
            '61-90' => $unpaidPurchases->where('days_overdue', '>', 60)->where('days_overdue', '<=', 90),
            '90+' => $unpaidPurchases->where('days_overdue', '>', 90),
        ];

        return view('admin.reports.aged-payables', compact('aging'));
    }
}