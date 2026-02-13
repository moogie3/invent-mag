<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CurrencyHelper;
use App\Http\Controllers\Controller;
use App\Models\StockAdjustment;
use App\Services\AgingReportService;
use App\Services\FinancialReportingService;
use App\Services\ReportExporter;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    protected $transactionService;
    protected $financialReportingService;
    protected $agingReportService;
    protected $reportExporter;

    public function __construct(
        TransactionService $transactionService,
        FinancialReportingService $financialReportingService,
        AgingReportService $agingReportService,
        ReportExporter $reportExporter
    ) {
        $this->transactionService = $transactionService;
        $this->financialReportingService = $financialReportingService;
        $this->agingReportService = $agingReportService;
        $this->reportExporter = $reportExporter;
    }

    /**
     * Display the adjustment log.
     */
    public function adjustmentLog(Request $request)
    {
        $query = StockAdjustment::with(['product:id,name', 'adjustedBy:id,name', 'warehouse:id,name']);

        if ($request->warehouse_id && $request->warehouse_id !== 'all') {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->type && $request->type !== 'all') {
            $query->where('adjustment_type', $request->type);
        }

        $adjustments = $query->orderBy('created_at', 'desc')->paginate(20);

        $warehouses = \App\Models\Warehouse::all();
        $types = ['adjustment', 'increase', 'decrease', 'correction', 'transfer'];

        return view('admin.reports.adjustment-log', compact('adjustments', 'warehouses', 'types'));
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

    public function bulkExport(Request $request)
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'string',
            'export_option' => 'required|string|in:pdf,csv',
        ]);

        return $this->transactionService->bulkExportTransactions($request->all(), $request->ids, $request->export_option);
    }

    public function bulkMarkAsPaid(Request $request)
    {
        try {
            $transactions = $request->input('transactions', []);

            if (empty($transactions)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No transactions selected.'
                ], 400);
            }

            $updatedCount = $this->transactionService->bulkMarkAsPaid($transactions);

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
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $reportData = $this->financialReportingService->generateIncomeStatement($startDate, $endDate);

        return view('admin.reports.income-statement', [
            'revenueAccounts' => $reportData['revenue_accounts'],
            'totalRevenue' => $reportData['total_revenue'],
            'expenseAccounts' => $reportData['expense_accounts'],
            'totalExpenses' => $reportData['total_expenses'],
            'netIncome' => $reportData['net_income'],
            'startDate' => $reportData['start_date'],
            'endDate' => $reportData['end_date'],
        ]);
    }

    /**
     * Export Income Statement
     */
    public function exportIncomeStatement(Request $request)
    {
        $request->validate([
            'export_option' => 'required|string|in:pdf,csv',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        try {
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
            $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

            $reportData = $this->financialReportingService->generateIncomeStatement($startDate, $endDate);

            if ($request->export_option === 'pdf') {
                return $this->reportExporter->exportToPdf(
                    'admin.reports.income-statement-export-pdf',
                    $reportData,
                    'income-statement.pdf'
                );
            }

            if ($request->export_option === 'csv') {
                return $this->reportExporter->exportIncomeStatementToCsv($reportData, 'income-statement.csv');
            }
        } catch (\Exception $e) {
            Log::error('Error exporting income statement: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error exporting income statement. Please try again.',
                'error_details' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the Balance Sheet report.
     */
    public function balanceSheet(Request $request)
    {
        $endDate = $request->input('end_date', Carbon::now()->endOfDay()->toDateString());

        $reportData = $this->financialReportingService->generateBalanceSheet($endDate);

        return view('admin.reports.balance-sheet', [
            'assets' => $reportData['assets'],
            'liabilities' => $reportData['liabilities'],
            'equity' => $reportData['equity'],
            'totalAssets' => $reportData['total_assets'],
            'totalLiabilities' => $reportData['total_liabilities'],
            'totalEquity' => $reportData['total_equity'],
            'endDate' => $reportData['end_date'],
            'equation_balanced' => $reportData['equation_balanced'],
        ]);
    }

    /**
     * Export Balance Sheet
     */
    public function exportBalanceSheet(Request $request)
    {
        $request->validate([
            'export_option' => 'required|string|in:pdf,csv',
            'end_date' => 'nullable|date',
        ]);

        try {
            $endDate = $request->input('end_date', Carbon::now()->endOfDay()->toDateString());

            $reportData = $this->financialReportingService->generateBalanceSheet($endDate);

            if ($request->export_option === 'pdf') {
                return $this->reportExporter->exportToPdf(
                    'admin.reports.balance-sheet-export-pdf',
                    $reportData,
                    'balance-sheet.pdf'
                );
            }

            if ($request->export_option === 'csv') {
                return $this->reportExporter->exportBalanceSheetToCsv($reportData, 'balance-sheet.csv');
            }
        } catch (\Exception $e) {
            Log::error('Error exporting balance sheet: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error exporting balance sheet. Please try again.',
                'error_details' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display Aged Receivables report.
     */
    public function agedReceivables(Request $request)
    {
        $aging = $this->agingReportService->generateAgedReceivables();

        return view('admin.reports.aged-receivables', compact('aging'));
    }

    /**
     * Export Aged Receivables Report
     */
    public function exportAgedReceivables(Request $request)
    {
        $request->validate([
            'export_option' => 'required|string|in:pdf,csv',
        ]);

        try {
            $aging = $this->agingReportService->generateAgedReceivables();

            if ($request->export_option === 'pdf') {
                return $this->reportExporter->exportToPdf(
                    'admin.reports.aged-receivables-export-pdf',
                    compact('aging'),
                    'aged-receivables.pdf'
                );
            }

            if ($request->export_option === 'csv') {
                return $this->reportExporter->exportAgingToCsv($aging, 'aged-receivables.csv', 'receivables');
            }
        } catch (\Exception $e) {
            Log::error('Error exporting aged receivables: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error exporting aged receivables report. Please try again.',
                'error_details' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display Aged Payables report.
     */
    public function agedPayables(Request $request)
    {
        $aging = $this->agingReportService->generateAgedPayables();

        return view('admin.reports.aged-payables', compact('aging'));
    }

    /**
     * Export Aged Payables Report
     */
    public function exportAgedPayables(Request $request)
    {
        $request->validate([
            'export_option' => 'required|string|in:pdf,csv',
        ]);

        try {
            $aging = $this->agingReportService->generateAgedPayables();

            if ($request->export_option === 'pdf') {
                return $this->reportExporter->exportToPdf(
                    'admin.reports.aged-payables-export-pdf',
                    compact('aging'),
                    'aged-payables.pdf'
                );
            }

            if ($request->export_option === 'csv') {
                return $this->reportExporter->exportAgingToCsv($aging, 'aged-payables.csv', 'payables');
            }
        } catch (\Exception $e) {
            Log::error('Error exporting aged payables: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error exporting aged payables report. Please try again.',
                'error_details' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Export Adjustment Log
     */
    public function exportAdjustmentLog(Request $request)
    {
        $request->validate([
            'export_option' => 'required|string|in:pdf,csv',
        ]);

        try {
            $adjustments = StockAdjustment::with(['product:id,name', 'adjustedBy:id,name'])
                ->orderBy('created_at', 'desc')
                ->get();

            if ($request->export_option === 'pdf') {
                return $this->reportExporter->exportToPdf(
                    'admin.reports.adjustment-log-export-pdf',
                    compact('adjustments'),
                    'adjustment-log.pdf'
                );
            }

            if ($request->export_option === 'csv') {
                return $this->reportExporter->exportAdjustmentLogToCsv($adjustments, 'adjustment-log.csv');
            }
        } catch (\Exception $e) {
            Log::error('Error exporting adjustment log: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error exporting adjustment log. Please try again.',
                'error_details' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Export Recent Transactions
     */
    public function exportRecentTransactions(Request $request)
    {
        $request->validate([
            'export_option' => 'required|string|in:pdf,csv',
        ]);

        try {
            $selectedIds = $request->input('selected', []);
            $filters = [
                'type' => $request->get('type'),
                'status' => $request->get('status'),
                'date_range' => $request->get('date_range', 'all'),
                'start_date' => $request->get('start_date'),
                'end_date' => $request->get('end_date'),
                'search' => $request->get('search'),
            ];

            $transactions = $this->transactionService->getTransactionsForExport($filters, $selectedIds);

            if ($request->export_option === 'pdf') {
                return $this->reportExporter->exportToPdf(
                    'admin.reports.recent-transactions-export-pdf',
                    compact('transactions'),
                    'recent-transactions.pdf'
                );
            }

            if ($request->export_option === 'csv') {
                return $this->reportExporter->exportRecentTransactionsToCsv($transactions, 'recent-transactions.csv');
            }
        } catch (\Exception $e) {
            Log::error('Error exporting recent transactions: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error exporting recent transactions. Please try again.',
                'error_details' => 'Internal server error',
            ], 500);
        }
    }
}
