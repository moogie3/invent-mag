<?php

namespace App\Services;

use App\Helpers\SalesHelper;
use App\Models\Account;
use App\Models\Categories;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\Concerns\CostsLineItems;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosService
{
    use CostsLineItems;

    /**
     * Products per catalog page. With 1000+ items the POS grid renders this
     * page up front and loads the rest via admin.pos.products (search/paging).
     */
    public const CATALOG_PAGE_SIZE = 60;

    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function getPosIndexData()
    {
        ['products' => $products, 'has_more' => $has_more] = $this->searchProducts(null, 0, self::CATALOG_PAGE_SIZE);

        $customers = Customer::all();
        $walkInCustomerId = $customers->where('name', 'Walk In Customer')->first()->id ?? null;
        $categories = Categories::all();
        $units = Unit::all();
        $suppliers = Supplier::all();
        $warehouses = Warehouse::all();
        $mainWarehouse = Warehouse::where('is_main', true)->first();

        return compact('products', 'has_more', 'customers', 'walkInCustomerId', 'categories', 'units', 'suppliers', 'warehouses', 'mainWarehouse');
    }

    /**
     * Paginated catalog search (name/code match) with main-warehouse stock
     * decorated onto each product. Fetches limit + 1 rows to know if a next
     * page exists.
     *
     * @return array{products: Collection<int, Product>, has_more: bool}
     */
    public function searchProducts(?string $query, int $offset, int $limit): array
    {
        $term = trim((string) $query);

        $products = Product::with(['unit', 'productWarehouses'])
            ->when($term !== '', function ($builder) use ($term) {
                // Strip LIKE wildcards — they are metacharacters, not search text.
                $safe = str_replace(['%', '_'], '', $term);
                $builder->where(fn ($w) => $w
                    ->where('name', 'like', "%{$safe}%")
                    ->orWhere('code', 'like', "%{$safe}%"));
            })
            ->orderBy('name')
            ->orderBy('id')
            ->skip(max(0, $offset))
            ->take($limit + 1)
            ->get();

        $hasMore = $products->count() > $limit;
        $products = $products->take($limit)->values();

        $mainWarehouseId = Warehouse::where('is_main', true)->value('id');

        $products->each(function ($product) use ($mainWarehouseId) {
            $product->setAppends([]); // Prevent heavy default accessors

            $mainStock = $mainWarehouseId
                ? $product->productWarehouses->firstWhere('warehouse_id', $mainWarehouseId)
                : null;
            $stockQty = $mainStock ? max(0, (float) $mainStock->quantity) : 0;

            $product->main_warehouse_stock = $stockQty;
            $product->stock_quantity = $stockQty;
            $product->total_stock = $product->productWarehouses->sum('quantity');
        });

        return ['products' => $products, 'has_more' => $hasMore];
    }

    public function validateStockBeforeSale(array $products, $warehouseId = null)
    {
        $warehouseId = $warehouseId ?? Product::getMainWarehouseId();

        if (! $warehouseId) {
            throw new \Exception('No main warehouse configured. Please set a main warehouse first.');
        }

        $insufficientProducts = [];

        foreach ($products as $item) {
            $product = Product::find($item['id']);
            if (! $product) {
                continue;
            }

            $availableStock = $product->getWarehouseStock($warehouseId);
            $requestedQty = (int) $item['quantity'];

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

        return $insufficientProducts;
    }

    public function createSale(array $data)
    {
        $products = json_decode($data['products'], true);
        if (! $products || ! is_array($products)) {
            throw new \Exception('Invalid product data');
        }

        if (empty($products)) {
            throw new \Exception('No products in the order');
        }

        $productIds = array_column($products, 'id');
        $dbProducts = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $subTotal = 0;
        $totalBeforeDiscounts = 0;

        foreach ($products as $product) {
            $quantity = $product['quantity'];
            $price = $product['price'];

            $productSubtotal = $price * $quantity;
            $totalBeforeDiscounts += $productSubtotal;
            $subTotal += $productSubtotal;
        }

        $orderDiscount = $data['discount_total'] ?? 0;
        $orderDiscountType = $data['discount_total_type'] ?? 'fixed';
        $orderDiscountAmount = SalesHelper::calculateDiscount($totalBeforeDiscounts, $orderDiscount, $orderDiscountType);

        // POS uses the tax selection from the frontend dropdown. The dropdown
        // always submits tax_id — "None (0%)" sends an empty value, which must
        // be honored as no tax. Only fall back to the default active tax when
        // the field is absent entirely (clients that omit the selection).
        $defaultTax = Tax::effectiveDefault();
        $taxId = array_key_exists('tax_id', $data) ? ($data['tax_id'] ?: null) : $defaultTax?->id;
        $tax = $taxId ? Tax::find($taxId) : null;
        $taxType = $tax?->type ?? 'none';
        $taxRateVal = $tax?->rate ?? 0;

        $taxable = $subTotal - $orderDiscountAmount;
        $taxAmount = SalesHelper::calculateTaxAmount($taxable, $taxRateVal, $taxType);
        // For inclusive tax the price already contains tax; do NOT add taxAmount again.
        $grandTotal = ($taxType === 'inclusive') ? $taxable : ($taxable + $taxAmount);

        // Withholding (PPh) is subtracted from what the customer owes, so the
        // cash check has to run against that NET figure - the same one the POS
        // cart sends as grandTotalInput - not the pre-withholding gross. The
        // check used to run first and reject a payment of exactly what the
        // receipt shows.
        $pphTaxId = $data['pph_tax_id'] ?? null;
        $pphTax = $pphTaxId ? Tax::find($pphTaxId) : null;
        // Gate like the snapshot below: for a non-Indonesian tenant the
        // total must not shrink, since pph_amount is recorded as 0.
        $pphApplied = app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false);
        $pphTaxRate = $pphApplied && $pphTax ? (float) $pphTax->rate : 0;
        $pphAmount = $pphApplied ? SalesHelper::calculatePphAmount($taxable, $pphTaxRate) : 0;
        $dueTotal = max(0, $grandTotal - $pphAmount);

        // Validate cash payment covers the amount due
        $paymentMethod = $data['payment_method'] ?? 'Cash';
        $amountReceived = (float) ($data['amount_received'] ?? $dueTotal);
        if (strtoupper($paymentMethod) === 'CASH' && $amountReceived < $dueTotal) {
            throw new \Exception(
                'Amount received ('.number_format($amountReceived, 2, '.', '').') '.
                'must be greater than or equal to the amount due ('.number_format($dueTotal, 2, '.', '').').'
            );
        }

        $lastPosInvoice = Sales::where('invoice', 'like', 'POS-%')
            ->latest()
            ->first();

        $invoiceNumber = 1;
        if ($lastPosInvoice) {
            $lastNumber = (int) substr($lastPosInvoice->invoice, 4);
            $invoiceNumber = $lastNumber + 1;
        }
        $invoice = 'POS-'.str_pad($invoiceNumber, 4, '0', STR_PAD_LEFT);
        // Never propose a number this tenant already holds (a manually
        // entered POS invoice can sit above the newest row's number).
        while (Sales::where('invoice', $invoice)->exists()) {
            $invoiceNumber++;
            $invoice = 'POS-'.str_pad($invoiceNumber, 4, '0', STR_PAD_LEFT);
        }

        $mainWarehouse = Warehouse::where('is_main', true)->first();
        if (! $mainWarehouse) {
            throw new \Exception('No main warehouse configured. Please set a main warehouse first.');
        }

        $stockRecords = ProductWarehouse::whereIn('product_id', $productIds)
            ->where('warehouse_id', $mainWarehouse->id)
            ->get()
            ->keyBy('product_id');

        $insufficientProducts = [];
        foreach ($products as $product) {
            $productModel = $dbProducts[$product['id']] ?? null;
            if (! $productModel) {
                continue;
            }

            $availableStock = 0;
            if (isset($stockRecords[$product['id']])) {
                $availableStock = (float) $stockRecords[$product['id']]->quantity;
            }

            $requestedQty = (int) $product['quantity'];
            if ($availableStock < $requestedQty) {
                $insufficientProducts[] = [
                    'id' => $productModel->id,
                    'name' => $productModel->name,
                    'requested' => $requestedQty,
                    'available' => $availableStock,
                    'shortage' => $requestedQty - $availableStock,
                ];
            }
        }

        if (! empty($insufficientProducts)) {
            $productList = collect($insufficientProducts)->pluck('name')->implode(', ');
            throw new \Exception('Insufficient stock for: '.$productList.'. Please adjust quantities or restock.');
        }

        return DB::transaction(function () use ($data, $products, $dbProducts, $grandTotal, $orderDiscount, $orderDiscountType, $tax, $taxAmount, $taxRateVal, $taxId, $taxType, $mainWarehouse, $invoice, $amountReceived, $pphTax, $pphTaxRate, $pphAmount) {
            // Update grandTotal to subtract PPh (computed above the cash check)
            $grandTotal = max(0, $grandTotal - $pphAmount);

            $changeAmount = max(0, $amountReceived - $grandTotal);

            // exists:customers,id is raw SQL with no tenant filter, so it
            // would accept another tenant's customer id. Re-resolve through
            // the scoped model. A walk-in sale carries no customer at all
            // and stays legal, so only a present id is checked.
            $customerId = $data['customer_id'] ?? null;
            if ($customerId !== null && $customerId !== '') {
                $customerId = Customer::findOrFail($customerId)->id;
            }

            $sale = Sales::create([
                'invoice' => $invoice,
                'customer_id' => $customerId,
                'user_id' => Auth::id(),
                'warehouse_id' => $mainWarehouse->id,
                'order_date' => $data['transaction_date'],
                'due_date' => $data['transaction_date'],
                'tax_id' => $taxId,
                'tax_rate' => $taxRateVal,
                'total_tax' => $taxAmount,
                'tax_type' => $taxType,
                'total' => $grandTotal,
                'order_discount' => $orderDiscount,
                'order_discount_type' => $orderDiscountType,
                'status' => 'Paid',
                'payment_type' => $data['payment_method'],
                'amount_received' => $amountReceived,
                'change_amount' => $changeAmount,
                'type' => Sales::TYPE_POS,
                'pph_enabled' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false),
                'pph_rate' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false) ? $pphTaxRate : null,
                'pph_amount' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false) ? $pphAmount : 0,
                'pph_tax_id' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false) ? $pphTax?->id : null,
                // PPN snapshot, same gating as the journal's VAT split below.
                // e-Faktur (isPpn), Tenant::unissuedCount() and the sales
                // return PPN reversal all read these off the row; without
                // them a POS PPN sale was invisible to all three even though
                // its journal had already credited PPN Keluaran.
                'ppn_enabled' => app('currentTenant')->isIndonesian() && ($tax?->is_ppn ?? false),
                'ppn_rate' => app('currentTenant')->isIndonesian() && ($tax?->is_ppn ?? false) ? $taxRateVal : null,
                'ppn_amount' => app('currentTenant')->isIndonesian() && ($tax?->is_ppn ?? false) ? $taxAmount : 0,
            ]);

            $sale->payments()->create([
                'amount' => $grandTotal,
                'payment_date' => now(),
                'payment_method' => $data['payment_method'],
                'notes' => 'Payment for POS sale '.$invoice,
            ]);

            $stockRecords = ProductWarehouse::whereIn('product_id', array_column($products, 'id'))
                ->where('warehouse_id', $mainWarehouse->id)
                ->get()
                ->keyBy('product_id');

            $totalCostOfGoods = 0;

            foreach ($products as $product) {
                $productModel = $dbProducts[$product['id']] ?? null;
                if (! $productModel) {
                    continue;
                }
                $productSubtotal = $product['price'] * $product['quantity'];

                SalesItem::create([
                    'sales_id' => $sale->id,
                    'product_id' => $product['id'],
                    'name' => $productModel->name,
                    'quantity' => $product['quantity'],
                    'customer_price' => $product['price'],
                    'discount' => 0,
                    'discount_type' => 'fixed',
                    'total' => $productSubtotal,
                ]);

                if (isset($stockRecords[$product['id']])) {
                    $stockRecords[$product['id']]->decrement('quantity', $product['quantity']);
                }

                // Costed the way a regular sale costs it, batches and all.
                // This used to read $productModel->cost_price, a column that
                // has never existed on products, so the value was always null
                // and every POS transaction booked zero cost: revenue was
                // recorded, the goods were never charged against it, and the
                // Inventory account never fell even though stock had left the
                // warehouse. Note the two different prices in play here:
                // $product['price'] is what the cashier charged (the selling
                // price the POS screen sends), $productModel->price is what
                // the goods cost us.
                $totalCostOfGoods += $this->costAndConsumeBatches(
                    $productModel->id,
                    $product['quantity'],
                    $productModel->price
                );
            }

            $accountingSettings = app('currentTenant')->accounting_settings ?? [];
            if (! empty($accountingSettings) && isset($accountingSettings['sales_revenue_account_id'], $accountingSettings['accounts_receivable_account_id'], $accountingSettings['cost_of_goods_sold_account_id'], $accountingSettings['inventory_account_id'])) {
                $salesRevenueAccount = Account::find($accountingSettings['sales_revenue_account_id']);
                $accountsReceivableAccount = Account::find($accountingSettings['accounts_receivable_account_id']);
                $costOfGoodsSoldAccount = Account::find($accountingSettings['cost_of_goods_sold_account_id']);
                $inventoryAccount = Account::find($accountingSettings['inventory_account_id']);

                if ($salesRevenueAccount && $accountsReceivableAccount && $costOfGoodsSoldAccount && $inventoryAccount) {
                    // AR is booked at the GROSS the customer owes before
                    // withholding: $grandTotal is already net of PPh (it was
                    // reduced above) and the withholding entry credits AR a
                    // second time, so booking the net deducted the withheld
                    // amount twice and left AR sitting at minus the PPh.
                    // Same figure SalesService::postSaleJournalEntry() uses.
                    $arBase = $grandTotal + $pphAmount;

                    $transactions = [
                        ['account_name' => $accountsReceivableAccount->name, 'type' => 'debit', 'amount' => $arBase],
                        ['account_name' => $salesRevenueAccount->name, 'type' => 'credit', 'amount' => $arBase],
                        ['account_name' => $costOfGoodsSoldAccount->name, 'type' => 'debit', 'amount' => $totalCostOfGoods],
                        ['account_name' => $inventoryAccount->name, 'type' => 'credit', 'amount' => $totalCostOfGoods],
                    ];

                    // VAT split — Indonesian PPN sales book output VAT to the PPN
                    // Keluaran liability at sale creation:
                    //   Dr AR (gross) / Cr Revenue (net of VAT) / Cr PPN Keluaran (ppn)
                    // Matches standard VAT accounting and avoids the AR overstatement
                    // that occurred when PPN was only recognized on Faktur Pajak issue.
                    if (app('currentTenant')->isIndonesian() && ($tax?->is_ppn ?? false) && $taxAmount > 0) {
                        $ppnKeluaranAccountId = $accountingSettings['ppn_keluaran_account_id'] ?? null;
                        $ppnKeluaranAccount = $ppnKeluaranAccountId ? Account::find($ppnKeluaranAccountId) : null;

                        if ($ppnKeluaranAccount) {
                            $transactions[] = ['account_name' => $ppnKeluaranAccount->name, 'type' => 'credit', 'amount' => $taxAmount];

                            foreach ($transactions as &$transaction) {
                                if ($transaction['account_name'] === $salesRevenueAccount->name && $transaction['type'] === 'credit') {
                                    $transaction['amount'] = round($arBase - $taxAmount, 2);
                                }
                            }
                            unset($transaction);
                        }
                    }

                    $this->accountingService->createJournalEntry(
                        "POS Sale Invoice #{$sale->invoice}",
                        Carbon::parse($data['transaction_date']),
                        $transactions,
                        $sale
                    );

                    if (isset($accountingSettings['cash_account_id'], $accountingSettings['accounts_receivable_account_id'])) {
                        $cashAccount = Account::find($accountingSettings['cash_account_id']);
                        if ($cashAccount && $accountsReceivableAccount) {
                            $paymentTransactions = [
                                ['account_name' => $cashAccount->name, 'type' => 'debit', 'amount' => $grandTotal],
                                ['account_name' => $accountsReceivableAccount->name, 'type' => 'credit', 'amount' => $grandTotal],
                            ];

                            $this->accountingService->createJournalEntry(
                                "POS Payment for Invoice #{$sale->invoice}",
                                Carbon::parse($data['transaction_date']),
                                $paymentTransactions,
                                $sale
                            );
                        }
                    }

                    if ($pphAmount > 0 && app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false)) {
                        $pphReceivableAccountId = $accountingSettings['pph_receivable_account_id'] ?? null;
                        if ($pphReceivableAccountId) {
                            $pphReceivableAccount = Account::find($pphReceivableAccountId);
                            if ($pphReceivableAccount) {
                                $pphTransactions = [
                                    ['account_name' => $pphReceivableAccount->name, 'type' => 'debit', 'amount' => $pphAmount],
                                    ['account_name' => $accountsReceivableAccount->name, 'type' => 'credit', 'amount' => $pphAmount],
                                ];
                                $this->accountingService->createJournalEntry(
                                    "PPh Withholding on POS Sale #{$sale->invoice}",
                                    Carbon::parse($data['transaction_date']),
                                    $pphTransactions,
                                    $sale
                                );
                            }
                        }
                    }
                }
            }

            return $sale;
        });
    }

    public function getReceiptData(Sales $sale)
    {
        $totalBeforeDiscount = 0;
        $totalItemDiscount = 0;

        foreach ($sale->salesItems as $item) {
            $discountAmount = SalesHelper::calculateDiscountPerUnit(
                $item->customer_price,
                $item->discount,
                $item->discount_type
            ) * $item->quantity;

            $item->calculated_total = SalesHelper::calculateTotal(
                $item->customer_price,
                $item->quantity,
                $item->discount,
                $item->discount_type
            );

            $totalBeforeDiscount += $item->customer_price * $item->quantity;
            $totalItemDiscount += $discountAmount;
        }

        $subTotal = $totalBeforeDiscount - $totalItemDiscount;
        $orderDiscount = $sale->order_discount ?? 0;
        $orderDiscountType = $sale->order_discount_type ?? 'fixed';
        $orderDiscountAmount = SalesHelper::calculateDiscount(
            $totalBeforeDiscount,
            $orderDiscount,
            $orderDiscountType
        );

        $taxableAmount = $subTotal - $orderDiscountAmount;
        $taxRate = $sale->tax_rate ?? 0;
        $taxType = $sale->tax_type ?? 'exclusive';
        $taxAmount = SalesHelper::calculateTaxAmount($taxableAmount, $taxRate, $taxType);
        $grandTotal = $taxType === 'inclusive' ? $taxableAmount : $taxableAmount + $taxAmount;
        $pphAmount = $sale->pph_amount ?? 0;
        $grandTotal = max(0, $grandTotal - $pphAmount);
        $amountReceived = $sale->amount_received ?? $grandTotal;
        $change = max(0, $amountReceived - $grandTotal);

        return [
            'sale' => $sale,
            'subTotal' => $subTotal,
            'orderDiscountAmount' => $orderDiscountAmount,
            'taxRate' => $taxRate,
            'taxType' => $taxType,
            'taxAmount' => $taxAmount,
            'pphAmount' => $pphAmount,
            'grandTotal' => $grandTotal,
            'amountReceived' => $amountReceived,
            'change' => $change,
        ];
    }
}
