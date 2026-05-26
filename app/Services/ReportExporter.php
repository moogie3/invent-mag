<?php

namespace App\Services;

use App\Helpers\CurrencyHelper;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExporter
{
    /**
     * Export data to PDF.
     *
     * @param string $view
     * @param array $data
     * @param string $filename
     * @param string $orientation
     * @return Response
     */
    public function exportToPdf(string $view, array $data, string $filename, string $orientation = 'portrait'): Response
    {
        $dompdf = new Dompdf();
        $html = view($view, $data)->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();

        $output = $dompdf->output();
        
        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export data to CSV.
     *
     * @param callable $callback
     * @param string $filename
     * @return StreamedResponse
     */
    public function exportToCsv(callable $callback, string $filename): StreamedResponse
    {
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename,
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export Income Statement to CSV.
     *
     * @param array $reportData
     * @param string $filename
     * @return Response
     */
    public function exportIncomeStatementToCsv(array $reportData, string $filename): Response
    {
        return $this->exportToCsv(function () use ($reportData) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Income Statement']);
            fputcsv($file, ["Period: {$reportData['start_date']} to {$reportData['end_date']}"]);
            fputcsv($file, []);

            fputcsv($file, ['Revenue']);
            foreach ($reportData['revenue_accounts'] as $account) {
                fputcsv($file, [$account->name, CurrencyHelper::format($account->calculated_balance)]);
            }
            fputcsv($file, ['Total Revenue', CurrencyHelper::format($reportData['total_revenue'])]);
            fputcsv($file, []);

            fputcsv($file, ['Expenses']);
            foreach ($reportData['expense_accounts'] as $account) {
                fputcsv($file, [$account->name, CurrencyHelper::format($account->calculated_balance)]);
            }
            fputcsv($file, ['Total Expenses', CurrencyHelper::format($reportData['total_expenses'])]);
            fputcsv($file, []);

            fputcsv($file, ['Net Income', CurrencyHelper::format($reportData['net_income'])]);

            fclose($file);
        }, $filename);
    }

    /**
     * Export Balance Sheet to CSV.
     *
     * @param array $reportData
     * @param string $filename
     * @return Response
     */
    public function exportBalanceSheetToCsv(array $reportData, string $filename): Response
    {
        return $this->exportToCsv(function () use ($reportData) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Balance Sheet']);
            fputcsv($file, ["As of: {$reportData['end_date']}"]);
            fputcsv($file, []);

            fputcsv($file, ['Assets']);
            foreach ($reportData['assets'] as $account) {
                fputcsv($file, [$account->name, CurrencyHelper::format($account->calculated_balance)]);
            }
            fputcsv($file, ['Total Assets', CurrencyHelper::format($reportData['total_assets'])]);
            fputcsv($file, []);

            fputcsv($file, ['Liabilities']);
            foreach ($reportData['liabilities'] as $account) {
                fputcsv($file, [$account->name, CurrencyHelper::format($account->calculated_balance)]);
            }
            fputcsv($file, ['Total Liabilities', CurrencyHelper::format($reportData['total_liabilities'])]);
            fputcsv($file, []);

            fputcsv($file, ['Equity']);
            foreach ($reportData['equity'] as $account) {
                fputcsv($file, [$account->name, CurrencyHelper::format($account->calculated_balance)]);
            }
            fputcsv($file, ['Total Equity', CurrencyHelper::format($reportData['total_equity'])]);
            fputcsv($file, []);

            fputcsv($file, ['Total Liabilities & Equity', CurrencyHelper::format($reportData['total_liabilities'] + $reportData['total_equity'])]);

            fclose($file);
        }, $filename);
    }

    /**
     * Export Aged Receivables/Payables to CSV.
     *
     * @param array $aging
     * @param string $filename
     * @param string $reportType 'receivables' or 'payables'
     * @return Response
     */
    public function exportAgingToCsv(array $aging, string $filename, string $reportType = 'receivables'): Response
    {
        $buckets = [
            'current' => 'Current',
            '1-30' => '1-30 Days Overdue',
            '31-60' => '31-60 Days Overdue',
            '61-90' => '61-90 Days Overdue',
            '90+' => 'Over 90 Days Overdue',
        ];

        $contactLabel = $reportType === 'receivables' ? 'Customer' : 'Supplier';

        return $this->exportToCsv(function () use ($aging, $buckets, $contactLabel) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Aged ' . ($contactLabel === 'Customer' ? 'Receivables' : 'Payables') . ' Report']);
            fputcsv($file, ["Report Date: " . Carbon::now()->format('Y-m-d')]);
            fputcsv($file, []);

            foreach ($aging as $bucketKey => $invoices) {
                if ($invoices->count() > 0) {
                    fputcsv($file, [$buckets[$bucketKey]]);
                    fputcsv($file, [
                        $contactLabel,
                        'Invoice No',
                        'Due Date',
                        'Days Overdue',
                        'Amount',
                    ]);
                    
                    foreach ($invoices as $invoice) {
                        $contact = $contactLabel === 'Customer' 
                            ? ($invoice->customer->name ?? 'Walk-in Customer')
                            : ($invoice->supplier->name ?? 'Unknown Supplier');
                            
                        fputcsv($file, [
                            $contact,
                            $invoice->invoice,
                            Carbon::parse($invoice->due_date)->format('Y-m-d'),
                            $invoice->days_overdue,
                            CurrencyHelper::format($invoice->total),
                        ]);
                    }
                    
                    fputcsv($file, ['Total for ' . $buckets[$bucketKey], '', '', '', CurrencyHelper::format($invoices->sum('total'))]);
                    fputcsv($file, []);
                }
            }

            fclose($file);
        }, $filename);
    }

    /**
     * Export Adjustment Log to CSV.
     *
     * @param Collection $adjustments
     * @param string $filename
     * @return Response
     */
    public function exportAdjustmentLogToCsv(Collection $adjustments, string $filename): Response
    {
        return $this->exportToCsv(function () use ($adjustments) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Date',
                'Product',
                'Type',
                'Quantity Before',
                'Quantity After',
                'Change',
                'Reason',
                'Adjusted By',
            ]);

            foreach ($adjustments as $log) {
                $change = $log->quantity_after - $log->quantity_before;
                fputcsv($file, [
                    $log->created_at->translatedFormat('d M Y, H:i'),
                    $log->product ? $log->product->name : 'Product Not Found',
                    $log->adjustment_type,
                    $log->quantity_before,
                    $log->quantity_after,
                    $change,
                    $log->reason ?: 'N/A',
                    $log->adjustedBy ? $log->adjustedBy->name : 'System',
                ]);
            }

            fclose($file);
        }, $filename);
    }

    /**
     * Export Recent Transactions to CSV.
     *
     * @param Collection $transactions
     * @param string $filename
     * @return Response
     */
    public function exportRecentTransactionsToCsv(Collection $transactions, string $filename): Response
    {
        return $this->exportToCsv(function () use ($transactions) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Type',
                'Invoice',
                'Customer/Supplier',
                'Date',
                'Amount',
                'Status',
            ]);

            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction['type'],
                    $transaction['invoice'],
                    $transaction['customer_supplier'],
                    Carbon::parse($transaction['date'])->format('Y-m-d H:i'),
                    CurrencyHelper::format($transaction['amount']),
                    $transaction['status'],
                ]);
            }

            fclose($file);
        }, $filename);
    }

    /**
     * Export Cash Flow Statement to CSV.
     *
     * @param array $reportData
     * @param string $filename
     * @return Response
     */
    public function exportCashFlowToCsv(array $reportData, string $filename): Response
    {
        return $this->exportToCsv(function () use ($reportData) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Cash Flow Statement']);
            fputcsv($file, ["Period: {$reportData['start_date']} to {$reportData['end_date']}"]);
            fputcsv($file, []);

            fputcsv($file, ['Operating Activities']);
            fputcsv($file, ['Net Income', CurrencyHelper::format($reportData['net_income'])]);
            
            foreach ($reportData['non_cash_items'] as $item) {
                fputcsv($file, [$item['account_name'], CurrencyHelper::format($item['amount'])]);
            }
            foreach ($reportData['working_capital_changes'] as $item) {
                fputcsv($file, [$item['account_name'], CurrencyHelper::format($item['amount'])]);
            }
            fputcsv($file, ['Net Cash from Operating Activities', CurrencyHelper::format($reportData['operating_cash_flow'])]);
            fputcsv($file, []);

            fputcsv($file, ['Investing Activities']);
            foreach ($reportData['investing_activities'] as $item) {
                fputcsv($file, [$item['account_name'], CurrencyHelper::format($item['amount'])]);
            }
            fputcsv($file, ['Net Cash from Investing Activities', CurrencyHelper::format($reportData['investing_cash_flow'])]);
            fputcsv($file, []);

            fputcsv($file, ['Financing Activities']);
            foreach ($reportData['financing_activities'] as $item) {
                fputcsv($file, [$item['account_name'], CurrencyHelper::format($item['amount'])]);
            }
            fputcsv($file, ['Net Cash from Financing Activities', CurrencyHelper::format($reportData['financing_cash_flow'])]);
            fputcsv($file, []);

            fputcsv($file, ['Net Increase (Decrease) in Cash', CurrencyHelper::format($reportData['net_change_in_cash'])]);
            fputcsv($file, ['Cash at Beginning of Period', CurrencyHelper::format($reportData['opening_cash_balance'])]);
            fputcsv($file, ['Cash at End of Period', CurrencyHelper::format($reportData['closing_cash_balance'])]);

            fclose($file);
        }, $filename);
    }
}