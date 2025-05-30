<?php
namespace App\Http\Controllers\Admin;

use App\Helpers\CurrencyHelper;
use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Sales;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

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

    private function getTransactions($dates, $type = null, $status = null, $search = null, $sort = 'date', $direction = 'desc', $perPage = 25)
    {
        $salesQuery = Sales::with('customer')
            ->when($dates['start'], fn($q) => $q->where('order_date', '>=', $dates['start']))
            ->when($dates['end'], fn($q) => $q->where('order_date', '<=', $dates['end']))
            ->when($status, function ($q) use ($status) {
                if ($status === 'Paid') {
                    $q->where('status', 'Paid');
                } elseif ($status === 'Partial') {
                    $q->where('status', 'Partial');
                } elseif ($status === 'Unpaid') {
                    $q->where('status', 'Unpaid');
                }
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('invoice', 'LIKE', "%{$search}%")->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery
                            ->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('address', 'LIKE', "%{$search}%")
                            ->orWhere('payment_terms', 'LIKE', "%{$search}%")
                            ->orWhere('phone_number', 'LIKE', "%{$search}%");
                    });
                });
            });

        $purchaseQuery = Purchase::with('supplier')
            ->when($dates['start'], fn($q) => $q->where('order_date', '>=', $dates['start']))
            ->when($dates['end'], fn($q) => $q->where('order_date', '<=', $dates['end']))
            ->when($status, function ($q) use ($status) {
                if ($status === 'Paid') {
                    $q->where('status', 'Paid');
                } elseif ($status === 'Partial') {
                    $q->where('status', 'Partial');
                } elseif ($status === 'Unpaid') {
                    $q->where('status', 'Unpaid');
                }
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('invoice', 'LIKE', "%{$search}%")->orWhereHas('supplier', function ($supplierQuery) use ($search) {
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

        // Get collections and transform them to objects with proper keys
        $sales = collect();
        $purchases = collect();

        if (!$type || $type === 'sale') {
            $sales = $salesQuery->get()->map(function ($sale) {
                $paidAmount = $this->calculatePaidAmount($sale);
                $dueAmount = max(0, $sale->total - $paidAmount);

                // Create a custom object that implements the required methods
                return new class ($sale->id, [
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
                ]) {
                    private $key;
                    private $attributes;

                    public function __construct($key, $attributes)
                    {
                        $this->key = $key;
                        $this->attributes = $attributes;
                    }

                    public function __get($name)
                    {
                        return $this->attributes[$name] ?? null;
                    }

                    public function __isset($name)
                    {
                        return isset($this->attributes[$name]);
                    }

                    public function getKey()
                    {
                        return $this->key;
                    }

                    public function toArray()
                    {
                        return $this->attributes;
                    }

                    public function __toString()
                    {
                        return (string) $this->key;
                    }
                };
            });
        }

        if (!$type || $type === 'purchase') {
            $purchases = $purchaseQuery->get()->map(function ($purchase) {
                $paidAmount = $this->calculatePaidAmount($purchase);
                $dueAmount = max(0, $purchase->total - $paidAmount);

                // Create a custom object that implements the required methods
                return new class ($purchase->id, [
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
                ]) {
                    private $key;
                    private $attributes;

                    public function __construct($key, $attributes)
                    {
                        $this->key = $key;
                        $this->attributes = $attributes;
                    }

                    public function __get($name)
                    {
                        return $this->attributes[$name] ?? null;
                    }

                    public function __isset($name)
                    {
                        return isset($this->attributes[$name]);
                    }

                    public function getKey()
                    {
                        return $this->key;
                    }

                    public function toArray()
                    {
                        return $this->attributes;
                    }

                    public function __toString()
                    {
                        return (string) $this->key;
                    }
                };
            });
        }

        // Combine and sort
        $transactions = $sales->merge($purchases);

        // Sort transactions
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

        // Paginate
        $currentPage = Paginator::resolveCurrentPage();
        $transactionsForPage = $transactions->forPage($currentPage, $perPage);

        return new LengthAwarePaginator($transactionsForPage->values()->all(), $transactions->count(), $perPage, $currentPage, [
            'path' => request()->url(),
            'pageName' => 'page',
        ]);
    }

    private function getTransactionsSummary($dates, $type = null, $status = null, $search = null)
    {
        $salesQuery = Sales::query()
            ->when($dates['start'], fn($q) => $q->where('order_date', '>=', $dates['start']))
            ->when($dates['end'], fn($q) => $q->where('order_date', '<=', $dates['end']))
            ->when($status, function ($q) use ($status) {
                if ($status === 'Paid') {
                    $q->where('status', 'Paid');
                } elseif ($status === 'Partial') {
                    $q->where('status', 'Partial');
                } elseif ($status === 'Unpaid') {
                    $q->where('status', 'Unpaid');
                }
            })
            ->when($search && (!$type || $type === 'sale'), function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('invoice', 'LIKE', "%{$search}%")->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'LIKE', "%{$search}%");
                    });
                });
            });

        $purchaseQuery = Purchase::query()
            ->when($dates['start'], fn($q) => $q->where('order_date', '>=', $dates['start']))
            ->when($dates['end'], fn($q) => $q->where('order_date', '<=', $dates['end']))
            ->when($status, function ($q) use ($status) {
                if ($status === 'Paid') {
                    $q->where('status', 'Paid');
                } elseif ($status === 'Partial') {
                    $q->where('status', 'Partial');
                } elseif ($status === 'Unpaid') {
                    $q->where('status', 'Unpaid');
                }
            })
            ->when($search && (!$type || $type === 'purchase'), function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('invoice', 'LIKE', "%{$search}%")->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                        $supplierQuery->where('name', 'LIKE', "%{$search}%");
                    });
                });
            });

        $salesStats = null;
        $purchaseStats = null;

        if (!$type || $type === 'sale') {
            $salesStats = $salesQuery
                ->selectRaw(
                    '
                COUNT(*) as count,
                SUM(total) as amount,
                SUM(CASE WHEN status = "Paid" THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status = "Paid" THEN total ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status IN ("Unpaid", "Partial") THEN 1 ELSE 0 END) as unpaid_count,
                SUM(CASE WHEN status IN ("Unpaid", "Partial") THEN total ELSE 0 END) as unpaid_amount
            ',
                )
                ->first();
        }

        if (!$type || $type === 'purchase') {
            $purchaseStats = $purchaseQuery
                ->selectRaw(
                    '
                COUNT(*) as count,
                SUM(total) as amount,
                SUM(CASE WHEN status = "Paid" THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status = "Paid" THEN total ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status IN ("Unpaid", "Partial") THEN 1 ELSE 0 END) as unpaid_count,
                SUM(CASE WHEN status IN ("Unpaid", "Partial") THEN total ELSE 0 END) as unpaid_amount
            ',
                )
                ->first();
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
        // For Sales model
        if ($transaction instanceof Sales) {
            if ($transaction->status === 'Paid') {
                return $transaction->total;
            } elseif ($transaction->status === 'Partial') {
                // If you have amount_received field for partial payments
                return $transaction->amount_received ?? 0;
            }
            return 0;
        }

        // For Purchase model
        if ($transaction instanceof Purchase) {
            if ($transaction->status === 'Paid') {
                return $transaction->total;
            } elseif ($transaction->status === 'Partial') {
                // You might need to add a paid_amount field to Purchase model
                // or calculate from payment records
                return 0; // Adjust based on your payment tracking logic
            }
            return 0;
        }

        return 0;
    }

    private function getPaymentStatus($status, $paidAmount = null, $totalAmount = null)
    {
        // If status is already set correctly in the model
        if (in_array($status, ['Paid', 'Partial', 'Unpaid'])) {
            return $status;
        }

        // Fallback logic if needed
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

            // For sales, also update amount_received if it exists
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
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error updating transaction: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    private function exportTransactions(Request $request, $dates, $type = null, $status = null, $search = null)
    {
        // Get all transactions without pagination for export
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

            // Add CSV headers
            fputcsv($file, ['Type', 'Invoice', 'Customer/Supplier', 'Date', 'Amount', 'Paid Amount', 'Due Amount', 'Status']);

            // Add data rows
            foreach ($transactions as $transaction) {
                fputcsv($file, [ucfirst($transaction['type']), $transaction['invoice'], $transaction['customer_supplier'], Carbon::parse($transaction['date'])->format('Y-m-d'), $transaction['amount'], $transaction['paid_amount'], $transaction['due_amount'], $transaction['status']]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getTransactionsForExport($dates, $type = null, $status = null, $search = null)
    {
        // Similar to getTransactions but returns all results without pagination
        $salesQuery = Sales::with('customer')
            ->when($dates['start'], fn($q) => $q->where('order_date', '>=', $dates['start']))
            ->when($dates['end'], fn($q) => $q->where('order_date', '<=', $dates['end']))
            ->when($status, function ($q) use ($status) {
                if ($status === 'Paid') {
                    $q->where('status', 'Paid');
                } elseif ($status === 'Partial') {
                    $q->where('status', 'Partial');
                } elseif ($status === 'Unpaid') {
                    $q->where('status', 'Unpaid');
                }
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('invoice', 'LIKE', "%{$search}%")->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'LIKE', "%{$search}%");
                    });
                });
            });

        $purchaseQuery = Purchase::with('supplier')
            ->when($dates['start'], fn($q) => $q->where('order_date', '>=', $dates['start']))
            ->when($dates['end'], fn($q) => $q->where('order_date', '<=', $dates['end']))
            ->when($status, function ($q) use ($status) {
                if ($status === 'Paid') {
                    $q->where('status', 'Paid');
                } elseif ($status === 'Partial') {
                    $q->where('status', 'Partial');
                } elseif ($status === 'Unpaid') {
                    $q->where('status', 'Unpaid');
                }
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('invoice', 'LIKE', "%{$search}%")->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                        $supplierQuery->where('name', 'LIKE', "%{$search}%");
                    });
                });
            });

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
