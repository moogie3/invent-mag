<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Sales;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;

// Create a simple Transaction DTO class to replace anonymous classes
class TransactionDTO
{
    public $id;
    public $type;
    public $invoice;
    public $customer_supplier;
    public $contact_info;
    public $date;
    public $amount;
    public $paid_amount;
    public $due_amount;
    public $status;
    public $view_url;
    public $edit_url;

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getKey()
    {
        return $this->id;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }

    public function __toString()
    {
        return (string) $this->id;
    }
}

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 25);
        $type = $request->get('type');
        $status = $request->get('status');
        $dateRange = $request->get('date_range', 'all');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $search = $request->get('search');
        $sort = $request->get('sort', 'date');
        $direction = $request->get('direction', 'desc');

        // Calculate date range
        $dates = $this->calculateDateRange($dateRange, $startDate, $endDate);

        // Get transactions
        $transactions = $this->getTransactions($dates, $type, $status, $search, $sort, $direction, $perPage);

        // Get summary data
        $summary = $this->getTransactionsSummary($dates, $type, $status, $search);

        // Handle export request
        if ($request->get('export') === 'excel') {
            return $this->exportTransactions($request, $dates, $type, $status, $search);
        }

        return view('admin.recentts', compact('transactions', 'summary', 'type', 'status', 'dateRange', 'startDate', 'endDate', 'search'));
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

            $updatedCount = 0;
            $transactions = $this->getTransactionsByIds($transactionIds);

            foreach ($transactions as $transaction) {
                // Skip if already paid
                if ($transaction->status === 'Paid') {
                    continue;
                }

                if ($transaction->type === 'sale') {
                    $sale = Sales::find($transaction->sale_id);
                    if ($sale) {
                        $sale->update([
                            'status' => 'Paid',
                            'payment_date' => now(),
                            'amount_received' => $sale->total,
                            'change_amount' => 0
                        ]);
                        $updatedCount++;
                    }
                } elseif ($transaction->type === 'purchase') {
                    $purchase = Purchase::find($transaction->purchase_id);
                    if ($purchase) {
                        $purchase->update([
                            'status' => 'Paid',
                            'payment_date' => now()
                        ]);
                        $updatedCount++;
                    }
                }
            }

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

    private function getTransactionsByIds($ids)
    {
        $salesTransactions = Sales::whereIn('id', $ids)
            ->with('customer')
            ->get()
            ->map(function ($sale) {
                return (object) [
                    'id' => $sale->id,
                    'type' => 'sale',
                    'status' => $sale->status,
                    'sale_id' => $sale->id,
                    'purchase_id' => null
                ];
            });

        $purchaseTransactions = Purchase::whereIn('id', $ids)
            ->with('supplier')
            ->get()
            ->map(function ($purchase) {
                return (object) [
                    'id' => $purchase->id,
                    'type' => 'purchase',
                    'status' => $purchase->status,
                    'sale_id' => null,
                    'purchase_id' => $purchase->id
                ];
            });

        return $salesTransactions->concat($purchaseTransactions);
    }

    private function getTransactions($dates, $type = null, $status = null, $search = null, $sort = 'date', $direction = 'desc', $perPage = 25)
    {
        // Build base queries
        $salesQuery = $this->buildSalesQuery($dates, $status, $search);
        $purchaseQuery = $this->buildPurchaseQuery($dates, $status, $search);

        // Get collections and transform them
        $sales = collect();
        $purchases = collect();

        if (!$type || $type === 'sale') {
            $sales = $salesQuery->get()->map(function ($sale) {
                return $this->transformSaleToTransaction($sale);
            });
        }

        if (!$type || $type === 'purchase') {
            $purchases = $purchaseQuery->get()->map(function ($purchase) {
                return $this->transformPurchaseToTransaction($purchase);
            });
        }

        // Combine and sort
        $transactions = $sales->merge($purchases);
        $transactions = $this->sortTransactions($transactions, $sort, $direction);

        // Paginate
        return $this->paginateTransactions($transactions, $perPage);
    }

    private function buildSalesQuery($dates, $status, $search)
    {
        return Sales::with('customer')
            ->when($dates['start'], fn($q) => $q->where('order_date', '>=', $dates['start']))
            ->when($dates['end'], fn($q) => $q->where('order_date', '<=', $dates['end']))
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
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
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
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

    private function transformSaleToTransaction($sale): TransactionDTO
    {
        $paidAmount = $this->calculatePaidAmount($sale);
        $dueAmount = max(0, $sale->total - $paidAmount);

        return new TransactionDTO([
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
        ]);
    }

    private function transformPurchaseToTransaction($purchase): TransactionDTO
    {
        $paidAmount = $this->calculatePaidAmount($purchase);
        $dueAmount = max(0, $purchase->total - $paidAmount);

        return new TransactionDTO([
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
        ]);
    }

    private function sortTransactions($transactions, $sort, $direction)
    {
        $transactions = $transactions->sortBy(function ($transaction) use ($sort) {
            switch ($sort) {
                case 'type':
                    return $transaction->type;
                case 'invoice':
                    return $transaction->invoice;
                case 'customer_supplier':
                    return $transaction->customer_supplier;
                case 'amount':
                    return $transaction->amount;
                case 'status':
                    return $transaction->status;
                case 'date':
                default:
                    return $transaction->date;
            }
        });

        if ($direction === 'desc') {
            $transactions = $transactions->reverse();
        }

        return $transactions;
    }

    private function paginateTransactions($transactions, $perPage)
    {
        $currentPage = Paginator::resolveCurrentPage();
        $transactionsForPage = $transactions->forPage($currentPage, $perPage);

        return new LengthAwarePaginator(
            $transactionsForPage->values()->all(),
            $transactions->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    private function getTransactionsSummary($dates, $type = null, $status = null, $search = null)
    {
        $salesQuery = Sales::query()
            ->when($dates['start'], fn($q) => $q->where('order_date', '>=', $dates['start']))
            ->when($dates['end'], fn($q) => $q->where('order_date', '<=', $dates['end']))
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($search && (!$type || $type === 'sale'), function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('invoice', 'LIKE', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'LIKE', "%{$search}%");
                        });
                });
            });

        $purchaseQuery = Purchase::query()
            ->when($dates['start'], fn($q) => $q->where('order_date', '>=', $dates['start']))
            ->when($dates['end'], fn($q) => $q->where('order_date', '<=', $dates['end']))
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($search && (!$type || $type === 'purchase'), function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('invoice', 'LIKE', "%{$search}%")
                        ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                            $supplierQuery->where('name', 'LIKE', "%{$search}%");
                        });
                });
            });

        $salesStats = null;
        $purchaseStats = null;

        if (!$type || $type === 'sale') {
            $salesStats = $salesQuery->selectRaw('
                COUNT(*) as count,
                SUM(total) as amount,
                SUM(CASE WHEN status = "Paid" THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status = "Paid" THEN total ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status IN ("Unpaid", "Partial") THEN 1 ELSE 0 END) as unpaid_count,
                SUM(CASE WHEN status IN ("Unpaid", "Partial") THEN total ELSE 0 END) as unpaid_amount
            ')->first();
        }

        if (!$type || $type === 'purchase') {
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

    private function calculateDateRange($dateRange, $startDate = null, $endDate = null)
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

    private function calculatePaidAmount($transaction)
    {
        if ($transaction instanceof Sales) {
            if ($transaction->status === 'Paid') {
                return $transaction->total;
            } elseif ($transaction->status === 'Partial') {
                return $transaction->amount_received ?? 0;
            }
            return 0;
        }

        if ($transaction instanceof Purchase) {
            if ($transaction->status === 'Paid') {
                return $transaction->total;
            } elseif ($transaction->status === 'Partial') {
                return 0; // Adjust based on your payment tracking logic
            }
            return 0;
        }

        return 0;
    }

    private function getPaymentStatus($status, $paidAmount = null, $totalAmount = null)
    {
        if (in_array($status, ['Paid', 'Partial', 'Unpaid'])) {
            return $status;
        }

        if ($paidAmount !== null && $totalAmount !== null) {
            if ($paidAmount >= $totalAmount) {
                return 'Paid';
            } elseif ($paidAmount > 0) {
                return 'Partial';
            }
        }

        return 'Unpaid';
    }

    public function markAsPaid(Request $request, $id)
    {
        try {
            $type = $request->input('type');

            if ($type === 'sale') {
                $transaction = Sales::findOrFail($id);
            } else {
                $transaction = Purchase::findOrFail($id);
            }

            $transaction->update([
                'status' => 'Paid',
                'payment_date' => now(),
                'updated_at' => now(),
            ]);

            if ($type === 'sale') {
                $transaction->update([
                    'amount_received' => $transaction->total,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Transaction marked as paid successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating transaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function exportTransactions(Request $request, $dates, $type = null, $status = null, $search = null)
    {
        $transactions = $this->getTransactionsForExport($dates, $type, $status, $search);

        $selectedIds = $request->get('selected');
        if ($selectedIds) {
            $selectedIds = explode(',', $selectedIds);
            $transactions = $transactions->whereIn('id', $selectedIds);
        }

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

    private function getTransactionsForExport($dates, $type = null, $status = null, $search = null)
    {
        $salesQuery = $this->buildSalesQuery($dates, $status, $search);
        $purchaseQuery = $this->buildPurchaseQuery($dates, $status, $search);

        $sales = collect();
        $purchases = collect();

        if (!$type || $type === 'sale') {
            $sales = $salesQuery->get()->map(function ($sale) {
                $paidAmount = $this->calculatePaidAmount($sale);
                $dueAmount = max(0, $sale->total - $paidAmount);

                return [
                    'id' => $sale->id,
                    'type' => 'sale',
                    'invoice' => $sale->invoice,
                    'customer_supplier' => $sale->customer->name ?? 'Unknown Customer',
                    'date' => $sale->order_date,
                    'amount' => $sale->total,
                    'paid_amount' => $paidAmount,
                    'due_amount' => $dueAmount,
                    'status' => $this->getPaymentStatus($sale->status, $paidAmount, $sale->total),
                ];
            });
        }

        if (!$type || $type === 'purchase') {
            $purchases = $purchaseQuery->get()->map(function ($purchase) {
                $paidAmount = $this->calculatePaidAmount($purchase);
                $dueAmount = max(0, $purchase->total - $paidAmount);

                return [
                    'id' => $purchase->id,
                    'type' => 'purchase',
                    'invoice' => $purchase->invoice,
                    'customer_supplier' => $purchase->supplier->name ?? 'Unknown Supplier',
                    'date' => $purchase->order_date,
                    'amount' => $purchase->total,
                    'paid_amount' => $paidAmount,
                    'due_amount' => $dueAmount,
                    'status' => $this->getPaymentStatus($purchase->status, $paidAmount, $purchase->total),
                ];
            });
        }

        return $sales->merge($purchases)->sortByDesc('date');
    }
}
