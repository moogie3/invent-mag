<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesService
{
    public function createSale(array $data)
    {
        $products = json_decode($data['products'], true);
        if (!$products || !is_array($products)) {
            throw new \Exception('Invalid product data');
        }

        $subTotal = 0;
        $itemsData = [];

        foreach ($products as $product) {
            $quantity = $product['quantity'];
            $price = $product['price'];
            $discount = $product['discount'] ?? 0;
            $discountType = $product['discountType'] ?? 'fixed';

            $productSubtotal = $price * $quantity;

            if ($discountType === 'percentage') {
                $itemDiscountAmount = ($productSubtotal * $discount) / 100;
            } else {
                $itemDiscountAmount = $discount * $quantity;
            }

            $itemTotal = $productSubtotal - $itemDiscountAmount;
            $subTotal += $itemTotal;

            $itemsData[] = [
                'product' => $product,
                'productSubtotal' => $productSubtotal,
                'itemDiscountAmount' => $itemDiscountAmount,
                'itemTotal' => $itemTotal,
            ];
        }

        $orderDiscount = $data['discount_total'] ?? 0;
        $orderDiscountType = $data['discount_total_type'] ?? 'fixed';

        if ($orderDiscountType === 'percentage') {
            $orderDiscountAmount = ($subTotal * $orderDiscount) / 100;
        } else {
            $orderDiscountAmount = $orderDiscount;
        }

        $taxableAmount = $subTotal - $orderDiscountAmount;
        $tax = Tax::where('is_active', 1)->first();
        $taxRate = $tax ? $tax->rate : 0;
        $taxAmount = $taxableAmount * ($taxRate / 100);

        $grandTotal = $taxableAmount + $taxAmount;

        if (empty($data['invoice'])) {
            $lastInvoice = Sales::latest()->first();
            $invoiceNumber = $lastInvoice ? intval(substr($lastInvoice->invoice, -4)) + 1 : 1;
            $invoice = 'INV-' . str_pad($invoiceNumber, 4, '0', STR_PAD_LEFT);
        } else {
            $invoice = $data['invoice'];
        }

        $sale = Sales::create([
            'invoice' => $invoice,
            'customer_id' => $data['customer_id'],
            'order_date' => $data['order_date'],
            'due_date' => $data['due_date'],
            'tax_rate' => $taxRate,
            'total_tax' => $taxAmount,
            'total' => $grandTotal,
            'order_discount' => $orderDiscount,
            'order_discount_type' => $orderDiscountType,
            'status' => 'Unpaid',
            'is_pos' => 0,
        ]);

        foreach ($itemsData as $index => $itemData) {
            $product = $itemData['product'];

            SalesItem::create([
                'sales_id' => $sale->id,
                'product_id' => $product['id'],
                'quantity' => $product['quantity'],
                'customer_price' => $product['price'],
                'discount' => $product['discount'] ?? 0,
                'discount_type' => $product['discountType'] ?? 'fixed',
                'total' => $itemData['itemTotal'],
            ]);

            $productModel = Product::find($product['id']);
            if ($productModel) {
                if (isset($productModel->stock_quantity)) {
                    $productModel->decrement('stock_quantity', $product['quantity']);
                } elseif (isset($productModel->quantity)) {
                    $productModel->decrement('quantity', $product['quantity']);
                } elseif (isset($productModel->stock)) {
                    $productModel->decrement('stock', $product['quantity']);
                }
            }
        }

        return $sale;
    }

    public function updateSale(Sales $sale, array $data)
    {
        $sale->payment_type = $data['payment_type'];
        $sale->status = $data['status'];
        $sale->payment_date = $data['status'] === 'Paid' ? now() : null;
        $sale->order_discount_type = $data['order_discount_type'] ?? 'fixed';
        $sale->order_discount = $data['order_discount'] ?? 0;

        $subTotal = 0;
        $itemDiscountTotal = 0;

        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $itemId => $itemData) {
                $salesItem = SalesItem::findOrFail($itemId);

                $quantity = (int) $itemData['quantity'];
                $price = (float) $itemData['price'];
                $discount = (float) $itemData['discount'];
                $discountType = $itemData['discount_type'] ?? 'fixed';

                $discountAmount = $discountType === 'percentage' ? (($price * $discount) / 100) * $quantity : $discount * $quantity;

                $productSubtotal = $price * $quantity;
                $itemTotal = $productSubtotal - $discountAmount;

                $itemDiscountTotal += $discountAmount;
                $subTotal += $itemTotal;

                $salesItem->update([
                    'quantity' => $quantity,
                    'customer_price' => $price,
                    'discount' => $discount,
                    'discount_type' => $discountType,
                    'total' => $itemTotal,
                ]);
            }
        }

        $orderDiscountAmount = $sale->order_discount_type === 'percentage' ? ($subTotal * $sale->order_discount) / 100 : $sale->order_discount;

        $taxable = $subTotal - $orderDiscountAmount;
        $taxRate = $sale->tax_rate;
        $taxAmount = $taxable * ($taxRate / 100);

        $sale->total_tax = $taxAmount;
        $sale->total = $taxable + $taxAmount;
        $sale->save();

        return $sale;
    }

    public function deleteSale(Sales $sale)
    {
        DB::transaction(function () use ($sale) {
            $isPaid = $sale->status === 'Paid';

            SalesItem::where('sales_id', $sale->id)->delete();

            if (!$isPaid) {
                foreach ($sale->salesItems as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        if (isset($product->stock_quantity)) {
                            $product->increment('stock_quantity', $item->quantity);
                        } elseif (isset($product->quantity)) {
                            $product->increment('quantity', $item->quantity);
                        } elseif (isset($product->stock)) {
                            $product->increment('stock', $item->quantity);
                        }
                    }
                }
            }

            $sale->delete();
        });
    }

    public function bulkDeleteSales(array $ids)
    {
        DB::transaction(function () use ($ids) {
            $salesOrders = Sales::whereIn('id', $ids)->with('salesItems')->get();

            if ($salesOrders->isEmpty()) {
                throw new \Exception('No sales orders found with the provided IDs');
            }

            SalesItem::whereIn('sales_id', $ids)->delete();

            foreach ($salesOrders as $sale) {
                if ($sale->status !== 'Paid') {
                    foreach ($sale->salesItems as $item) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            if (isset($product->stock_quantity)) {
                                $product->increment('stock_quantity', $item->quantity);
                            } elseif (isset($product->quantity)) {
                                $product->increment('quantity', $item->quantity);
                            } elseif (isset($product->stock)) {
                                $product->increment('stock', $item->quantity);
                            }
                        }
                    }
                }
            }

            Sales::whereIn('id', $ids)->delete();
        });
    }

    public function bulkMarkPaid(array $ids)
    {
        $updatedCount = 0;
        $salesOrders = Sales::whereIn('id', $ids)->get();

        foreach ($salesOrders as $salesOrder) {
            if ($salesOrder->status === 'Paid') {
                continue;
            }

            $salesOrder->update([
                'status' => 'Paid',
                'payment_date' => now(),
                'updated_at' => now(),
            ]);

            $updatedCount++;
        }

        return $updatedCount;
    }
}