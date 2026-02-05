<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Helpers\SalesHelper;
use Illuminate\Support\Facades\Auth;

use App\Models\Categories;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\ProductWarehouse;

class PosService
{
    public function getPosIndexData()
    {
        $products = Product::with('unit')->get();
        $customers = Customer::all();
        $walkInCustomerId = $customers->where('name', 'Walk In Customer')->first()->id ?? null;
        $categories = Categories::all();
        $units = Unit::all();
        $suppliers = Supplier::all();

        return compact('products', 'customers', 'walkInCustomerId', 'categories', 'units', 'suppliers');
    }

    public function createSale(array $data)
    {
        $products = json_decode($data['products'], true);
        if (!$products || !is_array($products)) {
            throw new \Exception('Invalid product data');
        }

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

        $taxable = $subTotal - $orderDiscountAmount;
        $taxRate = $data['tax_rate'] ?? 0;
        $taxAmount = $data['tax_amount'] ?? SalesHelper::calculateTaxAmount($taxable, $taxRate);
        $grandTotal = $data['grand_total'] ?? $taxable + $taxAmount;

        $userId = Auth::id();
        $userTimezone = Auth::user()->timezone ?? config('app.timezone');

        $transactionDate = \Carbon\Carbon::parse($data['transaction_date'], $userTimezone)->setTimezone('UTC')->format('Y-m-d');
        $transactionDate = $data['transaction_date'];

        $lastPosInvoice = Sales::where('invoice', 'like', 'POS-%')
                            ->latest()
                            ->first();

        $invoiceNumber = 1;
        if ($lastPosInvoice) {
            $lastNumber = (int) substr($lastPosInvoice->invoice, 4);
            $invoiceNumber = $lastNumber + 1;
        }
        $invoice = 'POS-' . str_pad($invoiceNumber, 4, '0', STR_PAD_LEFT);

        // Assign to Main Warehouse by default for POS
        $mainWarehouse = Warehouse::where('is_main', true)->first();
        if (!$mainWarehouse) {
            // Fallback
            $mainWarehouse = Warehouse::first();
        }

        $sale = Sales::create([
            'invoice' => $invoice,
            'customer_id' => $data['customer_id'],
            'user_id' => Auth::id(),
            'warehouse_id' => $mainWarehouse ? $mainWarehouse->id : null, // Added
            'order_date' => $data['transaction_date'],
            'due_date' => $data['transaction_date'],
            'tax_rate' => $taxRate,
            'total_tax' => $taxAmount,
            'total' => $grandTotal,
            'order_discount' => $orderDiscountAmount,
            'order_discount_type' => $orderDiscountType,
            'status' => 'Paid',
            'payment_type' => $data['payment_method'],
            'amount_received' => $data['amount_received'] ?? $grandTotal,
            'change_amount' => $data['change_amount'] ?? 0,
            'is_pos' => true,
        ]);

        $sale->payments()->create([
            'amount' => $grandTotal,
            'payment_date' => now(),
            'payment_method' => $data['payment_method'],
            'notes' => 'Payment for POS sale ' . $invoice,
        ]);

        foreach ($products as $product) {
            $productModel = Product::find($product['id']);
            if (!$productModel) {
                continue;
            }
            $productSubtotal = $product['price'] * $product['quantity'];

            SalesItem::create([
                'sales_id' => $sale->id,
                'product_id' => $product['id'],
                'name' => $productModel->name, // Added product name
                'quantity' => $product['quantity'],
                'customer_price' => $product['price'],
                'discount' => 0,
                'discount_type' => 'fixed',
                'total' => $productSubtotal,
            ]);

            // Deduct stock from Main Warehouse
            if ($mainWarehouse) {
                $stockRecord = ProductWarehouse::where('product_id', $productModel->id)
                    ->where('warehouse_id', $mainWarehouse->id)
                    ->where('tenant_id', $productModel->tenant_id)
                    ->first();

                if ($stockRecord) {
                    $stockRecord->decrement('quantity', $product['quantity']);
                } else {
                    // Create negative stock if allowed
                    ProductWarehouse::create([
                        'product_id' => $productModel->id,
                        'warehouse_id' => $mainWarehouse->id,
                        'quantity' => -$product['quantity'],
                        'tenant_id' => $productModel->tenant_id
                    ]);
                }
            }
        }

        return $sale;
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
        $taxAmount = SalesHelper::calculateTaxAmount($taxableAmount, $taxRate);
        $grandTotal = $taxableAmount + $taxAmount;
        $amountReceived = $sale->amount_received ?? $grandTotal;
        $change = $amountReceived - $grandTotal;

        return [
            'sale' => $sale,
            'subTotal' => $subTotal,
            'orderDiscountAmount' => $orderDiscountAmount,
            'taxRate' => $taxRate,
            'taxAmount' => $taxAmount,
            'grandTotal' => $grandTotal,
            'amountReceived' => $amountReceived,
            'change' => $change,
        ];
    }
}