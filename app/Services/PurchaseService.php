<?php

namespace App\Services;

use App\Helpers\CurrencyHelper;
use App\Helpers\PurchaseHelper;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\POItem;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function getPurchaseIndexData(array $filters, int $entries)
    {
        $tenantId = app('currentTenant')->id ?? null;
        $query = Purchase::with(['items', 'supplier', 'user', 'purchaseReturns']);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if (isset($filters['month']) && $filters['month']) {
            $query->whereMonth('order_date', $filters['month']);
            if (! isset($filters['year'])) { // If year is not specified, default to current year
                $query->whereYear('order_date', Carbon::now()->year);
            }
        }

        if (isset($filters['year']) && $filters['year']) {
            $query->whereYear('order_date', $filters['year']);
        }

        $pos = $query->paginate($entries);
        $pos->getCollection()->each->setAppends([]);

        // Load warehouse for filtering/display
        $pos->load('warehouse');

        $totalinvoice = $pos->total();

        // Use efficient DB queries to avoid loading all rows and relationships
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $aggQuery = DB::table('po')
            ->join('suppliers', 'po.supplier_id', '=', 'suppliers.id')
            ->selectRaw("
                SUM(CASE WHEN suppliers.location = 'IN' THEN 1 ELSE 0 END) as in_count,
                SUM(CASE WHEN suppliers.location = 'IN' AND po.status = 'Unpaid' THEN po.total ELSE 0 END) as in_unpaid_amount,
                SUM(CASE WHEN suppliers.location = 'OUT' THEN 1 ELSE 0 END) as out_count,
                SUM(CASE WHEN suppliers.location = 'OUT' AND po.status = 'Unpaid' THEN po.total ELSE 0 END) as out_unpaid_amount,
                SUM(CASE WHEN po.order_date >= ? AND po.order_date <= ? THEN po.total ELSE 0 END) as total_monthly,
                SUM(CASE WHEN po.status = 'Paid' AND po.order_date >= ? AND po.order_date <= ? THEN po.total ELSE 0 END) as payment_monthly
            ", [$monthStart, $monthEnd, $monthStart, $monthEnd]);

        if ($tenantId) {
            $aggQuery->where('po.tenant_id', $tenantId);
        }

        $aggregates = $aggQuery->first();

        $inCount = (int) ($aggregates->in_count ?? 0);
        $inCountamount = (float) ($aggregates->in_unpaid_amount ?? 0);
        $outCount = (int) ($aggregates->out_count ?? 0);
        $outCountamount = (float) ($aggregates->out_unpaid_amount ?? 0);
        $totalMonthly = (float) ($aggregates->total_monthly ?? 0);
        $paymentMonthly = (float) ($aggregates->payment_monthly ?? 0);

        // Fetch total payments made against unpaid/partial invoices to calculate true balance
        $paidAgainstIn = DB::table('payments')
            ->join('po', 'payments.paymentable_id', '=', 'po.id')
            ->join('suppliers', 'po.supplier_id', '=', 'suppliers.id')
            ->where('payments.paymentable_type', Purchase::class)
            ->whereIn('po.status', ['Unpaid', 'Partial'])
            ->where('suppliers.location', 'IN');

        $paidAgainstOut = clone $paidAgainstIn;
        $paidAgainstOut->where('suppliers.location', 'OUT');

        if ($tenantId) {
            $paidAgainstIn->where('po.tenant_id', $tenantId);
            $paidAgainstOut->where('po.tenant_id', $tenantId);
        }

        $inCountamount = max(0, $inCountamount - $paidAgainstIn->sum('payments.amount'));
        $outCountamount = max(0, $outCountamount - $paidAgainstOut->sum('payments.amount'));

        $shopname = User::where('tenant_id', $tenantId)->whereNotNull('shopname')->value('shopname');
        $address = User::where('tenant_id', $tenantId)->whereNotNull('address')->value('address');

        // Do not fetch POItem::all() just for frontend rendering; pass empty array if unused
        $items = [];

        return compact('inCountamount', 'outCountamount', 'pos', 'inCount', 'outCount', 'shopname', 'address', 'entries', 'totalinvoice', 'totalMonthly', 'paymentMonthly', 'items');
    }

    public function getPurchaseCreateData()
    {
        $suppliers = Supplier::select('id', 'name', 'email', 'phone_number', 'location')->get();
        $products = Product::select('id', 'name', 'code', 'price')->get()->each->setAppends(['main_warehouse_stock']);
        $warehouses = Warehouse::all();
        $taxes = Tax::active()->get();
        $defaultTax = Tax::default()->first() ?? Tax::active()->first();
        $pos = []; // Don't load all purchases

        return compact('pos', 'suppliers', 'products', 'warehouses', 'taxes', 'defaultTax');
    }

    public function getPurchaseEditData($id)
    {
        $pos = Purchase::with(['items', 'supplier', 'payments', 'purchaseReturns.items'])->find($id);

        if (! $pos) {
            return []; // Or throw an exception, depending on desired behavior
        }

        $suppliers = Supplier::all();
        $products = Product::all();
        $items = POItem::all();
        $isPaid = $pos->status == 'Paid';
        $warehouses = Warehouse::all();
        $taxes = Tax::active()->get();
        $defaultTax = Tax::default()->first() ?? Tax::active()->first();

        return compact('pos', 'suppliers', 'products', 'items', 'isPaid', 'warehouses', 'taxes', 'defaultTax');
    }

    public function getPurchaseViewData($id)
    {
        $pos = Purchase::with(['items', 'supplier', 'payments', 'purchaseReturns.items'])->find($id);

        if (! $pos) {
            throw new ModelNotFoundException("Purchase with ID {$id} not found.");
        }

        $suppliers = Supplier::all();
        $items = POItem::all();
        $summary = PurchaseHelper::calculateInvoiceSummaryWithReturns(
            $pos->items,
            $pos->discount_total,
            $pos->discount_total_type,
            $pos->relationLoaded('purchaseReturns') ? $pos->purchaseReturns : collect(),
            (float) ($pos->tax_rate ?? 0),
            $pos->tax_type ?? 'none'
        );
        $subtotal = $summary['subtotal'];
        $itemCount = $summary['itemCount'];
        $totalProductDiscount = $summary['totalProductDiscount'];
        $orderDiscount = $summary['orderDiscount'];
        $taxAmount = $summary['taxAmount'];
        $finalTotal = $summary['finalTotal'];

        return compact('pos', 'suppliers', 'items', 'itemCount', 'subtotal', 'orderDiscount', 'finalTotal', 'totalProductDiscount', 'taxAmount');
    }

    public function getPurchaseForModal($id)
    {
        return Purchase::with(['supplier', 'items.product', 'payments', 'purchaseReturns.items'])->findOrFail($id);
    }

    public function createPurchase(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $products = json_decode($data['products'], true);
            if (! $products || ! is_array($products)) {
                throw new \Exception('Invalid product data');
            }

            $taxId = $data['tax_id'] ?? null;
            $tax = $taxId ? Tax::find($taxId) : (Tax::default()->first() ?? Tax::active()->first());
            $taxRate = $tax ? (float) $tax->rate : 0;
            $taxType = $tax ? $tax->type : 'none';

            $pphTaxId = $data['pph_tax_id'] ?? null;
            $pphTax = $pphTaxId ? Tax::find($pphTaxId) : null;
            $pphTaxRate = $pphTax ? (float) $pphTax->rate : 0;

            $purchase = Purchase::create([
                'invoice' => $data['invoice'],
                'supplier_id' => $data['supplier_id'],
                'user_id' => Auth::id(),
                'warehouse_id' => $data['warehouse_id'],
                'order_date' => $data['order_date'],
                'due_date' => $data['due_date'],
                'discount_total' => $data['discount_total'] ?? 0,
                'discount_total_type' => $data['discount_total_type'] ?? 'fixed',
                'tax_id' => $tax?->id,
                'tax_rate' => $taxRate,
                'tax_type' => $taxType,
                'status' => 'Unpaid',
                'total' => 0,
                'total_tax' => 0,
            ]);

            $totalAmount = 0;
            foreach ($products as $productData) {
                $itemTotal = PurchaseHelper::calculateTotal($productData['price'], $productData['quantity'], $productData['discount'] ?? 0, $productData['discountType'] ?? 'fixed');
                POItem::create([
                    'po_id' => $purchase->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'remaining_quantity' => $productData['quantity'],
                    'price' => $productData['price'],
                    'discount' => $productData['discount'] ?? 0,
                    'discount_type' => $productData['discountType'] ?? 'fixed',
                    'total' => $itemTotal,
                    'expiry_date' => ! empty($productData['expiry_date']) ? $productData['expiry_date'] : null,
                ]);
                $totalAmount += $itemTotal;

                // Update Stock in Pivot Table
                $stockRecord = ProductWarehouse::firstOrCreate(
                    [
                        'product_id' => $productData['product_id'],
                        'warehouse_id' => $purchase->warehouse_id,
                        'tenant_id' => $purchase->tenant_id,
                    ],
                    ['quantity' => 0]
                );

                $stockRecord->increment('quantity', $productData['quantity']);
            }

            $orderDiscount = PurchaseHelper::calculateDiscount($totalAmount, $purchase->discount_total, $purchase->discount_total_type);
            $afterDiscount = $totalAmount - $orderDiscount;
            $taxAmount = PurchaseHelper::calculateTaxAmount($afterDiscount, $taxRate, $taxType);

            if ($taxType === 'inclusive') {
                $finalTotal = $afterDiscount;
            } else {
                $finalTotal = $afterDiscount + $taxAmount;
            }

            $pphAmount = PurchaseHelper::calculatePphAmount($afterDiscount, $pphTaxRate);
            $finalTotal = max(0, $finalTotal - $pphAmount);

            $isIndonesian = app('currentTenant')->isIndonesian();
            $ppnEnabled = $isIndonesian && ($tax?->is_ppn ?? false);
            $ppnAmount = $ppnEnabled ? $taxAmount : 0;

            $purchase->update([
                'total' => $finalTotal,
                'total_tax' => $taxAmount,
                'pph_enabled' => $isIndonesian && ($pphTax?->is_pph ?? false),
                'pph_rate' => $isIndonesian && ($pphTax?->is_pph ?? false) ? $pphTaxRate : null,
                'pph_amount' => $isIndonesian && ($pphTax?->is_pph ?? false) ? $pphAmount : 0,
                'pph_tax_id' => $isIndonesian && ($pphTax?->is_pph ?? false) ? $pphTax?->id : null,
                'ppn_enabled' => $ppnEnabled,
                'ppn_rate' => $ppnEnabled ? $taxRate : null,
                'ppn_amount' => $ppnAmount,
            ]);

            // Get accounting settings from tenant
            $accountingSettings = app('currentTenant')->accounting_settings ?? [];

            if (! $accountingSettings || ! isset($accountingSettings['inventory_account_id']) || ! isset($accountingSettings['accounts_payable_account_id'])) {
                throw new \Exception('Accounting settings for inventory or accounts payable are not configured.');
            }

            // Retrieve account names using the IDs from settings
            // Retrieve account names using the IDs from settings
            $inventoryAccount = Account::find($accountingSettings['inventory_account_id']);
            $accountsPayableAccount = Account::find($accountingSettings['accounts_payable_account_id']);

            if (! $inventoryAccount || ! $accountsPayableAccount) {
                throw new \Exception('Required accounts (Inventory, Accounts Payable) not found. Please check Accounting Settings.');
            }

            $inventoryAccountName = $inventoryAccount->name;
            $accountsPayableAccountName = $accountsPayableAccount->name;

            // Journal entry: Dr Inventory (net), Cr AP (net after PPh), Cr PPh Payable (withholding).
            // When PPN Masukan applies, PPN is debited to the Input VAT asset account (1160)
            // instead of capitalizing it into inventory cost.
            $inventoryAmount = $taxType === 'inclusive' ? $afterDiscount : ($afterDiscount + $taxAmount);

            $ppnMasukanAccountId = $accountingSettings['ppn_masukan_account_id'] ?? null;
            $ppnMasukanAccount = null;

            if ($ppnEnabled && $ppnAmount > 0 && $ppnMasukanAccountId) {
                $ppnMasukanAccount = Account::find($ppnMasukanAccountId);
                if ($ppnMasukanAccount) {
                    $inventoryAmount = $afterDiscount;
                }
            }

            $transactions = [
                ['account_name' => $inventoryAccountName, 'type' => 'debit', 'amount' => $inventoryAmount],
                ['account_name' => $accountsPayableAccountName, 'type' => 'credit', 'amount' => $finalTotal],
            ];

            if ($ppnMasukanAccount) {
                $transactions[] = ['account_name' => $ppnMasukanAccount->name, 'type' => 'debit', 'amount' => $ppnAmount];
            }

            if ($pphAmount > 0 && app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false)) {
                $pphPayableAccountId = $accountingSettings['pph_payable_account_id'] ?? null;
                if ($pphPayableAccountId) {
                    $pphPayableAccount = Account::find($pphPayableAccountId);
                    if ($pphPayableAccount) {
                        $transactions[] = ['account_name' => $pphPayableAccount->name, 'type' => 'credit', 'amount' => $pphAmount];
                    }
                }
            }

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
            if (! $products || ! is_array($products)) {
                throw new \Exception('Invalid product data');
            }

            // Revert old stock quantities before deleting items
            foreach ($purchase->items as $oldItem) {
                $stockRecord = ProductWarehouse::where('product_id', $oldItem->product_id)
                    ->where('warehouse_id', $purchase->warehouse_id)
                    ->where('tenant_id', $purchase->tenant_id)
                    ->first();

                if ($stockRecord) {
                    $stockRecord->decrement('quantity', $oldItem->quantity);
                }
            }
            $purchase->items()->delete();

            $taxId = $data['tax_id'] ?? null;
            $tax = $taxId ? Tax::find($taxId) : (Tax::default()->first() ?? Tax::active()->first());
            $taxRate = $tax ? (float) $tax->rate : 0;
            $taxType = $tax ? $tax->type : 'none';

            $pphTaxId = $data['pph_tax_id'] ?? null;
            $pphTax = $pphTaxId ? Tax::find($pphTaxId) : null;
            $pphTaxRate = $pphTax ? (float) $pphTax->rate : 0;

            // Update the main purchase details
            $purchase->update([
                'invoice' => $data['invoice'],
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'order_date' => $data['order_date'],
                'due_date' => $data['due_date'],
                'discount_total' => $data['discount_total'] ?? 0,
                'discount_total_type' => $data['discount_total_type'] ?? 'fixed',
                'payment_type' => $data['payment_type'] ?? '-',
                'tax_id' => $tax?->id,
                'tax_rate' => $taxRate,
                'tax_type' => $taxType,
            ]);

            $totalAmount = 0;
            foreach ($products as $productData) {
                $itemTotal = PurchaseHelper::calculateTotal($productData['price'], $productData['quantity'], $productData['discount'] ?? 0, $productData['discountType'] ?? 'fixed');
                POItem::create([
                    'po_id' => $purchase->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'remaining_quantity' => $productData['quantity'],
                    'price' => $productData['price'],
                    'discount' => $productData['discount'] ?? 0,
                    'discount_type' => $productData['discountType'] ?? 'fixed',
                    'total' => $itemTotal,
                    'expiry_date' => ! empty($productData['expiry_date']) ? $productData['expiry_date'] : null,
                ]);
                $totalAmount += $itemTotal;

                // Add new stock to (possibly new) warehouse
                $stockRecord = ProductWarehouse::firstOrCreate(
                    [
                        'product_id' => $productData['product_id'],
                        'warehouse_id' => $purchase->warehouse_id, // Use updated warehouse ID
                        'tenant_id' => $purchase->tenant_id,
                    ],
                    ['quantity' => 0]
                );
                $stockRecord->increment('quantity', $productData['quantity']);
            }

            $orderDiscount = PurchaseHelper::calculateDiscount($totalAmount, $purchase->discount_total, $purchase->discount_total_type);
            $afterDiscount = $totalAmount - $orderDiscount;
            $taxAmount = PurchaseHelper::calculateTaxAmount($afterDiscount, $taxRate, $taxType);

            if ($taxType === 'inclusive') {
                $finalTotal = $afterDiscount;
            } else {
                $finalTotal = $afterDiscount + $taxAmount;
            }

            $pphAmount = PurchaseHelper::calculatePphAmount($afterDiscount, $pphTaxRate);
            $finalTotal = max(0, $finalTotal - $pphAmount);

            $isIndonesian = app('currentTenant')->isIndonesian();
            $ppnEnabled = $isIndonesian && ($tax?->is_ppn ?? false);
            $ppnAmount = $ppnEnabled ? $taxAmount : 0;

            $purchase->update([
                'total' => $finalTotal,
                'total_tax' => $taxAmount,
                'pph_enabled' => $isIndonesian && ($pphTax?->is_pph ?? false),
                'pph_rate' => $isIndonesian && ($pphTax?->is_pph ?? false) ? $pphTaxRate : null,
                'pph_amount' => $isIndonesian && ($pphTax?->is_pph ?? false) ? $pphAmount : 0,
                'pph_tax_id' => $isIndonesian && ($pphTax?->is_pph ?? false) ? $pphTax?->id : null,
                'ppn_enabled' => $ppnEnabled,
                'ppn_rate' => $ppnEnabled ? $taxRate : null,
                'ppn_amount' => $ppnAmount,
            ]);

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

    public function addPayment(Purchase $purchase, array $data): Payment
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

            // Get accounting settings from tenant
            $accountingSettings = app('currentTenant')->accounting_settings ?? [];

            if (! $accountingSettings || ! isset($accountingSettings['accounts_payable_account_id']) || ! isset($accountingSettings['cash_account_id'])) {
                throw new \Exception('Accounting settings for accounts payable or cash are not configured.');
            }

            // Retrieve account names using the IDs from settings
            // Retrieve account names using the IDs from settings
            $accountsPayableAccount = Account::find($accountingSettings['accounts_payable_account_id']);
            $cashAccount = Account::find($accountingSettings['cash_account_id']);

            if (! $accountsPayableAccount || ! $cashAccount) {
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
            // Reverse associated journal entries for accurate accounting
            $journalEntries = JournalEntry::where('sourceable_type', get_class($purchase))
                ->where('sourceable_id', $purchase->id)
                ->get();

            foreach ($journalEntries as $entry) {
                if ($entry->status === JournalEntry::STATUS_POSTED && $entry->canBeReversed()) {
                    $this->accountingService->reverseJournalEntry($entry, "Auto-reversal due to deleted PO #{$purchase->invoice}");
                }
            }

            foreach ($purchase->items as $item) {
                // Decrement stock from the specific warehouse
                $stockRecord = ProductWarehouse::where('product_id', $item->product_id)
                    ->where('warehouse_id', $purchase->warehouse_id)
                    ->where('tenant_id', $purchase->tenant_id)
                    ->first();

                if ($stockRecord) {
                    $stockRecord->decrement('quantity', $item->quantity);
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
            $dompdf = new Dompdf;
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
        $pos = Purchase::with(['supplier', 'items.product', 'payments', 'purchaseReturns.items'])->findOrFail($id);
        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');

        $html = view('admin.po.print-pdf', compact('pos', 'shopname', 'address'))->render();
        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->stream('po-'.$pos->invoice.'.pdf', ['Attachment' => false]);
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

        $allPurchases = Purchase::with(['supplier', 'items', 'purchaseReturns'])->get();
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
}
