<?php

namespace App\Services;

use App\Helpers\CurrencyHelper;
use Dompdf\Dompdf;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class AccountingExportService
{
    /**
     * Export to PDF.
     *
     * @param string $view
     * @param array $data
     * @param string $filename
     * @param string $orientation
     * @return Response
     */
    public function exportToPdf(string $view, array $data, string $filename, string $orientation = 'landscape'): Response
    {
        $dompdf = new Dompdf();
        $html = view($view, $data)->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();

        return $dompdf->stream($filename);
    }

    /**
     * Export to CSV.
     *
     * @param callable $callback
     * @param string $filename
     * @return Response
     */
    public function exportToCsv(callable $callback, string $filename): Response
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
     * Export Chart of Accounts to PDF.
     *
     * @param Collection $accounts
     * @param string $filename
     * @return Response
     */
    public function exportChartOfAccountsToPdf(Collection $accounts, string $filename): Response
    {
        return $this->exportToPdf(
            'admin.accounting.accounts.export-pdf',
            compact('accounts'),
            $filename,
            'landscape'
        );
    }

    /**
     * Export Chart of Accounts to CSV.
     *
     * @param array $accountList
     * @param string $filename
     * @return Response
     */
    public function exportChartOfAccountsToCsv(array $accountList, string $filename): Response
    {
        return $this->exportToCsv(function () use ($accountList) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Code', 'Name', 'Type', 'Level']);

            foreach ($accountList as $account) {
                fputcsv($file, [
                    $account['code'],
                    $account['name'],
                    $account['type'],
                    $account['level'],
                ]);
            }

            fclose($file);
        }, $filename);
    }

    /**
     * Export Journal to PDF.
     *
     * @param Collection $entries
     * @param string $startDate
     * @param string $endDate
     * @param string $filename
     * @return Response
     */
    public function exportJournalToPdf(Collection $entries, string $startDate, string $endDate, string $filename): Response
    {
        return $this->exportToPdf(
            'admin.accounting.journal-export-pdf',
            compact('entries', 'startDate', 'endDate'),
            $filename,
            'landscape'
        );
    }

    /**
     * Export Journal to CSV.
     *
     * @param Collection $entries
     * @param string $filename
     * @return Response
     */
    public function exportJournalToCsv(Collection $entries, string $filename): Response
    {
        return $this->exportToCsv(function () use ($entries) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Description', 'Account', 'Debit', 'Credit']);

            foreach ($entries as $entry) {
                foreach ($entry->transactions as $index => $transaction) {
                    $row = [
                        $index === 0 ? $entry->date->format('Y-m-d') : '',
                        $index === 0 ? $entry->description : '',
                        $transaction->account->name,
                        $transaction->type == 'debit' ? CurrencyHelper::format($transaction->amount) : '',
                        $transaction->type == 'credit' ? CurrencyHelper::format($transaction->amount) : '',
                    ];
                    fputcsv($file, $row);
                }
            }

            fclose($file);
        }, $filename);
    }

    /**
     * Export Trial Balance to PDF.
     *
     * @param array $reportData
     * @param float $totalDebits
     * @param float $totalCredits
     * @param string $endDate
     * @param string $filename
     * @return Response
     */
    public function exportTrialBalanceToPdf(array $reportData, float $totalDebits, float $totalCredits, string $endDate, string $filename): Response
    {
        return $this->exportToPdf(
            'admin.accounting.trial-balance-export-pdf',
            compact('reportData', 'totalDebits', 'totalCredits', 'endDate'),
            $filename,
            'landscape'
        );
    }

    /**
     * Export Trial Balance to CSV.
     *
     * @param array $reportData
     * @param float $totalDebits
     * @param float $totalCredits
     * @param string $endDate
     * @param string $filename
     * @return Response
     */
    public function exportTrialBalanceToCsv(array $reportData, float $totalDebits, float $totalCredits, string $endDate, string $filename): Response
    {
        return $this->exportToCsv(function () use ($reportData, $totalDebits, $totalCredits, $endDate) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Trial Balance as of ' . $endDate]);
            fputcsv($file, []);
            fputcsv($file, ['Code', 'Account', 'Debit', 'Credit']);

            foreach ($reportData as $item) {
                fputcsv($file, [
                    $item['code'],
                    $item['name'],
                    $item['debit'] > 0 ? CurrencyHelper::format($item['debit']) : '-',
                    $item['credit'] > 0 ? CurrencyHelper::format($item['credit']) : '-',
                ]);
            }

            fputcsv($file, [
                '',
                'Total',
                CurrencyHelper::format($totalDebits),
                CurrencyHelper::format($totalCredits),
            ]);

            fclose($file);
        }, $filename);
    }

    /**
     * Export General Ledger to PDF.
     *
     * @param mixed $account
     * @param Collection $transactions
     * @param float $openingBalance
     * @param float $closingBalance
     * @param string $startDate
     * @param string $endDate
     * @param string $filename
     * @return Response
     */
    public function exportGeneralLedgerToPdf($account, Collection $transactions, float $openingBalance, float $closingBalance, string $startDate, string $endDate, string $filename): Response
    {
        return $this->exportToPdf(
            'admin.accounting.general-ledger-export-pdf',
            compact('account', 'transactions', 'openingBalance', 'closingBalance', 'startDate', 'endDate'),
            $filename,
            'landscape'
        );
    }

    /**
     * Export General Ledger to CSV.
     *
     * @param mixed $account
     * @param Collection $transactions
     * @param float $openingBalance
     * @param float $closingBalance
     * @param string $startDate
     * @param string $endDate
     * @param string $filename
     * @return Response
     */
    public function exportGeneralLedgerToCsv($account, Collection $transactions, float $openingBalance, float $closingBalance, string $startDate, string $endDate, string $filename): Response
    {
        return $this->exportToCsv(function () use ($account, $transactions, $openingBalance, $closingBalance, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Account:', $account->name]);
            fputcsv($file, ['Period:', $startDate . ' to ' . $endDate]);
            fputcsv($file, []);
            fputcsv($file, ['Date', 'Description', 'Debit', 'Credit', 'Balance']);

            fputcsv($file, [
                '',
                'Opening Balance',
                '',
                '',
                CurrencyHelper::format($openingBalance),
            ]);

            $balance = $openingBalance;
            foreach ($transactions as $transaction) {
                $balance += $transaction->type === 'debit' ? $transaction->amount : -$transaction->amount;
                fputcsv($file, [
                    $transaction->journalEntry->date->format('Y-m-d'),
                    $transaction->journalEntry->description,
                    $transaction->type == 'debit' ? CurrencyHelper::format($transaction->amount) : '',
                    $transaction->type == 'credit' ? CurrencyHelper::format($transaction->amount) : '',
                    CurrencyHelper::format($balance),
                ]);
            }

            fputcsv($file, [
                '',
                'Closing Balance',
                '',
                '',
                CurrencyHelper::format($closingBalance),
            ]);

            fclose($file);
        }, $filename);
    }
}
