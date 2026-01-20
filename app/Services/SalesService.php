<?php

namespace App\Services;

use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Product;
use App\Models\POItem;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\SalesHelper;
use App\Helpers\CurrencyHelper;
use App\Models\Customer;
use Carbon\Carbon;
use App\Models\Account;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use Dompdf\Dompdf;

class SalesService
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function getSalesIndexData(array $filters, int $entries)
    {
        $query = Sales::with(['product', 'customer', 'user']);

        if (isset($filters['month']) && $filters['month']) {
            $query->whereMonth('order_date', $filters['month']);
        }
        if (isset($filters['year']) && $filters['year']) {
            $query->whereYear('order_date', $filters['year']);
        }

        $sales = $query->paginate($entries);
        $totalinvoice = $query->count();
        $unpaidDebt = Sales::all()->where('status', 'Unpaid')->sum('total');
        $totalMonthly = Sales::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total');
        $pendingOrders = Sales::where('status', 'Unpaid')->count();
        $dueInvoices = Sales::where('status', 'Unpaid')
            ->whereDate('due_date', '>=', now())
            ->whereDate('due_date', '<=', now()->addDays(7))
            ->count();
        $posTotal = Sales::where('is_pos', true)->sum('total');
        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');

        return compact('posTotal', 'dueInvoices', 'entries', 'sales', 'totalinvoice', 'shopname', 'address', 'unpaidDebt', 'pendingOrders', 'totalMonthly');
    }

    public function getSalesCreateData()
    {
        $sales = Sales::all();
        $customers = Customer::all();
        $products = Product::all();
        $items = SalesItem::all();
        $tax = Tax::where('is_active', 1)->first();

        return compact('sales', 'customers', 'products', 'items', 'tax');
    }

    public function getSalesEditData($id)
    {
        $sales = Sales::with(['salesItems', 'customer', 'payments'])->find($id);
        $customers = Customer::all();
        $tax = Tax::where('is_active', 1)->first();
        $isPaid = $sales->status == 'Paid';

        return compact('sales', 'customers', 'tax', 'isPaid');
    }

    public function getSalesViewData($id)
    {
        /** @var \App\Models\Sales $sales */
        $sales = Sales::with(['salesItems', 'customer', 'payments'])->find($id);

        $customer = Customer::all();
        $tax = Tax::first();

        $itemCount = $sales->salesItems->count();
        $subtotal = 0;
        $totalItemDiscount = 0;

        foreach ($sales->salesItems as $item) {
            $itemSubtotal = $item->customer_price * $item->quantity;
            if ($item->discount_type === 'percentage') {
                $itemDiscountAmount = ($itemSubtotal * $item->discount) / 100;
            } else {
                $itemDiscountAmount = $item->discount * $item->quantity;
            }
            $totalItemDiscount += $itemDiscountAmount;
            $subtotal += ($itemSubtotal - $itemDiscountAmount);
        }

        $orderDiscount = 0;
        if ($sales->order_discount > 0) {
            if ($sales->order_discount_type === 'percentage') {
                $orderDiscount = ($subtotal * $sales->order_discount) / 100;
            } else {
                $orderDiscount = $sales->order_discount;
            }
        }

        $taxAmount = $sales->total_tax;
        $finalTotal = $sales->total;

        $summary = [
            'itemCount' => $itemCount,
            'subtotal' => $subtotal,
            'totalItemDiscount' => $totalItemDiscount,
            'orderDiscount' => $orderDiscount,
            'taxAmount' => $taxAmount,
            'finalTotal' => $finalTotal
        ];

        return compact(
            'sales',
            'customer',
            'tax',
            'summary',
            'itemCount',
            'subtotal',
            'orderDiscount',
            'finalTotal',
            'totalItemDiscount',
            'taxAmount'
        );
    }

    public function getSalesMetrics()
    {
        $totalinvoice = Sales::count();
        $unpaidDebt = Sales::where('status', 'Unpaid')->sum('total');
        $totalMonthly = Sales::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total');
        $pendingOrders = Sales::where('status', 'Unpaid')->count();
        $dueInvoices = Sales::where('status', 'Unpaid')
            ->whereDate('due_date', '>=', now())
            ->whereDate('due_date', '<=', now()->addDays(7))
            ->count();
        $posTotal = Sales::where('is_pos', true)->sum('total');

        return [
            'totalinvoice' => $totalinvoice,
            'unpaidDebt' => $unpaidDebt,
            'totalMonthly' => $totalMonthly,
            'pendingOrders' => $pendingOrders,
            'dueInvoices' => $dueInvoices,
            'posTotal' => $posTotal,
        ];
    }

    public function createSale(array $data): Sales
    {
        return DB::transaction(function () use ($data) {
            $products = json_decode($data['products'], true);
            if (!$products || !is_array($products)) {
                throw new \Exception('Invalid product data');
            }

            $subTotal = 0;
            foreach ($products as $product) {
                $subTotal += $product['customer_price'] * $product['quantity'];
            }

            $orderDiscount = $data['discount_total'] ?? 0;
            $orderDiscountType = $data['discount_total_type'] ?? 'fixed';
            $orderDiscountAmount = SalesHelper::calculateDiscount($subTotal, $orderDiscount, $orderDiscountType);

            $tax = Tax::where('is_active', 1)->first();
            $taxRate = $tax ? $tax->rate : 0;
            $taxAmount = SalesHelper::calculateTaxAmount($subTotal - $orderDiscountAmount, $taxRate);

            $grandTotal = ($subTotal - $orderDiscountAmount) + $taxAmount;

            $invoice = $data['invoice'] ?? $this->generateInvoiceNumber();

            $sale = Sales::create([
                'invoice' => $invoice,
                'customer_id' => $data['customer_id'],
                'user_id' => Auth::id(),
                'order_date' => $data['order_date'],
                'due_date' => $data['due_date'],
                'tax_rate' => $taxRate,
                'total_tax' => $taxAmount,
                'total' => $grandTotal,
                'order_discount' => $orderDiscountAmount,
                'order_discount_type' => $orderDiscountType,
                'status' => 'Unpaid',
                'payment_type' => '-',
                'is_pos' => false,
            ]);

            $totalCostOfGoods = 0;

            foreach ($products as $productData) {
                $product = Product::where('id', $productData['product_id'])
                                  ->where('tenant_id', $sale->tenant_id)
                                  ->first();
                if (!$product) {
                    throw new \Exception("Product with ID {$productData['product_id']} not found in this tenant.");
                }
                $totalCostOfGoods += $product->price * $productData['quantity'];

                SalesItem::create([
                    'sales_id' => $sale->id,
                    'product_id' => $productData['product_id'],
                    'name' => $product->name,
                    'quantity' => $productData['quantity'],
                    'customer_price' => $productData['customer_price'],
                    'discount' => $productData['discount'] ?? 0,
                    'discount_type' => $productData['discount_type'] ?? 'fixed',
                    'total' => SalesHelper::calculateTotal($productData['customer_price'], $productData['quantity'], $productData['discount'] ?? 0, $productData['discount_type'] ?? 'fixed'),
                ]);

                if ($product) {
                    $product->decrement('stock_quantity', $productData['quantity']);

                    // FEFO Logic
                    $poItems = POItem::where('product_id', $product->id)
                        ->where('remaining_quantity', '>', 0)
                        ->orderBy('expiry_date', 'asc')
                        ->get();

                    $quantityToDecrement = $productData['quantity'];

                    foreach ($poItems as $poItem) {
                        if ($quantityToDecrement <= 0) {
                            break;
                        }

                        $decrement = min($poItem->remaining_quantity, $quantityToDecrement);
                        $poItem->decrement('remaining_quantity', $decrement);
                        $quantityToDecrement -= $decrement;
                    }
                }
            }

            // Get accounting settings from the user
            $accountingSettings = Auth::user()->accounting_settings;

            // Retrieve account names using the IDs from settings
            $salesRevenueAccountName = Account::find($accountingSettings['sales_revenue_account_id'])->name;
            $accountsReceivableAccountName = Account::find($accountingSettings['accounts_receivable_account_id'])->name;
            $costOfGoodsSoldAccountName = Account::find($accountingSettings['cost_of_goods_sold_account_id'])->name;
            $inventoryAccountName = Account::find($accountingSettings['inventory_account_id'])->name;

            // Create Journal Entry for the sale
            $transactions = [
                ['account_name' => $accountsReceivableAccountName, 'type' => 'debit', 'amount' => $grandTotal],
                ['account_name' => $salesRevenueAccountName, 'type' => 'credit', 'amount' => $grandTotal],
                ['account_name' => $costOfGoodsSoldAccountName, 'type' => 'debit', 'amount' => $totalCostOfGoods],
                ['account_name' => $inventoryAccountName, 'type' => 'credit', 'amount' => $totalCostOfGoods],
            ];

            $this->accountingService->createJournalEntry(
                "Sale Invoice #{$sale->invoice}",
                Carbon::parse($data['order_date']),
                $transactions,
                $sale
            );

            return $sale;
        });
    }

    public function updateSale(Sales $sale, array $data): Sales
    {
        return DB::transaction(function () use ($sale, $data) {
            $products = json_decode($data['products'], true);
            if (!$products || !is_array($products)) {
                throw new \Exception('Invalid product data');
            }

            // Revert old stock quantities
            foreach ($sale->salesItems as $oldItem) {
                $product = Product::where('id', $oldItem->product_id)
                                  ->where('tenant_id', $sale->tenant_id)
                                  ->first();
                if ($product) {
                    $product->increment('stock_quantity', $oldItem->quantity);

                    // Revert remaining quantity
                    $poItems = POItem::where('product_id', $product->id)
                        ->orderBy('expiry_date', 'desc')
                        ->get();

                    $quantityToIncrement = $oldItem->quantity;

                    foreach ($poItems as $poItem) {
                        if ($quantityToIncrement <= 0) {
                            break;
                        }

                        $increment = min($poItem->quantity - $poItem->remaining_quantity, $quantityToIncrement);
                        $poItem->increment('remaining_quantity', $increment);
                        $quantityToIncrement -= $increment;
                    }
                }
            }

            $sale->salesItems()->delete();

            $subTotal = 0;
            $subtotalBeforeDiscounts = 0;
            foreach ($products as $productData) {
                $subTotal += SalesHelper::calculateTotal($productData['customer_price'], $productData['quantity'], $productData['discount'] ?? 0, $productData['discount_type'] ?? 'fixed');
                $subtotalBeforeDiscounts += $productData['customer_price'] * $productData['quantity'];
            }

            $orderDiscount = $data['order_discount'] ?? 0;
            $orderDiscountType = $data['order_discount_type'] ?? 'fixed';
            $orderDiscountAmount = SalesHelper::calculateDiscount($subtotalBeforeDiscounts, $orderDiscount, $orderDiscountType);

            $tax = Tax::where('is_active', 1)->first();
            $taxRate = $tax ? $tax->rate : 0;
            $taxAmount = SalesHelper::calculateTaxAmount($subTotal - $orderDiscountAmount, $taxRate);

            $grandTotal = ($subTotal - $orderDiscountAmount) + $taxAmount;

            $sale->update([
                'customer_id' => $data['customer_id'],
                'order_date' => $data['order_date'],
                'due_date' => $data['due_date'],
                'tax_rate' => $taxRate,
                'total_tax' => $taxAmount,
                'total' => $grandTotal,
                'order_discount' => $orderDiscount,
                'order_discount_type' => $orderDiscountType,
                'payment_type' => $data['payment_type'] ?? '-',
            ]);

            foreach ($products as $productData) {
                $product = Product::where('id', $productData['product_id'])
                                  ->where('tenant_id', $sale->tenant_id)
                                  ->first();
                if (!$product) continue;

                SalesItem::create([
                    'sales_id' => $sale->id,
                    'product_id' => $productData['product_id'],
                    'name' => $product->name,
                    'quantity' => $productData['quantity'],
                    'customer_price' => $productData['customer_price'],
                    'discount' => $productData['discount'] ?? 0,
                    'discount_type' => $productData['discount_type'] ?? 'fixed',
                    'total' => SalesHelper::calculateTotal($productData['customer_price'], $productData['quantity'], $productData['discount'] ?? 0, $productData['discount_type'] ?? 'fixed'),
                ]);

                $product->decrement('stock_quantity', $productData['quantity']);

                $poItems = POItem::where('product_id', $product->id)
                    ->where('remaining_quantity', '>', 0)
                    ->orderBy('expiry_date', 'asc')
                    ->get();
                $quantityToDecrement = $productData['quantity'];
                foreach ($poItems as $poItem) {
                    if ($quantityToDecrement <= 0) break;
                    $decrement = min($poItem->remaining_quantity, $quantityToDecrement);
                    $poItem->decrement('remaining_quantity', $decrement);
                    $quantityToDecrement -= $decrement;
                }
            }

            $sale->refresh();

            if (isset($data['status']) && $data['status'] === 'Paid' && $sale->balance > 0) {
                $this->addPayment($sale, [
                    'amount' => $sale->balance,
                    'payment_date' => now(),
                    'payment_method' => $data['payment_type'] ?? 'Manual',
                    'notes' => 'Invoice manually marked as paid from edit page.',
                ]);
            } else {
                if (isset($data['status']) && $sale->status !== $data['status']) {
                    $sale->status = $data['status'];
                    $sale->save();
                }
            }

            $this->updateSaleStatus($sale);

            return $sale;
        });
    }

    public function addPayment(Sales $sale, array $data): \App\Models\Payment
    {
        return DB::transaction(function () use ($sale, $data) {
            $payment = $sale->payments()->create([
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? null,
            ]);

            $sale->load('payments'); // Refresh the payments relationship
            $this->updateSaleStatus($sale);
            $sale->refresh();

            // Get accounting settings from the user
            $accountingSettings = Auth::user()->accounting_settings;

            if (!$accountingSettings || !isset($accountingSettings['cash_account_id']) || !isset($accountingSettings['accounts_receivable_account_id'])) {
                throw new \Exception('Accounting settings for cash or accounts receivable are not configured.');
            }

            // Retrieve account names using the IDs from settings
            $cashAccountName = Account::find($accountingSettings['cash_account_id'])->name;
            $accountsReceivableAccountName = Account::find($accountingSettings['accounts_receivable_account_id'])->name;

            // Create Journal Entry for the payment
            $transactions = [
                ['account_name' => $cashAccountName, 'type' => 'debit', 'amount' => $data['amount']],
                ['account_name' => $accountsReceivableAccountName, 'type' => 'credit', 'amount' => $data['amount']],
            ];

            $this->accountingService->createJournalEntry(
                "Payment for Sale #{$sale->invoice}",
                Carbon::parse($data['payment_date']),
                $transactions,
                $payment
            );

            return $payment;
        });
    }

    public function updateSaleStatus(Sales $sale)
    {
        $totalPaid = $sale->total_paid;
        $grandTotal = $sale->total;

        if ($totalPaid >= $grandTotal) {
            $sale->status = 'Paid';
        } elseif ($totalPaid > 0 && $totalPaid < $grandTotal) {
            $sale->status = 'Partial';
        } else {
            $sale->status = 'Unpaid';
        }

        $sale->save();
    }

    public function deleteSale(Sales $sale): void
    {
        DB::transaction(function () use ($sale) {
            // Note: Proper accounting for deletions would require a reversing journal entry.
            // This is left as a future improvement.

            foreach ($sale->salesItems as $item) {
                $product = Product::where('id', $item->product_id)
                                  ->where('tenant_id', $sale->tenant_id)
                                  ->first();
                if ($product) {
                    $product->increment('stock_quantity', $item->quantity);

                    // Revert remaining quantity
                    $poItems = POItem::where('product_id', $product->id)
                        ->orderBy('expiry_date', 'desc')
                        ->get();

                    $quantityToIncrement = $item->quantity;

                    foreach ($poItems as $poItem) {
                        if ($quantityToIncrement <= 0) {
                            break;
                        }

                        $increment = min($poItem->quantity - $poItem->remaining_quantity, $quantityToIncrement);
                        $poItem->increment('remaining_quantity', $increment);
                        $quantityToIncrement -= $increment;
                    }
                }
            }
            $sale->delete();
        });
    }

    public function bulkDeleteSales(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            $sales = Sales::whereIn('id', $ids)->with('salesItems')->get();
            foreach ($sales as $sale) {
                $this->deleteSale($sale);
            }
        });
    }

    public function bulkMarkPaid(array $ids): int
    {
        $updatedCount = 0;
        DB::transaction(function () use ($ids, &$updatedCount) {
            $sales = Sales::whereIn('id', $ids)->with('payments')->get(); // Added with('payments')
            foreach ($sales as $sale) {
                if ($sale->balance > 0) {
                    $this->addPayment($sale, [
                        'amount' => $sale->balance,
                        'payment_date' => now(),
                        'payment_method' => 'Unknown',
                        'notes' => 'Bulk marked as paid.',
                    ]);
                }
                // Ensure the status is updated even if balance was 0
                $this->updateSaleStatus($sale);
                $updatedCount++;
            }
        });
        return $updatedCount;
    }

    public function bulkExportSales(array $ids, string $exportOption)
    {
        $sales = Sales::with(['customer', 'salesItems'])->whereIn('id', $ids)->get();

        if ($exportOption === 'pdf') {
            $html = view('admin.sales.bulk-export-pdf', compact('sales'))->render();
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            return $dompdf->stream('sales-orders.pdf');
        }

        if ($exportOption === 'csv') {
            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=sales-orders.csv',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $callback = function () use ($sales) {
                $file = fopen('php://output', 'w');
                fputcsv($file, [
                    'Invoice',
                    'Customer',
                    'Order Date',
                    'Due Date',
                    'Total',
                    'Status',
                ]);

                foreach ($sales as $sale) {
                    fputcsv($file, [
                        $sale->invoice,
                        $sale->customer->name,
                        $sale->order_date->format('Y-m-d'),
                        $sale->due_date->format('Y-m-d'),
                        CurrencyHelper::format($sale->total),
                        $sale->status,
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return null;
    }

    public function getSalesForModal($id)
    {
        return Sales::with(['customer', 'salesItems.product', 'payments'])->findOrFail($id);
    }

    public function getPastCustomerPriceForProduct(Customer $customer, Product $product)
    {
        $latestSale = Sales::where('customer_id', $customer->id)
            ->whereHas('salesItems', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->latest()
            ->first();

        $pastPrice = 0;

            if ($latestSale) {
                $saleItem = $latestSale->salesItems()->where('product_id', $product->id)->first();
                if ($saleItem) {
                    $pastPrice = $saleItem->customer_price; // Removed floor()
                }
            }

        return $pastPrice;
    }

    private function generateInvoiceNumber(): string
    {
        $lastSalesInvoice = Sales::latest()->first();
        $invoiceNumber = 1;
        if ($lastSalesInvoice) {
            $lastNumber = (int) substr($lastSalesInvoice->invoice, 4);
            $invoiceNumber = $lastNumber + 1;
        }
        return 'INV-' . str_pad($invoiceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function getExpiringSalesCount(): int
    {
        return Sales::where('due_date', '<=', Carbon::now()->addDays(10))
                        ->where('status', '!=', 'Paid')
                        ->count();
    }

    public function getExpiringSales()
    {
        $expiringSales = Sales::with('customer')
            ->where('due_date', '<=', Carbon::now()->addDays(90))
            ->where('status', '!=', 'Paid')
            ->orderBy('due_date', 'asc')
            ->get();

        return $expiringSales->map(function ($sale) {
            return [
                'id' => $sale->id,
                'invoice' => $sale->invoice,
                'customer' => $sale->customer,
                'due_date' => Carbon::parse($sale->due_date)->format('d M Y'),
                'total' => CurrencyHelper::format($sale->total),
            ];
        });
    }

    public function createSalesReturn(array $data): SalesReturn
    {
        return DB::transaction(function () use ($data) {
            $sale = Sales::findOrFail($data['sales_id']);
            $items = json_decode($data['items'], true);
            if (!$items || !is_array($items)) {
                throw new \Exception('Invalid items data');
            }

            $totalReturnAmount = 0;
            foreach ($items as $itemData) {
                $totalReturnAmount += $itemData['price'] * $itemData['quantity'];
            }

            $salesReturn = SalesReturn::create([
                'sales_id' => $sale->id,
                'user_id' => Auth::id(),
                'return_date' => $data['return_date'],
                'reason' => $data['reason'],
                'total_amount' => $totalReturnAmount,
                'status' => $data['status'],
            ]);

            foreach ($items as $itemData) {
                SalesReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                    'total' => $itemData['price'] * $itemData['quantity'],
                ]);

                $product = Product::where('id', $itemData['product_id'])
                                  ->where('tenant_id', $sale->tenant_id)
                                  ->first();
                if ($product) {
                    $product->increment('stock_quantity', $itemData['quantity']);
                }
            }

            // Update original sales status
            $totalSoldQuantity = $sale->salesItems->sum('quantity');
            $totalReturnedQuantity = $sale->salesReturns->flatMap->items->sum('quantity');

            if ($totalReturnedQuantity >= $totalSoldQuantity) {
                $sale->update(['status' => 'Returned']);
            } else {
                $sale->update(['status' => 'Partial']);
            }

            // Create Journal Entry for the return
            $accountingSettings = Auth::user()->accounting_settings;
            if (!$accountingSettings || !isset($accountingSettings['inventory_account_id']) || !isset($accountingSettings['accounts_receivable_account_id'])) {
                throw new \Exception('Accounting settings for inventory or accounts receivable are not configured.');
            }

            $inventoryAccountName = Account::find($accountingSettings['inventory_account_id'])->name;
            $accountsReceivableAccountName = Account::find($accountingSettings['accounts_receivable_account_id'])->name;

            $transactions = [
                ['account_name' => $accountsReceivableAccountName, 'type' => 'credit', 'amount' => $totalReturnAmount],
                ['account_name' => $inventoryAccountName, 'type' => 'debit', 'amount' => $totalReturnAmount],
            ];

            $this->accountingService->createJournalEntry(
                "Sales Return for SO #{$sale->invoice}",
                Carbon::parse($data['return_date']),
                $transactions,
                $salesReturn
            );

            return $salesReturn;
        });
    }
}