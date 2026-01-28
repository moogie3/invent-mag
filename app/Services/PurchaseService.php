<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\POItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\PurchaseHelper;
use App\Helpers\CurrencyHelper;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Account;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Dompdf\Dompdf;

class PurchaseService
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function getPurchaseIndexData(array $filters, int $entries)
    {
        $query = Purchase::with(['items', 'supplier', 'user']);

        if (isset($filters['month']) && $filters['month']) {
            $query->whereMonth('order_date', $filters['month']);
            if (!isset($filters['year'])) { // If year is not specified, default to current year
                $query->whereYear('order_date', Carbon::now()->year);
            }
        }

        if (isset($filters['year']) && $filters['year']) {
            $query->whereYear('order_date', $filters['year']);
        }

        $pos = $query->paginate($entries);



        $totalinvoice = $pos->total();
        $items = POItem::all();

        $inCount = 0;
        $inCountamount = 0;
        $outCount = 0;
        $outCountamount = 0;
        $totalMonthly = 0;
        $paymentMonthly = 0;

        $allPurchases = Purchase::with('items', 'supplier')->get();
        foreach ($allPurchases as $p) {

            if ($p->supplier->location === 'IN') {
                $inCount++;
                if ($p->status === 'Unpaid') {
                    $inCountamount += $p->total_amount;
                }
            }

            if ($p->supplier->location === 'OUT') {
                $outCount++;
                if ($p->status === 'Unpaid') {
                    $outCountamount += $p->total_amount;
                }
            }

            if ($p->order_date->isCurrentMonth()) {
                $totalMonthly += $p->total_amount;
            }

            if ($p->status === 'Paid' && $p->order_date->isCurrentMonth()) {
                $paymentMonthly += $p->total_amount;
            }
        }

        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');

        return compact('inCountamount', 'outCountamount', 'pos', 'inCount', 'outCount', 'shopname', 'address', 'entries', 'totalinvoice', 'totalMonthly', 'paymentMonthly', 'items');
    }

    public function getPurchaseCreateData()
    {
        $pos = Purchase::all();
        $suppliers = Supplier::all();
        $products = Product::all();

        return compact('pos', 'suppliers', 'products');
    }

    public function getPurchaseEditData($id)
    {
        $pos = Purchase::with(['items', 'supplier', 'payments'])->find($id);

        if (!$pos) {
            return []; // Or throw an exception, depending on desired behavior
        }

        $suppliers = Supplier::all();
        $products = Product::all();
        $items = POItem::all();
        $isPaid = $pos->status == 'Paid';

        return compact('pos', 'suppliers', 'products', 'items', 'isPaid');
    }

    public function getPurchaseViewData($id)
    {
        $pos = Purchase::with(['items', 'supplier', 'payments'])->find($id);

        if (!$pos) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Purchase with ID {$id} not found.");
        }

        $suppliers = Supplier::all();
        $items = POItem::all();
        $summary = PurchaseHelper::calculateInvoiceSummary($pos->items->toArray(), $pos->discount_total, $pos->discount_total_type);
        $subtotal = $summary['subtotal'];
        $itemCount = $summary['itemCount'];
        $totalProductDiscount = $summary['totalProductDiscount'];
        $orderDiscount = $summary['orderDiscount'];
        $finalTotal = $summary['finalTotal'];

        return compact('pos', 'suppliers', 'items', 'itemCount', 'subtotal', 'orderDiscount', 'finalTotal', 'totalProductDiscount');
    }

    public function getPurchaseForModal($id)
    {
        return Purchase::with(['supplier', 'items.product', 'payments'])->findOrFail($id);
    }

    public function createPurchase(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $products = json_decode($data['products'], true);
            if (!$products || !is_array($products)) {
                throw new \Exception('Invalid product data');
            }

            /** @var \App\Models\Purchase $purchase */
            $purchase = Purchase::create([
                'invoice' => $data['invoice'],
                'supplier_id' => $data['supplier_id'],
                'user_id' => Auth::id(),
                'order_date' => $data['order_date'],
                'due_date' => $data['due_date'],
                'discount_total' => $data['discount_total'] ?? 0,
                'discount_total_type' => $data['discount_total_type'] ?? 'fixed',
                'status' => 'Unpaid',
                'total' => 0, // Will be calculated after items are added
            ]);

            $totalAmount = 0;
            foreach ($products as $productData) {
                $itemTotal = PurchaseHelper::calculateTotal($productData['price'], $productData['quantity'], $productData['discount'] ?? 0, $productData['discount_type'] ?? 'fixed');
                POItem::create([
                    'po_id' => $purchase->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'remaining_quantity' => $productData['quantity'],
                    'price' => $productData['price'],
                    'discount' => $productData['discount'] ?? 0,
                    'discount_type' => $productData['discount_type'] ?? 'fixed',
                    'total' => $itemTotal,
                    'expiry_date' => $productData['expiry_date'] ?? null,
                ]);
                $totalAmount += $itemTotal;

                $product = Product::where('id', $productData['product_id'])
                                  ->where('tenant_id', $purchase->tenant_id)
                                  ->first();
                if ($product) {
                    $product->increment('stock_quantity', $productData['quantity']);
                }
            }

            $orderDiscount = PurchaseHelper::calculateDiscount($totalAmount, $purchase->discount_total, $purchase->discount_total_type);
            $finalTotal = $totalAmount - $orderDiscount;
            $purchase->update(['total' => $finalTotal]);

            // Get accounting settings from the user
            $accountingSettings = Auth::user()->accounting_settings;

            if (!$accountingSettings || !isset($accountingSettings['inventory_account_id']) || !isset($accountingSettings['accounts_payable_account_id'])) {
                throw new \Exception('Accounting settings for inventory or accounts payable are not configured.');
            }

            // Retrieve account names using the IDs from settings
            // Retrieve account names using the IDs from settings
            $inventoryAccount = Account::find($accountingSettings['inventory_account_id']);
            $accountsPayableAccount = Account::find($accountingSettings['accounts_payable_account_id']);

            if (!$inventoryAccount || !$accountsPayableAccount) {
                throw new \Exception('Required accounts (Inventory, Accounts Payable) not found. Please check Accounting Settings.');
            }

            $inventoryAccountName = $inventoryAccount->name;
            $accountsPayableAccountName = $accountsPayableAccount->name;

            // Create Journal Entry for the purchase
            $transactions = [
                ['account_name' => $inventoryAccountName, 'type' => 'debit', 'amount' => $finalTotal],
                ['account_name' => $accountsPayableAccountName, 'type' => 'credit', 'amount' => $finalTotal],
            ];

            $this->accountingService->createJournalEntry(
                "Purchase Order #{$purchase->invoice}",
                Carbon::parse($data['order_date']),
                $transactions,
                $purchase
            );

            return $purchase;
        });
    }

    public function updatePurchase(Purchase $purchase, array $data): Purchase
    {
        return DB::transaction(function () use ($purchase, $data) {
            $products = json_decode($data['products'], true);
            if (!$products || !is_array($products)) {
                throw new \Exception('Invalid product data');
            }

            // Revert old stock quantities before deleting items
            foreach ($purchase->items as $oldItem) {
                $product = Product::where('id', $oldItem->product_id)
                                  ->where('tenant_id', $purchase->tenant_id)
                                  ->first();
                if ($product) {
                    $product->decrement('stock_quantity', $oldItem->quantity);
                }
            }
            $purchase->items()->delete();

            // Update the main purchase details, excluding status for now
            $purchase->update([
                'invoice' => $data['invoice'],
                'supplier_id' => $data['supplier_id'],
                'order_date' => $data['order_date'],
                'due_date' => $data['due_date'],
                'discount_total' => $data['discount_total'] ?? 0,
                'discount_total_type' => $data['discount_total_type'] ?? 'fixed',
                'payment_type' => $data['payment_type'] ?? '-',
            ]);

            $totalAmount = 0;
            foreach ($products as $productData) {
                $itemTotal = PurchaseHelper::calculateTotal($productData['price'], $productData['quantity'], $productData['discount'] ?? 0, $productData['discount_type'] ?? 'fixed');
                POItem::create([
                    'po_id' => $purchase->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'remaining_quantity' => $productData['quantity'],
                    'price' => $productData['price'],
                    'discount' => $productData['discount'] ?? 0,
                    'discount_type' => $productData['discount_type'] ?? 'fixed',
                    'total' => $itemTotal,
                    'expiry_date' => $productData['expiry_date'] ?? null,
                ]);
                $totalAmount += $itemTotal;

                $product = Product::where('id', $productData['product_id'])
                                  ->where('tenant_id', $purchase->tenant_id)
                                  ->first();
                if ($product) {
                    $product->increment('stock_quantity', $productData['quantity']);
                }
            }

            $orderDiscount = PurchaseHelper::calculateDiscount($totalAmount, $purchase->discount_total, $purchase->discount_total_type);
            $finalTotal = $totalAmount - $orderDiscount;
            $purchase->update(['total' => $finalTotal]);

            // Refresh model to get grand_total attribute updated
            $purchase->refresh();

            // If status is changed to 'Paid' and there's a balance, create a payment.
            if (isset($data['status']) && $data['status'] === 'Paid' && $purchase->balance > 0) {
                $this->addPayment($purchase, [
                    'amount' => $purchase->balance,
                    'payment_date' => now(),
                    'payment_method' => $data['payment_type'] ?? 'Manual',
                    'notes' => 'Invoice manually marked as paid from edit page.',
                ]);
            } else {
                // For other status changes or if already paid, just update the status field if provided
                if (isset($data['status']) && $purchase->status !== $data['status']) {
                    $purchase->status = $data['status'];
                    $purchase->save();
                }
            }
            
            // Recalculate status based on payments at the end
            $this->updatePurchaseStatus($purchase);

            return $purchase;
        });
    }

    public function addPayment(Purchase $purchase, array $data): \App\Models\Payment
    {
        return DB::transaction(function () use ($purchase, $data) {
            $payment = $purchase->payments()->create([
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? null,
            ]);

            $purchase->load('payments'); // Refresh the payments relationship
            $this->updatePurchaseStatus($purchase);

            // Get accounting settings from the user
            $accountingSettings = Auth::user()->accounting_settings;

            if (!$accountingSettings || !isset($accountingSettings['accounts_payable_account_id']) || !isset($accountingSettings['cash_account_id'])) {
                throw new \Exception('Accounting settings for accounts payable or cash are not configured.');
            }

            // Retrieve account names using the IDs from settings
            // Retrieve account names using the IDs from settings
            $accountsPayableAccount = Account::find($accountingSettings['accounts_payable_account_id']);
            $cashAccount = Account::find($accountingSettings['cash_account_id']);

            if (!$accountsPayableAccount || !$cashAccount) {
                throw new \Exception('Required accounts (Accounts Payable, Cash) not found. Please check Accounting Settings.');
            }

            $accountsPayableAccountName = $accountsPayableAccount->name;
            $cashAccountName = $cashAccount->name;

            // Create Journal Entry for the payment
            $transactions = [
                ['account_name' => $accountsPayableAccountName, 'type' => 'debit', 'amount' => $data['amount']],
                ['account_name' => $cashAccountName, 'type' => 'credit', 'amount' => $data['amount']],
            ];

            $this->accountingService->createJournalEntry(
                "Payment for PO #{$purchase->invoice}",
                Carbon::parse($data['payment_date']),
                $transactions,
                $payment
            );

            return $payment;
        });
    }

    public function updatePurchaseStatus(Purchase $purchase)
    {
        $purchase->load('payments', 'items'); // Always get the latest payments
        $totalPaid = $purchase->payments->sum('amount');
        $grandTotal = $purchase->grand_total;

        if (round($totalPaid, 2) >= round($grandTotal, 2)) {
            $purchase->status = 'Paid';
        } elseif ($totalPaid > 0 && $totalPaid < $grandTotal) {
            $purchase->status = 'Partial';
        } else {
            $purchase->status = 'Unpaid';
        }

        $purchase->save();
    }

    public function deletePurchase(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            foreach ($purchase->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->decrement('stock_quantity', $item->quantity);
                }
                // Delete the POItem as well
                $item->delete();
            }
            $purchase->delete();
        });
    }

    public function bulkDeletePurchases(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            $purchases = Purchase::whereIn('id', $ids)->with('items')->get();
            foreach ($purchases as $purchase) {
                $this->deletePurchase($purchase);
            }
        });
    }

    public function bulkMarkPaid(array $ids)
    {
        $updatedCount = 0;
        DB::transaction(function () use ($ids, &$updatedCount) {
            $purchases = Purchase::whereIn('id', $ids)->with('payments', 'items')->get(); // Added with('items') // Added with('payments')
            foreach ($purchases as $purchase) {
                if ($purchase->balance > 0) {
                    $this->addPayment($purchase, [
                        'amount' => $purchase->balance,
                        'payment_date' => now(),
                        'payment_method' => 'Unknown',
                        'notes' => 'Bulk marked as paid.',
                    ]);
                }
                // Ensure the status is updated even if balance was 0
                $this->updatePurchaseStatus($purchase);
                $updatedCount++;
            }
        });
        return $updatedCount;
    }

    public function bulkExportPurchases(array $filters, ?array $ids, string $exportOption)
    {
        $query = Purchase::with(['items.product', 'supplier']);
        
        if ($ids) {
            $query->whereIn('id', $ids);
        } else {
            if (isset($filters['month']) && $filters['month']) {
                $query->whereMonth('order_date', $filters['month']);
            }
            if (isset($filters['year']) && $filters['year']) {
                $query->whereYear('order_date', $filters['year']);
            }
        }

        $purchases = $query->get();

        if ($exportOption === 'pdf') {
            $html = view('admin.po.bulk-export-pdf', compact('purchases'))->render();
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            return $dompdf->stream('purchase-orders.pdf');
        }

        if ($exportOption === 'csv') {
            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=purchase-orders.csv',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $callback = function () use ($purchases) {
                $file = fopen('php://output', 'w');
                fputcsv($file, [
                    'Invoice',
                    'Supplier',
                    'Order Date',
                    'Due Date',
                    'Total',
                    'Status',
                ]);

                foreach ($purchases as $purchase) {
                    fputcsv($file, [
                        $purchase->invoice,
                        $purchase->supplier->name,
                        $purchase->order_date->format('Y-m-d'),
                        $purchase->due_date->format('Y-m-d'),
                        CurrencyHelper::format($purchase->total_amount),
                        $purchase->status,
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return null;
    }

    public function printPo($id)
    {
        $pos = Purchase::with(['supplier', 'items.product', 'payments'])->findOrFail($id);
        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');

        $html = view('admin.po.print-pdf', compact('pos', 'shopname', 'address'))->render();
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->stream('po-' . $pos->invoice . '.pdf', ['Attachment' => false]);
    }

    public function getPurchaseMetrics()
    {
        $totalinvoice = Purchase::count();
        $inCount = 0;
        $inCountamount = 0;
        $outCount = 0;
        $outCountamount = 0;
        $totalMonthly = 0;
        $paymentMonthly = 0;

        $allPurchases = Purchase::with(['supplier', 'items'])->get();
        foreach ($allPurchases as $p) {
            if ($p->supplier->location === 'IN') {
                $inCount++;
                if ($p->status === 'Unpaid') {
                    $inCountamount += $p->total_amount;
                }
            }

            if ($p->supplier->location === 'OUT') {
                $outCount++;
                if ($p->status === 'Unpaid') {
                    $outCountamount += $p->total_amount;
                }
            }

            if ($p->order_date->isCurrentMonth()) {
                $totalMonthly += $p->total_amount;
            }

            if ($p->status === 'Paid' && $p->order_date->isCurrentMonth()) {
                $paymentMonthly += $p->total_amount;
            }
        }

        return [
            'totalinvoice' => $totalinvoice,
            'inCount' => $inCount,
            'inCountamount' => $inCountamount,
            'outCount' => $outCount,
            'outCountamount' => $outCountamount,
            'totalMonthly' => $totalMonthly,
            'paymentMonthly' => $paymentMonthly,
        ];
    }

    public function getExpiringPurchaseCount(): int
    {
        return Purchase::where('due_date', '<=', Carbon::now()->addDays(90))
                        ->where('status', '!=', 'Paid')
                        ->count();
    }

    public function getExpiringPurchases()
    {
        $expiringPurchases = Purchase::with('supplier')
            ->where('due_date', '<=', Carbon::now()->addDays(90))
            ->where('status', '!=', 'Paid')
            ->orderBy('due_date', 'asc')
            ->get();

        return $expiringPurchases->map(function ($purchase) {
            return [
                'id' => $purchase->id,
                'invoice' => $purchase->invoice,
                'supplier' => $purchase->supplier,
                'due_date' => Carbon::parse($purchase->due_date)->format('d M Y'),
                'total' => CurrencyHelper::format($purchase->total_amount),
            ];
        });
    }

    public function createPurchaseReturn(array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($data) {
            $purchase = Purchase::findOrFail($data['purchase_id']);
            $items = json_decode($data['items'], true);
            if (!$items || !is_array($items)) {
                throw new \Exception('Invalid items data');
            }

            // Filter for items that are actually being returned
            $returnedItems = array_filter($items, function($item) {
                return isset($item['returned_quantity']) && $item['returned_quantity'] > 0;
            });

            if (empty($returnedItems)) {
                throw new \Exception('No items selected for return.');
            }

            // Recalculate total amount on the backend to ensure data integrity
            $totalReturnAmount = 0;
            foreach ($returnedItems as $itemData) {
                $totalReturnAmount += ($itemData['return_price'] ?? $itemData['price']) * $itemData['returned_quantity'];
            }

            $purchaseReturn = PurchaseReturn::create([
                'purchase_id' => $purchase->id,
                'user_id' => Auth::id(),
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'total_amount' => $totalReturnAmount,
                'status' => $data['status'],
            ]);

            foreach ($returnedItems as $itemData) {
                $returnPrice = $itemData['return_price'] ?? $itemData['price'];
                $returnedQuantity = $itemData['returned_quantity'];

                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $returnedQuantity,
                    'price' => $returnPrice,
                    'total' => $returnPrice * $returnedQuantity,
                ]);

                // Decrement stock for the returned product
                $product = Product::find($itemData['product_id']);
                if ($product) {
                    $product->decrement('stock_quantity', $returnedQuantity);
                }
            }

            // Update original purchase status
            $totalPurchasedQuantity = $purchase->items->sum('quantity');
            $totalReturnedQuantity = $purchase->purchaseReturns()->with('items')->get()->flatMap->items->sum('quantity');


            if ($totalReturnedQuantity >= $totalPurchasedQuantity) {
                $purchase->update(['status' => 'Returned']);
            } elseif ($totalReturnedQuantity > 0) {
                $purchase->update(['status' => 'Partial']);
            }

            // Create Journal Entry for the return
            $accountingSettings = Auth::user()->accounting_settings;
            if (!$accountingSettings || !isset($accountingSettings['inventory_account_id']) || !isset($accountingSettings['accounts_payable_account_id'])) {
                throw new \Exception('Accounting settings for inventory or accounts payable are not configured.');
            }

            $inventoryAccount = Account::find($accountingSettings['inventory_account_id']);
            $accountsPayableAccount = Account::find($accountingSettings['accounts_payable_account_id']);

            if (!$inventoryAccount || !$accountsPayableAccount) {
                throw new \Exception('Required accounts (Inventory, Accounts Payable) not found for Purchase Return. Please check Accounting Settings.');
            }

            $inventoryAccountName = $inventoryAccount->name;
            $accountsPayableAccountName = $accountsPayableAccount->name;

            $transactions = [
                ['account_name' => $accountsPayableAccountName, 'type' => 'debit', 'amount' => $totalReturnAmount],
                ['account_name' => $inventoryAccountName, 'type' => 'credit', 'amount' => $totalReturnAmount],
            ];

            $this->accountingService->createJournalEntry(
                "Purchase Return for PO #{$purchase->invoice}",
                Carbon::parse($data['return_date']),
                $transactions,
                $purchaseReturn
            );

            return $purchaseReturn;
        });
    }
}
