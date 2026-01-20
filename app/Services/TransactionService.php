<?php

namespace App\Services;

use App\DTOs\TransactionDTO;
use App\Models\Purchase;
use App\Models\Sales;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Dompdf\Dompdf;
use App\Services\SalesService;
use App\Services\PurchaseService;

class TransactionService
{
    protected $salesService;
    protected $purchaseService;

    public function __construct(SalesService $salesService, PurchaseService $purchaseService)
    {
        $this->salesService = $salesService;
        $this->purchaseService = $purchaseService;
    }

    public function getTransactions(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $dates = $this->calculateDateRange($filters['date_range'], $filters['start_date'], $filters['end_date']);

        $salesQuery = $this->buildSalesQuery($dates, $filters['status'], $filters['search']);
        $purchaseQuery = $this->buildPurchaseQuery($dates, $filters['status'], $filters['search']);

        $sales = collect();
        if (!$filters['type'] || $filters['type'] === 'sale') {
            $sales = $salesQuery->get()->map(fn($sale) => $this->transformSaleToTransaction($sale));
        }

        $purchases = collect();
        if (!$filters['type'] || $filters['type'] === 'purchase') {
            $purchases = $purchaseQuery->get()->map(fn($purchase) => $this->transformPurchaseToTransaction($purchase));
        }

        $transactions = $this->sortTransactions($sales->merge($purchases), $filters['sort'], $filters['direction']);

        return $this->paginateTransactions($transactions, $perPage);
    }

    public function getTransactionsSummary(array $filters): array
    {
        $dates = $this->calculateDateRange($filters['date_range'], $filters['start_date'], $filters['end_date']);

        $salesQuery = Sales::query()
            ->when($dates['start'], fn($q) => $q->where('order_date', '>=', $dates['start']))
            ->when($dates['end'], fn($q) => $q->where('order_date', '<=', $dates['end']))
            ->when($filters['status'], fn($q, $status) => $q->where('status', $status))
            ->when($filters['search'] && (!$filters['type'] || $filters['type'] === 'sale'), function ($q) use ($filters) {
                $q->where(function ($query) use ($filters) {
                    $query->where('invoice', 'LIKE', "%{$filters['search']}%")
                        ->orWhereHas('customer', fn($cq) => $cq->where('name', 'LIKE', "%{$filters['search']}%"));
                });
            });

        $purchaseQuery = Purchase::query()
            ->when($dates['start'], fn($q) => $q->where('order_date', '>=', $dates['start']))
            ->when($dates['end'], fn($q) => $q->where('order_date', '<=', $dates['end']))
            ->when($filters['status'], fn($q, $status) => $q->where('status', $status))
            ->when($filters['search'] && (!$filters['type'] || $filters['type'] === 'purchase'), function ($q) use ($filters) {
                $q->where(function ($query) use ($filters) {
                    $query->where('invoice', 'LIKE', "%{$filters['search']}%")
                        ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'LIKE', "%{$filters['search']}%"));
                });
            });

        $salesStats = null;
        if (!$filters['type'] || $filters['type'] === 'sale') {
            $salesStats = $salesQuery->selectRaw('
                COUNT(*) as count,
                SUM(total) as amount,
                SUM(CASE WHEN status = "Paid" THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status = "Paid" THEN total ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status IN ("Unpaid", "Partial") THEN 1 ELSE 0 END) as unpaid_count,
                SUM(CASE WHEN status IN ("Unpaid", "Partial") THEN total ELSE 0 END) as unpaid_amount
            ')->first();
        }

        $purchaseStats = null;
        if (!$filters['type'] || $filters['type'] === 'purchase') {
            $purchaseStats = $purchaseQuery->selectRaw('
                COUNT(*) as count,
                SUM(total) as amount,
                SUM(CASE WHEN status = "Paid" THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status = "Paid" THEN total ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status IN ("Unpaid", "Partial") THEN 1 ELSE 0 END) as unpaid_count,
                SUM(CASE WHEN status IN ("Unpaid", "Partial") THEN total ELSE 0 END) as unpaid_amount
            ')->first();
        }

        return [
            'total_count' => ($salesStats->count ?? 0) + ($purchaseStats->count ?? 0),
            'sales_count' => $salesStats->count ?? 0,
            'purchases_count' => $purchaseStats->count ?? 0,
            'total_amount' => ($salesStats->amount ?? 0) + ($purchaseStats->amount ?? 0),
            'sales_amount' => $salesStats->amount ?? 0,
            'purchases_amount' => $purchaseStats->amount ?? 0,
            'paid_count' => ($salesStats->paid_count ?? 0) + ($purchaseStats->paid_count ?? 0),
            'paid_amount' => ($salesStats->paid_amount ?? 0) + ($purchaseStats->paid_amount ?? 0),
            'unpaid_count' => ($salesStats->unpaid_count ?? 0) + ($purchaseStats->unpaid_count ?? 0),
            'unpaid_amount' => ($salesStats->unpaid_amount ?? 0) + ($purchaseStats->unpaid_amount ?? 0),
        ];
    }

    public function bulkMarkAsPaid(array $transactions): int
    {
        $updatedCount = 0;

        $salesIds = [];
        $purchaseIds = [];

        foreach ($transactions as $transaction) {
            if ($transaction['type'] === 'sale') {
                $salesIds[] = $transaction['id'];
            } elseif ($transaction['type'] === 'purchase') {
                $purchaseIds[] = $transaction['id'];
            }
        }

        if (!empty($salesIds)) {
            $updatedCount += $this->salesService->bulkMarkPaid($salesIds);
        }

        if (!empty($purchaseIds)) {
            $updatedCount += $this->purchaseService->bulkMarkPaid($purchaseIds);
        }

        return $updatedCount;
    }

    public function getTransactionsForExport(array $filters, ?array $selectedIds): Collection
    {
        $dateRange = $filters['date_range'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $status = $filters['status'] ?? null;
        $search = $filters['search'] ?? null;
        $type = $filters['type'] ?? null;

        $dates = $this->calculateDateRange($dateRange, $startDate, $endDate);
        $salesQuery = $this->buildSalesQuery($dates, $status, $search);
        $purchaseQuery = $this->buildPurchaseQuery($dates, $status, $search);

        $sales = collect();
        if (!$type || $type === 'sale') {
            $sales = $salesQuery->get()->map(fn($sale) => $this->transformSaleToTransaction($sale, true));
        }

        $purchases = collect();
        if (!$type || $type === 'purchase') {
            $purchases = $purchaseQuery->get()->map(fn($purchase) => $this->transformPurchaseToTransaction($purchase, true));
        }

        $transactions = $sales->merge($purchases)->sortByDesc('date');

        if ($selectedIds) {
            return $transactions->whereIn('id', $selectedIds);
        }

        return $transactions;
    }

    public function markTransactionAsPaid(int $id, string $type): array
    {
        if ($type === 'sale') {
            $sale = Sales::findOrFail($id);
            if ($sale->balance > 0) {
                $this->salesService->addPayment($sale, [
                    'amount' => $sale->balance,
                    'payment_date' => now(),
                    'payment_method' => 'Unknown', // Or fetch a default
                    'notes' => 'Marked as paid from recent transactions.',
                ]);
            } else {
                // If already paid (balance is 0), ensure status is 'Paid'
                $sale->update(['status' => 'Paid', 'payment_date' => now()]);
            }
        } elseif ($type === 'purchase') {
            $purchase = Purchase::findOrFail($id);
            if ($purchase->balance > 0) {
                $this->purchaseService->addPayment($purchase, [
                    'amount' => $purchase->balance,
                    'payment_date' => now(),
                    'payment_method' => 'Unknown', // Or fetch a default
                    'notes' => 'Marked as paid from recent transactions.',
                ]);
            } else {
                // If already paid (balance is 0), ensure status is 'Paid'
                $purchase->update(['status' => 'Paid', 'payment_date' => now()]);
            }
        } else {
            return ['success' => false, 'message' => 'Invalid transaction type.'];
        }

        return ['success' => true, 'message' => 'Transaction marked as paid successfully.'];
    }

    private function getTransactionsByIds(array $ids): Collection
    {
        $salesTransactions = Sales::whereIn('id', $ids)
            ->with('customer')
            ->get()
            ->map(fn($sale) => (object) [
                'id' => $sale->id,
                'type' => 'sale',
                'status' => $sale->status,
                'sale_id' => $sale->id,
                'purchase_id' => null
            ]);

        $purchaseTransactions = Purchase::whereIn('id', $ids)
            ->with('supplier')
            ->get()
            ->map(fn($purchase) => (object) [
                'id' => $purchase->id,
                'type' => 'purchase',
                'status' => $purchase->status,
                'sale_id' => null,
                'purchase_id' => $purchase->id
            ]);

        return $salesTransactions->concat($purchaseTransactions);
    }

    private function buildSalesQuery($dates, $status, $search)
    {
        return Sales::with('customer')
            ->when($dates['start'], fn($q) => $q->where('order_date', '>=', $dates['start']))
            ->when($dates['end'], fn($q) => $q->where('order_date', '<=', $dates['end']))
            ->when($status, fn($q, $status) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('invoice', 'LIKE', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery
                                ->where('name', 'LIKE', "%{$search}%")
                                ->orWhere('address', 'LIKE', "%{$search}%")
                                ->orWhere('payment_terms', 'LIKE', "%{$search}%")
                                ->orWhere('phone_number', 'LIKE', "%{$search}%");
                        });
                });
            });
    }

    private function buildPurchaseQuery($dates, $status, $search)
    {
        return Purchase::with('supplier')
            ->when($dates['start'], fn($q) => $q->where('order_date', '>=', $dates['start']))
            ->when($dates['end'], fn($q) => $q->where('order_date', '<=', $dates['end']))
            ->when($status, fn($q, $status) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('invoice', 'LIKE', "%{$search}%")
                        ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                            $supplierQuery
                                ->where('name', 'LIKE', "%{$search}%")
                                ->orWhere('code', 'LIKE', "%{$search}%")
                                ->orWhere('location', 'LIKE', "%{$search}%")
                                ->orWhere('payment_terms', 'LIKE', "%{$search}%")
                                ->orWhere('address', 'LIKE', "%{$search}%")
                                ->orWhere('phone_number', 'LIKE', "%{$search}%");
                        });
                });
            });
    }

    private function transformSaleToTransaction(Sales $sale, bool $forExport = false)
    {
        $paidAmount = $this->calculatePaidAmount($sale);
        $dueAmount = max(0, $sale->total - $paidAmount);
        $data = [
            'id' => $sale->id,
            'type' => 'sale',
            'invoice' => $sale->invoice,
            'customer_supplier' => $sale->customer->name ?? 'Unknown Customer',
            'contact_info' => $sale->customer->email ?? ($sale->customer->phone ?? null),
            'date' => $sale->order_date,
            'amount' => $sale->total,
            'paid_amount' => $paidAmount,
            'due_amount' => $dueAmount,
            'status' => $this->getPaymentStatus($sale->status, $paidAmount, $sale->total),
            'view_url' => route('admin.sales.view', $sale->id),
            'edit_url' => route('admin.sales.edit', $sale->id),
        ];
        return $forExport ? $data : new TransactionDTO($data);
    }

    private function transformPurchaseToTransaction(Purchase $purchase, bool $forExport = false)
    {
        $paidAmount = $this->calculatePaidAmount($purchase);
        $dueAmount = max(0, $purchase->total - $paidAmount);
        $data = [
            'id' => $purchase->id,
            'type' => 'purchase',
            'invoice' => $purchase->invoice,
            'customer_supplier' => $purchase->supplier->name ?? 'Unknown Supplier',
            'contact_info' => $purchase->supplier->email ?? ($purchase->supplier->phone ?? null),
            'date' => $purchase->order_date,
            'amount' => $purchase->total,
            'paid_amount' => $paidAmount,
            'due_amount' => $dueAmount,
            'status' => $this->getPaymentStatus($purchase->status, $paidAmount, $purchase->total),
            'view_url' => route('admin.po.view', $purchase->id),
            'edit_url' => route('admin.po.edit', $purchase->id),
        ];
        return $forExport ? $data : new TransactionDTO($data);
    }

    private function sortTransactions(Collection $transactions, $sort, $direction): Collection
    {
        $transactions = $transactions->sortBy(function ($transaction) use ($sort) {
            $sortKey = is_array($transaction) ? 'date' : 'date';
            if (is_array($transaction) && isset($transaction[$sort])) {
                $sortKey = $sort;
            } elseif (is_object($transaction) && isset($transaction->{$sort})) {
                $sortKey = $sort;
            }
            return is_array($transaction) ? $transaction[$sortKey] : $transaction->{$sortKey};
        });

        if ($direction === 'desc') {
            $transactions = $transactions->reverse();
        }

        return $transactions;
    }

    private function paginateTransactions(Collection $transactions, int $perPage): LengthAwarePaginator
    {
        $currentPage = Paginator::resolveCurrentPage();
        $currentItems = $transactions->slice(($currentPage - 1) * $perPage, $perPage)->values();
        return new LengthAwarePaginator($currentItems, $transactions->count(), $perPage, $currentPage, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }

    private function calculateDateRange(?string $dateRange, ?string $startDate, ?string $endDate): array
    {
        $dates = ['start' => null, 'end' => null];

        switch ($dateRange) {
            case 'today':
                $dates['start'] = Carbon::today()->startOfDay();
                $dates['end'] = Carbon::today()->endOfDay();
                break;
            case 'this_week':
                $dates['start'] = Carbon::now()->startOfWeek();
                $dates['end'] = Carbon::now()->endOfWeek();
                break;
            case 'this_month':
                $dates['start'] = Carbon::now()->startOfMonth();
                $dates['end'] = Carbon::now()->endOfMonth();
                break;
            case 'last_month':
                $dates['start'] = Carbon::now()->subMonth()->startOfMonth();
                $dates['end'] = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'custom':
                if ($startDate) {
                    $dates['start'] = Carbon::parse($startDate)->startOfDay();
                }
                if ($endDate) {
                    $dates['end'] = Carbon::parse($endDate)->endOfDay();
                }
                break;
        }

        return $dates;
    }

    private function calculatePaidAmount($transaction): float
    {
        if ($transaction->status === 'Paid') {
            return $transaction->total;
        }
        if ($transaction instanceof Sales && $transaction->status === 'Partial') {
            return $transaction->amount_received ?? 0;
        }
        return 0;
    }

    private function getPaymentStatus(string $status, ?float $paidAmount, ?float $totalAmount): string
    {
        if (in_array($status, ['Paid', 'Partial', 'Unpaid'])) {
            return $status;
        }

        if ($paidAmount !== null && $totalAmount !== null) {
            if ($paidAmount >= $totalAmount) {
                return 'Paid';
            }
            if ($paidAmount > 0) {
                return 'Partial';
            }
        }

        return 'Unpaid';
    }

    public function exportTransactions(array $filters, ?array $selectedIds)
    {
        $transactions = $this->getTransactionsForExport($filters, $selectedIds);

        $filename = 'transactions_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Type', 'Invoice', 'Customer/Supplier', 'Date', 'Amount', 'Paid Amount', 'Due Amount', 'Status']);

            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    ucfirst($transaction['type']),
                    $transaction['invoice'],
                    $transaction['customer_supplier'],
                    Carbon::parse($transaction['date'])->format('Y-m-d'),
                    $transaction['amount'],
                    $transaction['paid_amount'],
                    $transaction['due_amount'],
                    $transaction['status']
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bulkExportTransactions(array $ids, string $exportOption)
    {
        $transactions = $this->getTransactionsForExport([], $ids);

        if ($exportOption === 'pdf') {
            $html = view('admin.reports.recent-transactions-bulk-export-pdf', compact('transactions'))->render();
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            $output = $dompdf->output();
            return response()->streamDownload(function () use ($output) {
                echo $output;
            }, 'transactions.pdf');
        }

        if ($exportOption === 'csv') {
            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=transactions.csv',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $callback = function () use ($transactions) {
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
                        \App\Helpers\CurrencyHelper::format($transaction['amount']),
                        $transaction['status'],
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return null;
    }
}
