<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Tax;
use App\Helpers\SalesHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Categories;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\ProductWarehouse;
use App\Models\Account;
use App\Services\AccountingService;

class PosService
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function getPosIndexData()
    {
        $products = Product::with(['unit', 'productWarehouses'])->get();
        
        $mainWarehouse = Warehouse::where('is_main', true)->first();
        $mainWarehouseId = $mainWarehouse ? $mainWarehouse->id : null;

        $products->each(function ($product) use ($mainWarehouseId) {
            $product->setAppends([]); // Prevent heavy default accessors
            
            if ($mainWarehouseId) {
                $mainStock = $product->productWarehouses->where('warehouse_id', $mainWarehouseId)->first();
                $stockQty = $mainStock ? max(0, (float) $mainStock->quantity) : 0;
            } else {
                $stockQty = 0;
            }
            
            $product->main_warehouse_stock = $stockQty;
            $product->stock_quantity = $stockQty;
            $product->total_stock = $product->productWarehouses->sum('quantity');
        });
        
        $customers = Customer::all();
        $walkInCustomerId = $customers->where('name', 'Walk In Customer')->first()->id ?? null;
        $categories = Categories::all();
        $units = Unit::all();
        $suppliers = Supplier::all();
        $warehouses = Warehouse::all();

        return compact('products', 'customers', 'walkInCustomerId', 'categories', 'units', 'suppliers', 'warehouses', 'mainWarehouse');
    }

    public function validateStockBeforeSale(array $products, $warehouseId = null)
    {
        $warehouseId = $warehouseId ?? Product::getMainWarehouseId();
        
        if (!$warehouseId) {
            throw new \Exception('No main warehouse configured. Please set a main warehouse first.');
        }
        
        $insufficientProducts = [];
        
        foreach ($products as $item) {
            $product = Product::find($item['id']);
            if (!$product) {
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
        if (!$products || !is_array($products)) {
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

        // POS uses the tax selection from the frontend dropdown.  Fall back to the
        // default active tax when the client omits the selection.
        $defaultTax = Tax::default()->first() ?? Tax::active()->first();
        $taxId = $data['tax_id'] ?? $defaultTax?->id;
        $tax = $taxId ? Tax::find($taxId) : $defaultTax;
        $taxType = $tax?->type ?? 'none';
        $taxRateVal = $tax?->rate ?? 0;

        $taxable = $subTotal - $orderDiscountAmount;
        $taxAmount = SalesHelper::calculateTaxAmount($taxable, $taxRateVal, $taxType);
        // For inclusive tax the price already contains tax; do NOT add taxAmount again.
        $grandTotal = ($taxType === 'inclusive') ? $taxable : ($taxable + $taxAmount);

        // Validate cash payment covers the total
        $paymentMethod = $data['payment_method'] ?? 'Cash';
        $amountReceived = (float) ($data['amount_received'] ?? $grandTotal);
        if (strtoupper($paymentMethod) === 'CASH' && $amountReceived < $grandTotal) {
            throw new \Exception(
                'Amount received (' . number_format($amountReceived, 2, '.', '') . ') ' .
                'must be greater than or equal to the grand total (' . number_format($grandTotal, 2, '.', '') . ').'
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
        $invoice = 'POS-' . str_pad($invoiceNumber, 4, '0', STR_PAD_LEFT);

        $mainWarehouse = Warehouse::where('is_main', true)->first();
        if (!$mainWarehouse) {
            throw new \Exception('No main warehouse configured. Please set a main warehouse first.');
        }

        $stockRecords = ProductWarehouse::whereIn('product_id', $productIds)
            ->where('warehouse_id', $mainWarehouse->id)
            ->get()
            ->keyBy('product_id');

        $insufficientProducts = [];
        foreach ($products as $product) {
            $productModel = $dbProducts[$product['id']] ?? null;
            if (!$productModel) {
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
        
        if (!empty($insufficientProducts)) {
            $productList = collect($insufficientProducts)->pluck('name')->implode(', ');
            throw new \Exception('Insufficient stock for: ' . $productList . '. Please adjust quantities or restock.');
        }

        return DB::transaction(function () use ($data, $products, $dbProducts, $grandTotal, $orderDiscount, $orderDiscountType, $taxAmount, $taxRateVal, $taxId, $taxType, $subTotal, $mainWarehouse, $invoice, $amountReceived) {
            $changeAmount = max(0, $amountReceived - $grandTotal);
            $sale = Sales::create([
                'invoice' => $invoice,
                'customer_id' => $data['customer_id'],
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
            ]);

            $sale->payments()->create([
                'amount' => $grandTotal,
                'payment_date' => now(),
                'payment_method' => $data['payment_method'],
                'notes' => 'Payment for POS sale ' . $invoice,
            ]);

            $stockRecords = ProductWarehouse::whereIn('product_id', array_column($products, 'id'))
                ->where('warehouse_id', $mainWarehouse->id)
                ->get()
                ->keyBy('product_id');

            foreach ($products as $product) {
                $productModel = $dbProducts[$product['id']] ?? null;
                if (!$productModel) {
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
            }

            $totalCostOfGoods = 0;
            foreach ($dbProducts as $productModel) {
                $totalCostOfGoods += ($productModel->cost_price ?? 0) * ($products[array_search($productModel->id, array_column($products, 'id'))]['quantity'] ?? 0);
            }

            $accountingSettings = app('currentTenant')->accounting_settings ?? [];
            if (!empty($accountingSettings) && isset($accountingSettings['sales_revenue_account_id'], $accountingSettings['accounts_receivable_account_id'], $accountingSettings['cost_of_goods_sold_account_id'], $accountingSettings['inventory_account_id'])) {
                $salesRevenueAccount = Account::find($accountingSettings['sales_revenue_account_id']);
                $accountsReceivableAccount = Account::find($accountingSettings['accounts_receivable_account_id']);
                $costOfGoodsSoldAccount = Account::find($accountingSettings['cost_of_goods_sold_account_id']);
                $inventoryAccount = Account::find($accountingSettings['inventory_account_id']);

                if ($salesRevenueAccount && $accountsReceivableAccount && $costOfGoodsSoldAccount && $inventoryAccount) {
                    $transactions = [
                        ['account_name' => $accountsReceivableAccount->name, 'type' => 'debit', 'amount' => $grandTotal],
                        ['account_name' => $salesRevenueAccount->name, 'type' => 'credit', 'amount' => $grandTotal],
                        ['account_name' => $costOfGoodsSoldAccount->name, 'type' => 'debit', 'amount' => $totalCostOfGoods],
                        ['account_name' => $inventoryAccount->name, 'type' => 'credit', 'amount' => $totalCostOfGoods],
                    ];

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
        $amountReceived = $sale->amount_received ?? $grandTotal;
        $change = max(0, $amountReceived - $grandTotal);

        return [
            'sale' => $sale,
            'subTotal' => $subTotal,
            'orderDiscountAmount' => $orderDiscountAmount,
            'taxRate' => $taxRate,
            'taxType' => $taxType,
            'taxAmount' => $taxAmount,
            'grandTotal' => $grandTotal,
            'amountReceived' => $amountReceived,
            'change' => $change,
        ];
    }
}