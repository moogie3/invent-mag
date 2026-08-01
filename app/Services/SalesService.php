<?php

namespace App\Services;

use App\Helpers\CurrencyHelper;
use App\Helpers\SalesHelper;
use App\Models\Account;
use App\Models\Customer;
use App\Models\CustomerCredit;
use App\Models\JournalEntry;
use App\Models\NsfpPool;
use App\Models\Payment;
use App\Models\POItem;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Tax;
use App\Models\User; // Added
use App\Models\Warehouse;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesService
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function getSalesIndexData(array $filters, int $entries)
    {
        $tenantId = app('currentTenant')->id ?? null;
        $query = Sales::with(['customer', 'user', 'warehouse', 'salesReturns', 'fakturPajak']);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if (isset($filters['month']) && $filters['month']) {
            $query->whereMonth('order_date', $filters['month']);
        }
        if (isset($filters['year']) && $filters['year']) {
            $query->whereYear('order_date', $filters['year']);
        }

        // Count total before pagination for accurate total
        $totalinvoice = $query->count();

        // Now apply pagination
        $sales = $query->paginate($entries);
        $sales->getCollection()->each->setAppends([]); // Prevent N+1

        // Use DB to efficiently calculate totals without loading all rows into memory
        // Calculate total unpaid debt minus completed returns and credit used
        $unpaidSalesTotal = DB::table('sales')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['Unpaid', 'Partial'])
            ->sum('total');

        $unpaidCreditUsed = DB::table('sales')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['Unpaid', 'Partial'])
            ->sum('customer_credit_used');

        $unpaidReturns = DB::table('sales_returns')
            ->join('sales', 'sales_returns.sales_id', '=', 'sales.id')
            ->where('sales_returns.tenant_id', $tenantId)
            ->whereIn('sales.status', ['Unpaid', 'Partial'])
            ->where('sales_returns.status', 'Completed')
            ->sum('sales_returns.total_amount');

        $unpaidDebt = max(0, $unpaidSalesTotal - $unpaidCreditUsed - $unpaidReturns);

        // Calculate total monthly minus returns
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $monthlySales = DB::table('sales')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$monthStart.' 00:00:00', $monthEnd.' 23:59:59'])
            ->selectRaw('SUM(total) as monthly_total')
            ->first();

        $monthlyReturns = DB::table('sales_returns')
            ->join('sales', 'sales_returns.sales_id', '=', 'sales.id')
            ->where('sales_returns.tenant_id', $tenantId)
            ->whereBetween('sales.created_at', [$monthStart.' 00:00:00', $monthEnd.' 23:59:59'])
            ->where('sales_returns.status', 'Completed')
            ->selectRaw('SUM(sales_returns.total_amount) as returned_total')
            ->first();

        $totalMonthly = ($monthlySales->monthly_total ?? 0) - ($monthlyReturns->returned_total ?? 0);

        // Calculate POS Total minus returns
        $posSales = DB::table('sales')
            ->where('tenant_id', $tenantId)
            ->where('type', Sales::TYPE_POS)
            ->selectRaw('SUM(total) as pos_total')
            ->first();

        $posReturns = DB::table('sales_returns')
            ->join('sales', 'sales_returns.sales_id', '=', 'sales.id')
            ->where('sales_returns.tenant_id', $tenantId)
            ->where('sales.type', Sales::TYPE_POS)
            ->where('sales_returns.status', 'Completed')
            ->selectRaw('SUM(sales_returns.total_amount) as returned_total')
            ->first();

        $posTotal = ($posSales->pos_total ?? 0) - ($posReturns->returned_total ?? 0);

        $pendingOrders = Sales::where('tenant_id', $tenantId)->whereIn('status', ['Unpaid', 'Partial'])->count();

        $dueInvoices = Sales::where('tenant_id', $tenantId)
            ->whereIn('status', ['Unpaid', 'Partial'])
            ->whereDate('due_date', '>=', now())
            ->whereDate('due_date', '<=', now()->addDays(7))
            ->count();

        $overdueInvoices = Sales::where('tenant_id', $tenantId)
            ->whereIn('status', ['Unpaid', 'Partial'])
            ->whereDate('due_date', '<', now())
            ->count();

        $shopname = User::where('tenant_id', $tenantId)->whereNotNull('shopname')->value('shopname');
        $address = User::where('tenant_id', $tenantId)->whereNotNull('address')->value('address');

        return compact('posTotal', 'dueInvoices', 'overdueInvoices', 'entries', 'sales', 'totalinvoice', 'shopname', 'address', 'unpaidDebt', 'pendingOrders', 'totalMonthly');
    }

    public function getSalesCreateData()
    {
        $customers = Customer::select('id', 'name', 'email', 'phone_number', 'payment_terms')->get();
        $products = Product::select('id', 'name', 'code', 'selling_price')->get()->each->setAppends(['main_warehouse_stock']);
        $taxes = Tax::active()->orderBy('name')->get();
        $defaultTax = Tax::default()->first() ?? $taxes->first();
        $sales = [];
        $items = [];

        $tax = $defaultTax;

        return compact('sales', 'customers', 'products', 'items', 'taxes', 'defaultTax', 'tax');
    }

    public function getActiveCustomerCredits(int $customerId)
    {
        return CustomerCredit::forCustomer($customerId)->active()->get();
    }

    public function applyCustomerCredit(Sales $sale, float $creditAmount): void
    {
        $availableCredits = $this->getActiveCustomerCredits($sale->customer_id);

        $remainingCredit = $creditAmount;

        foreach ($availableCredits as $credit) {
            if ($remainingCredit <= 0) {
                break;
            }

            $availableOnThisCredit = $credit->available_amount;
            $creditToUse = min($availableOnThisCredit, $remainingCredit);

            if ($creditToUse > 0) {
                $credit->increment('used_amount', $creditToUse);
                $credit->decrement('available_amount', $creditToUse);
                $remainingCredit -= $creditToUse;

                // Check if credit is fully used
                if ($credit->used_amount >= $credit->amount) {
                    $credit->update(['status' => 'used']);
                }
            }
        }

        // Update sale with credit used amount
        $sale->update(['customer_credit_used' => $creditAmount - $remainingCredit]);
    }

    public function getCustomerTotalAvailableCredit(int $customerId): float
    {
        return CustomerCredit::forCustomer($customerId)->active()->sum('available_amount');
    }

    public function useCustomerCredit(int $customerId, float $amount, ?int $saleId = null): void
    {
        $credits = CustomerCredit::forCustomer($customerId)->active()->orderBy('created_at', 'asc')->get();

        $remainingAmount = $amount;

        foreach ($credits as $credit) {
            if ($remainingAmount <= 0) {
                break;
            }

            $availableOnThisCredit = $credit->available_amount;
            $creditToUse = min($availableOnThisCredit, $remainingAmount);

            if ($creditToUse > 0) {
                $credit->increment('used_amount', $creditToUse);
                $credit->decrement('available_amount', $creditToUse);
                $remainingAmount -= $creditToUse;

                // Check if credit is fully used
                if ($credit->used_amount >= $credit->amount) {
                    $credit->update(['status' => 'used']);
                }
            }
        }
    }

    public function getSalesEditData($id)
    {
        $sales = Sales::with(['salesItems', 'customer', 'payments', 'warehouse', 'salesReturns.items'])->find($id);
        $customers = Customer::all();
        $taxes = Tax::active()->orderBy('name')->get();
        $defaultTax = Tax::default()->first() ?? $taxes->first();
        $isPaid = $sales->status == 'Paid';
        $tax = $defaultTax;

        return compact('sales', 'customers', 'taxes', 'defaultTax', 'tax', 'isPaid');
    }

    public function getSalesViewData($id)
    {
        /** @var Sales $sales */
        $sales = Sales::with(['salesItems', 'customer', 'payments', 'salesReturns.items', 'fakturPajak'])->find($id);

        $customer = $sales->customer;
        $tax = Tax::find($sales->tax_id);

        $itemCount = $sales->salesItems->count();

        $summary = SalesHelper::calculateInvoiceSummaryWithReturns(
            $sales->salesItems,
            $sales->order_discount ?? 0,
            $sales->order_discount_type ?? 'fixed',
            $sales->tax_rate ?? 0,
            $sales->relationLoaded('salesReturns') ? $sales->salesReturns : collect(),
            $sales->tax_type ?? 'exclusive'
        );

        $subtotal = $summary['subtotal'];
        $totalItemDiscount = $summary['totalProductDiscount'];
        $orderDiscount = $summary['orderDiscount'];
        $taxAmount = $summary['taxAmount'];
        $finalTotal = $summary['finalTotal'];

        $summary = array_merge($summary, [
            'itemCount' => $itemCount,
            'totalItemDiscount' => $totalItemDiscount,
        ]);

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
        $totalinvoice = 0;
        $unpaidDebt = 0;
        $totalMonthly = 0;
        $pendingOrders = 0;
        $dueInvoices = 0;
        $overdueInvoices = 0;
        $posTotal = 0;

        $sales = Sales::with(['salesItems', 'payments'])->get();
        foreach ($sales as $sale) {
            $totalinvoice += $sale->total_amount;
            $unpaidDebt += ($sale->status !== 'Paid') ? $sale->balance : 0;

            if ($sale->order_date->isCurrentMonth()) {
                $totalMonthly += $sale->total_amount;
            }

            if (in_array($sale->status, ['Unpaid', 'Partial'])) {
                $pendingOrders++;
                if ($sale->due_date) {
                    if ($sale->due_date->gte(now()) && $sale->due_date->lte(now()->addDays(7))) {
                        $dueInvoices++;
                    } elseif ($sale->due_date->lt(now())) {
                        $overdueInvoices++;
                    }
                }
            }

            if ($sale->type === Sales::TYPE_POS) {
                $posTotal += $sale->total_amount;
            }
        }

        return [
            'totalinvoice' => $totalinvoice,
            'unpaidDebt' => $unpaidDebt,
            'totalMonthly' => $totalMonthly,
            'pendingOrders' => $pendingOrders,
            'dueInvoices' => $dueInvoices,
            'overdueInvoices' => $overdueInvoices,
            'posTotal' => $posTotal,
        ];
    }

    public function createSale(array $data): Sales
    {
        return DB::transaction(function () use ($data) {
            $products = json_decode($data['products'], true);
            if (! $products || ! is_array($products)) {
                throw new \Exception('Invalid product data');
            }

            if (empty($products)) {
                throw new \Exception('No products in the order');
            }

            $subTotal = 0;
            foreach ($products as $product) {
                $subTotal += SalesHelper::calculateTotal(
                    $product['customer_price'],
                    $product['quantity'],
                    $product['discount'] ?? 0,
                    $product['discountType'] ?? 'fixed'
                );
            }

            $orderDiscount = $data['discount_total'] ?? 0;
            $orderDiscountType = $data['discount_total_type'] ?? 'fixed';
            $orderDiscountAmount = SalesHelper::calculateDiscount($subTotal, $orderDiscount, $orderDiscountType);

            $taxId = $data['tax_id'] ?? null;
            $tax = $taxId ? Tax::find($taxId) : (Tax::default()->first() ?? Tax::active()->first());
            $taxRate = $tax ? (float) $tax->rate : 0;
            $taxType = $tax ? $tax->type : 'none';
            $taxableAmount = $subTotal - $orderDiscountAmount;
            $taxAmount = SalesHelper::calculateTaxAmount($taxableAmount, $taxRate, $taxType);

            $pphTaxId = $data['pph_tax_id'] ?? null;
            $pphTax = $pphTaxId ? Tax::find($pphTaxId) : null;
            $pphTaxRate = $pphTax ? (float) $pphTax->rate : 0;
            $pphAmount = SalesHelper::calculatePphAmount($taxableAmount, $pphTaxRate);

            if ($taxType === 'inclusive') {
                $grandTotal = $taxableAmount;
            } else {
                $grandTotal = $taxableAmount + $taxAmount;
            }
            $grandTotal = max(0, $grandTotal - $pphAmount);

            $invoice = $data['invoice'] ?? $this->generateInvoiceNumber();

            $warehouseId = $data['warehouse_id'] ?? Product::getMainWarehouseId();
            if (! $warehouseId) {
                throw new \Exception('No warehouse specified and no main warehouse configured.');
            }

            $sale = Sales::create([
                'invoice' => $invoice,
                'customer_id' => $data['customer_id'],
                'user_id' => Auth::id(),
                'warehouse_id' => $warehouseId,
                'order_date' => $data['order_date'],
                'due_date' => $data['due_date'],
                'tax_id' => $tax?->id,
                'tax_rate' => $taxRate,
                'total_tax' => $taxAmount,
                'tax_type' => $taxType,
                'total' => $grandTotal,
                'order_discount' => $orderDiscount,
                'order_discount_type' => $orderDiscountType,
                'status' => 'Unpaid',
                'payment_type' => '-',
                'type' => Sales::TYPE_REGULAR,
                'customer_credit_used' => 0,
                'notes' => $data['notes'] ?? null,
                'terms_conditions' => $data['terms_conditions'] ?? null,
                // PPN / e-Faktur fields — derived from selected tax's is_ppn flag.
                // Only applied when tenant's tax jurisdiction is Indonesian.
                'ppn_enabled' => app('currentTenant')->isIndonesian() && ($tax?->is_ppn ?? false),
                'ppn_rate' => app('currentTenant')->isIndonesian() && ($tax?->is_ppn ?? false) ? $taxRate : null,
                'ppn_amount' => app('currentTenant')->isIndonesian() && ($tax?->is_ppn ?? false) ? $taxAmount : 0,
                // PPh / Withholding Tax fields — derived from selected PPh tax's is_pph flag.
                // Only applied when tenant's tax jurisdiction is Indonesian.
                'pph_enabled' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false),
                'pph_rate' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false) ? $pphTaxRate : null,
                'pph_amount' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false) ? $pphAmount : 0,
                'pph_tax_id' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false) ? $pphTax?->id : null,
            ]);

            // Apply customer credit if provided
            $creditAmount = floatval($data['customer_credit_amount'] ?? 0);
            if ($creditAmount > 0) {
                $this->applyCustomerCredit($sale, $creditAmount);
                $sale->refresh();

                // Update status based on credit usage
                $this->updateSaleStatus($sale);
            }

            $totalCostOfGoods = 0;

            $stockRecords = ProductWarehouse::whereIn('product_id', array_column($products, 'product_id'))
                ->where('warehouse_id', $warehouseId)
                ->where('tenant_id', $sale->tenant_id)
                ->get()
                ->keyBy('product_id');

            $insufficientProducts = [];
            foreach ($products as $productData) {
                $product = Product::where('id', $productData['product_id'])
                    ->where('tenant_id', $sale->tenant_id)
                    ->first();
                if (! $product) {
                    throw new \Exception("Product with ID {$productData['product_id']} not found in this tenant.");
                }

                $availableStock = 0;
                if (isset($stockRecords[$product->id])) {
                    $availableStock = (float) $stockRecords[$product->id]->quantity;
                }

                $requestedQty = (int) $productData['quantity'];
                if ($availableStock < $requestedQty) {
                    $insufficientProducts[] = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'requested' => $requestedQty,
                        'available' => $availableStock,
                        'shortage' => $requestedQty - $availableStock,
                    ];
                }
            }

            if (! empty($insufficientProducts)) {
                $sale->delete();
                $productList = collect($insufficientProducts)->pluck('name')->implode(', ');
                throw new \Exception('Insufficient stock for: '.$productList.'. Please adjust quantities or restock first.');
            }

            foreach ($products as $productData) {
                $product = Product::where('id', $productData['product_id'])
                    ->where('tenant_id', $sale->tenant_id)
                    ->first();
                if (! $product) {
                    throw new \Exception("Product with ID {$productData['product_id']} not found in this tenant.");
                }

                SalesItem::create([
                    'sales_id' => $sale->id,
                    'product_id' => $productData['product_id'],
                    'name' => $product->name,
                    'quantity' => $productData['quantity'],
                    'customer_price' => $productData['customer_price'],
                    'discount' => $productData['discount'] ?? 0,
                    'discount_type' => $productData['discountType'] ?? 'fixed',
                    'total' => SalesHelper::calculateTotal($productData['customer_price'], $productData['quantity'], $productData['discount'] ?? 0, $productData['discountType'] ?? 'fixed'),
                ]);

                if (isset($stockRecords[$product->id])) {
                    $stockRecords[$product->id]->decrement('quantity', $productData['quantity']);
                }

                $poItems = POItem::where('product_id', $product->id)
                    ->where('remaining_quantity', '>', 0)
                    ->orderBy('expiry_date', 'asc')
                    ->orderBy('created_at', 'asc')
                    ->get();

                $quantityToDecrement = $productData['quantity'];
                $itemCOGS = 0;

                foreach ($poItems as $poItem) {
                    if ($quantityToDecrement <= 0) {
                        break;
                    }

                    $decrement = min($poItem->remaining_quantity, $quantityToDecrement);
                    $itemCOGS += $decrement * $poItem->price;
                    $poItem->decrement('remaining_quantity', $decrement);
                    $quantityToDecrement -= $decrement;
                }

                if ($quantityToDecrement > 0) {
                    $itemCOGS += $quantityToDecrement * $product->price;
                }

                $totalCostOfGoods += $itemCOGS;
            }

            $accountingSettings = app('currentTenant')->accounting_settings ?? [];

            $salesRevenueAccount = Account::find($accountingSettings['sales_revenue_account_id'] ?? null);
            $accountsReceivableAccount = Account::find($accountingSettings['accounts_receivable_account_id'] ?? null);
            $costOfGoodsSoldAccount = Account::find($accountingSettings['cost_of_goods_sold_account_id'] ?? null);
            $inventoryAccount = Account::find($accountingSettings['inventory_account_id'] ?? null);

            if (! $salesRevenueAccount || ! $accountsReceivableAccount || ! $costOfGoodsSoldAccount || ! $inventoryAccount) {
                throw new \Exception('One or more required accounts (Sales Revenue, AR, COGS, Inventory) not found. Please check Accounting Settings.');
            }

            $salesRevenueAccountName = $salesRevenueAccount->name;
            $accountsReceivableAccountName = $accountsReceivableAccount->name;
            $costOfGoodsSoldAccountName = $costOfGoodsSoldAccount->name;
            $inventoryAccountName = $inventoryAccount->name;

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

            // Create PPh journal entry if PPh is applied
            if ($pphAmount > 0 && app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false)) {
                $pphReceivableAccountId = $accountingSettings['pph_receivable_account_id'] ?? null;
                if ($pphReceivableAccountId) {
                    $pphReceivableAccount = Account::find($pphReceivableAccountId);
                    if ($pphReceivableAccount) {
                        $pphTransactions = [
                            ['account_name' => $pphReceivableAccount->name, 'type' => 'debit', 'amount' => $pphAmount],
                            ['account_name' => $accountsReceivableAccountName, 'type' => 'credit', 'amount' => $pphAmount],
                        ];
                        $this->accountingService->createJournalEntry(
                            "PPh Withholding on Sale #{$sale->invoice}",
                            Carbon::parse($data['order_date']),
                            $pphTransactions,
                            $sale
                        );
                    }
                }
            }

            // Create customer credit usage journal entry if credit was applied
            $creditUsed = $sale->customer_credit_used;
            if ($creditUsed > 0) {
                // Check if a customer credit account exists, otherwise use a discount account
                $customerCreditAccountId = $accountingSettings['customer_credit_account_id'] ?? null;

                if ($customerCreditAccountId) {
                    $customerCreditAccount = Account::find($customerCreditAccountId);
                    if ($customerCreditAccount) {
                        $creditTransactions = [
                            ['account_name' => $customerCreditAccount->name, 'type' => 'debit', 'amount' => $creditUsed],
                            ['account_name' => $accountsReceivableAccountName, 'type' => 'credit', 'amount' => $creditUsed],
                        ];

                        $this->accountingService->createJournalEntry(
                            "Credit Applied to Invoice #{$sale->invoice}",
                            Carbon::parse($data['order_date']),
                            $creditTransactions,
                            $sale
                        );
                    }
                }
            }

            return $sale;
        });
    }

    public function updateSale(Sales $sale, array $data): Sales
    {
        return DB::transaction(function () use ($sale, $data) {
            $products = json_decode($data['products'], true);
            if (! $products || ! is_array($products)) {
                throw new \Exception('Invalid product data');
            }

            // Revert old stock quantities to the original warehouse
            foreach ($sale->salesItems as $oldItem) {
                $stockRecord = ProductWarehouse::where('product_id', $oldItem->product_id)
                    ->where('warehouse_id', $sale->warehouse_id)
                    ->where('tenant_id', $sale->tenant_id)
                    ->first();

                if ($stockRecord) {
                    $stockRecord->increment('quantity', $oldItem->quantity);
                }

                // Revert remaining quantity (Global Batch)
                $poItems = POItem::where('product_id', $oldItem->product_id)
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

            $sale->salesItems()->delete();

            $subTotal = 0;
            $subtotalBeforeDiscounts = 0;
            foreach ($products as $productData) {
                $subTotal += SalesHelper::calculateTotal($productData['customer_price'], $productData['quantity'], $productData['discount'] ?? 0, $productData['discountType'] ?? 'fixed');
                $subtotalBeforeDiscounts += $productData['customer_price'] * $productData['quantity'];
            }

            $orderDiscount = $data['discount_total'] ?? 0;
            $orderDiscountType = $data['discount_total_type'] ?? 'fixed';
            $orderDiscountAmount = SalesHelper::calculateDiscount($subTotal, $orderDiscount, $orderDiscountType);

            $taxId = $data['tax_id'] ?? null;
            $tax = $taxId ? Tax::find($taxId) : (Tax::default()->first() ?? Tax::active()->first());
            $taxRate = $tax ? (float) $tax->rate : 0;
            $taxType = $tax ? $tax->type : 'none';
            $taxableAmount = $subTotal - $orderDiscountAmount;
            $taxAmount = SalesHelper::calculateTaxAmount($taxableAmount, $taxRate, $taxType);

            $pphTaxId = $data['pph_tax_id'] ?? null;
            $pphTax = $pphTaxId ? Tax::find($pphTaxId) : null;
            $pphTaxRate = $pphTax ? (float) $pphTax->rate : 0;
            $pphAmount = SalesHelper::calculatePphAmount($taxableAmount, $pphTaxRate);

            if ($taxType === 'inclusive') {
                $grandTotal = $taxableAmount;
            } else {
                $grandTotal = $taxableAmount + $taxAmount;
            }
            $grandTotal = max(0, $grandTotal - $pphAmount);

            $sale->update([
                'customer_id' => $data['customer_id'],
                'order_date' => $data['order_date'],
                'due_date' => $data['due_date'],
                'tax_id' => $tax?->id,
                'tax_rate' => $taxRate,
                'total_tax' => $taxAmount,
                'tax_type' => $taxType,
                'total' => $grandTotal,
                'order_discount' => $orderDiscount,
                'order_discount_type' => $orderDiscountType,
                'payment_type' => $data['payment_type'] ?? '-',
                'notes' => $data['notes'] ?? $sale->notes,
                'terms_conditions' => $data['terms_conditions'] ?? $sale->terms_conditions,
                // PPN / e-Faktur fields — derived from selected tax's is_ppn flag.
                // Only applied when tenant's tax jurisdiction is Indonesian.
                'ppn_enabled' => app('currentTenant')->isIndonesian() && ($tax?->is_ppn ?? false),
                'ppn_rate' => app('currentTenant')->isIndonesian() && ($tax?->is_ppn ?? false) ? $taxRate : null,
                'ppn_amount' => app('currentTenant')->isIndonesian() && ($tax?->is_ppn ?? false) ? $taxAmount : 0,
                // PPh / Withholding Tax fields — derived from selected PPh tax's is_pph flag.
                // Only applied when tenant's tax jurisdiction is Indonesian.
                'pph_enabled' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false),
                'pph_rate' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false) ? $pphTaxRate : null,
                'pph_amount' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false) ? $pphAmount : 0,
                'pph_tax_id' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false) ? $pphTax?->id : null,
            ]);

            foreach ($products as $productData) {
                $product = Product::where('id', $productData['product_id'])
                    ->where('tenant_id', $sale->tenant_id)
                    ->first();
                if (! $product) {
                    continue;
                }

                SalesItem::create([
                    'sales_id' => $sale->id,
                    'product_id' => $productData['product_id'],
                    'name' => $product->name,
                    'quantity' => $productData['quantity'],
                    'customer_price' => $productData['customer_price'],
                    'discount' => $productData['discount'] ?? 0,
                    'discount_type' => $productData['discountType'] ?? 'fixed',
                    'total' => SalesHelper::calculateTotal($productData['customer_price'], $productData['quantity'], $productData['discount'] ?? 0, $productData['discountType'] ?? 'fixed'),
                ]);

                // Deduct from NEW warehouse
                $stockRecord = ProductWarehouse::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'warehouse_id' => $sale->warehouse_id,
                        'tenant_id' => $sale->tenant_id,
                    ],
                    ['quantity' => 0]
                );

                $stockRecord->decrement('quantity', $productData['quantity']);

                // Reduct from batch (FEFO)
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

    public function addPayment(Sales $sale, array $data): Payment
    {
        return DB::transaction(function () use ($sale, $data) {
            $cashAmount = $data['amount'] ?? 0;
            $creditAmount = $data['credit_amount'] ?? 0;
            $totalPaymentAmount = $cashAmount + $creditAmount;

            if ($totalPaymentAmount <= 0) {
                throw new \Exception('Payment amount must be greater than zero.');
            }

            // Validate credit usage if provided
            if ($creditAmount > 0) {
                $availableCredit = $this->getCustomerTotalAvailableCredit($sale->customer_id);
                if ($creditAmount > $availableCredit) {
                    throw new \Exception('Insufficient customer credit. Available: '.number_format($availableCredit, 2));
                }

                // Calculate remaining balance after credit
                $balanceAfterCredit = $sale->balance - $creditAmount;
                if ($balanceAfterCredit < 0) {
                    $creditAmount = $sale->balance; // Cap credit to remaining balance
                    $totalPaymentAmount = $cashAmount + $creditAmount;
                }

                // Use credits (FIFO - oldest first)
                $this->useCustomerCredit($sale->customer_id, $creditAmount, $sale->id);

                // Update sale's credit used
                $newCreditUsed = ($sale->customer_credit_used ?? 0) + $creditAmount;
                $sale->update(['customer_credit_used' => $newCreditUsed]);
            }

            // Create cash payment record
            if ($cashAmount > 0) {
                $payment = $sale->payments()->create([
                    'amount' => $cashAmount,
                    'payment_date' => $data['payment_date'],
                    'payment_method' => $data['payment_method'],
                    'notes' => $data['notes'] ?? null,
                ]);
            } else {
                // Create a dummy payment for credit-only transactions
                $payment = $sale->payments()->create([
                    'amount' => 0,
                    'payment_date' => $data['payment_date'],
                    'payment_method' => 'Credit',
                    'notes' => 'Paid via customer credit: '.number_format($creditAmount, 2),
                ]);
            }

            $sale->load('payments'); // Refresh the payments relationship
            $this->updateSaleStatus($sale);
            $sale->refresh();

            // Get accounting settings from tenant
            $accountingSettings = app('currentTenant')->accounting_settings ?? [];

            // Only create journal entry for actual cash payments
            if ($cashAmount > 0 && ! empty($accountingSettings) && isset($accountingSettings['cash_account_id']) && isset($accountingSettings['accounts_receivable_account_id'])) {
                // Retrieve account names using the IDs from settings
                $cashAccount = Account::find($accountingSettings['cash_account_id']);
                $accountsReceivableAccount = Account::find($accountingSettings['accounts_receivable_account_id']);

                if ($cashAccount && $accountsReceivableAccount) {
                    $cashAccountName = $cashAccount->name;
                    $accountsReceivableAccountName = $accountsReceivableAccount->name;

                    // Create Journal Entry for the payment
                    $transactions = [
                        ['account_name' => $cashAccountName, 'type' => 'debit', 'amount' => $cashAmount],
                        ['account_name' => $accountsReceivableAccountName, 'type' => 'credit', 'amount' => $cashAmount],
                    ];

                    $this->accountingService->createJournalEntry(
                        "Payment for Sale #{$sale->invoice}",
                        Carbon::parse($data['payment_date']),
                        $transactions,
                        $payment
                    );
                }
            }

            return $payment;
        });
    }

    public function updateSaleStatus(Sales $sale)
    {
        $totalPaid = $sale->total_paid;
        $creditUsed = $sale->customer_credit_used;
        $grandTotal = $sale->total;

        $totalPaidWithCredit = $totalPaid + $creditUsed;

        if ($totalPaidWithCredit >= $grandTotal) {
            $sale->status = 'Paid';
        } elseif ($totalPaidWithCredit > 0 && $totalPaidWithCredit < $grandTotal) {
            $sale->status = 'Partial';
        } else {
            $sale->status = 'Unpaid';
        }

        $sale->save();
    }

    public function deleteSale(Sales $sale): void
    {
        DB::transaction(function () use ($sale) {
            // Reverse associated journal entries for accurate accounting
            $journalEntries = JournalEntry::where('sourceable_type', get_class($sale))
                ->where('sourceable_id', $sale->id)
                ->get();

            foreach ($journalEntries as $entry) {
                if ($entry->status === JournalEntry::STATUS_POSTED && $entry->canBeReversed()) {
                    $this->accountingService->reverseJournalEntry($entry, "Auto-reversal due to deleted Sale #{$sale->invoice}");
                }
            }

            // Also reverse FakturPajak journal entries (PPN Keluaran)
            $fakturPajak = $sale->fakturPajak()->first();
            if ($fakturPajak) {
                $fpJournalEntries = JournalEntry::where('sourceable_type', get_class($fakturPajak))
                    ->where('sourceable_id', $fakturPajak->id)
                    ->get();

                foreach ($fpJournalEntries as $entry) {
                    if ($entry->status === JournalEntry::STATUS_POSTED && $entry->canBeReversed()) {
                        $this->accountingService->reverseJournalEntry($entry, "Auto-reversal due to deleted Sale #{$sale->invoice} (Faktur Pajak {$fakturPajak->nsfp})");
                    }
                }

                // Mark NSFP as cancelled and delete FakturPajak
                NsfpPool::where('nsfp', $fakturPajak->nsfp)->update(['status' => 'cancelled']);
                $fakturPajak->items()->delete();
                $fakturPajak->delete();
            }

            // Reverse PPh journal entries
            if ($sale->isPph() && $sale->pph_amount > 0) {
                $pphJournalEntries = JournalEntry::where('sourceable_type', get_class($sale))
                    ->where('sourceable_id', $sale->id)
                    ->where('description', 'like', '%PPh Withholding%')
                    ->get();
                foreach ($pphJournalEntries as $entry) {
                    if ($entry->status === JournalEntry::STATUS_POSTED && $entry->canBeReversed()) {
                        $this->accountingService->reverseJournalEntry($entry, "Auto-reversal due to deleted Sale #{$sale->invoice} (PPh)");
                    }
                }
            }

            foreach ($sale->salesItems as $item) {
                // Revert stock to the warehouse it was sold from
                $stockRecord = ProductWarehouse::where('product_id', $item->product_id)
                    ->where('warehouse_id', $sale->warehouse_id)
                    ->where('tenant_id', $sale->tenant_id)
                    ->first();

                if ($stockRecord) {
                    $stockRecord->increment('quantity', $item->quantity);
                }

                // Revert remaining quantity (Global Batch)
                $poItems = POItem::where('product_id', $item->product_id)
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

    public function bulkExportSales(array $filters, ?array $ids, string $exportOption)
    {
        $query = Sales::with(['customer', 'salesItems']);

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

        $sales = $query->get();

        if ($exportOption === 'pdf') {
            $html = view('admin.sales.bulk-export-pdf', compact('sales'))->render();
            $dompdf = new Dompdf;
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
                        CurrencyHelper::format($sale->total_amount),
                        $sale->status,
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return null;
    }

    public function printInvoice($id)
    {
        $sales = Sales::with(['customer', 'salesItems.product', 'payments'])->findOrFail($id);
        $tenantId = app('currentTenant')->id ?? null;
        $shopname = User::where('tenant_id', $tenantId)->whereNotNull('shopname')->value('shopname');
        $address = User::where('tenant_id', $tenantId)->whereNotNull('address')->value('address');

        $html = view('admin.sales.print-pdf', compact('sales', 'shopname', 'address'))->render();
        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->stream('invoice-'.$sales->invoice.'.pdf', ['Attachment' => false]);
    }

    public function getSalesForModal($id)
    {
        return Sales::with(['customer', 'salesItems.product', 'payments', 'salesReturns.items'])->findOrFail($id);
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

    public function generateInvoiceNumber(): string
    {
        $tenantId = auth()->user()->tenant_id ?? null;

        $query = Sales::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $lastSalesInvoice = $query->orderBy('id', 'desc')->first();

        $invoiceNumber = 1;
        if ($lastSalesInvoice && preg_match('/INV-(\d+)/', $lastSalesInvoice->invoice, $matches)) {
            $lastNumber = (int) $matches[1];
            $invoiceNumber = $lastNumber + 1;
        }

        return 'INV-'.str_pad($invoiceNumber, 4, '0', STR_PAD_LEFT);
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

    /**
     * @deprecated Use SalesReturnService::createSalesReturn() instead.
     *             This legacy method lacks customer credit creation, proper
     *             payment-aware status logic, and full journal entries.
     */
    public function createSalesReturn(array $data): SalesReturn
    {
        return DB::transaction(function () use ($data) {
            $sale = Sales::findOrFail($data['sales_id']);
            $items = json_decode($data['items'], true);
            if (! $items || ! is_array($items)) {
                throw new \Exception('Invalid items data');
            }

            $returnedItems = array_filter($items, function ($item) {
                return isset($item['returned_quantity']) && $item['returned_quantity'] > 0;
            });

            if (empty($returnedItems)) {
                throw new \Exception('No items selected for return.');
            }

            $totalReturnAmount = 0;
            foreach ($returnedItems as $itemData) {
                $totalReturnAmount += ($itemData['price'] ?? 0) * $itemData['returned_quantity'];
            }

            $salesReturn = SalesReturn::create([
                'sales_id' => $sale->id,
                'user_id' => Auth::id(),
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'total_amount' => $totalReturnAmount,
                'status' => $data['status'],
            ]);

            foreach ($returnedItems as $itemData) {
                $returnedQuantity = $itemData['returned_quantity'];
                $price = $itemData['price'] ?? 0;

                SalesReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $returnedQuantity,
                    'price' => $price,
                    'total' => $price * $returnedQuantity,
                ]);

                $product = Product::find($itemData['product_id']);
                if ($product) {
                    $warehouseId = $sale->warehouse_id ?? Product::getMainWarehouseId();

                    $stockRecord = ProductWarehouse::firstOrCreate(
                        ['product_id' => $product->id, 'warehouse_id' => $warehouseId, 'tenant_id' => $product->tenant_id],
                        ['quantity' => 0]
                    );
                    $stockRecord->increment('quantity', $returnedQuantity);
                }
            }

            $totalSoldQuantity = $sale->salesItems()->sum('quantity');
            $totalReturnedQuantity = $sale->salesReturns()->with('items')->get()->flatMap(function ($sr) {
                return $sr->items;
            })->sum('quantity');

            if ($totalReturnedQuantity >= $totalSoldQuantity) {
                $sale->update(['status' => 'Returned']);
            } elseif ($totalReturnedQuantity > 0) {
                $sale->update(['status' => 'Partial']);
            }

            $accountingSettings = app('currentTenant')->accounting_settings ?? [];
            if (! $accountingSettings || ! isset($accountingSettings['inventory_account_id']) || ! isset($accountingSettings['accounts_receivable_account_id'])) {
                throw new \Exception('Accounting settings for inventory or accounts receivable are not configured.');
            }

            $inventoryAccount = Account::find($accountingSettings['inventory_account_id']);
            $accountsReceivableAccount = Account::find($accountingSettings['accounts_receivable_account_id']);

            if (! $inventoryAccount || ! $accountsReceivableAccount) {
                throw new \Exception('Required accounts (Inventory, AR) not found for Sales Return. Please check Accounting Settings.');
            }

            $inventoryAccountName = $inventoryAccount->name;
            $accountsReceivableAccountName = $accountsReceivableAccount->name;

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
