<?php

namespace App\Services;

use App\Models\POItem;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseService
{
    public function createPurchase(array $data)
    {
        $products = json_decode($data['products'], true);
        if (!$products || !is_array($products)) {
            throw new \Exception('Invalid product data');
        }

        $subtotal = 0;
        $totalProductDiscounts = 0;

        foreach ($products as $product) {
            $discountPerUnit = \App\Helpers\PurchaseHelper::calculateDiscountPerUnit($product['price'], $product['discount'], $product['discountType']);
            $finalAmount = \App\Helpers\PurchaseHelper::calculateTotal($product['price'], $product['quantity'], $product['discount'], $product['discountType']);
            $subtotal += $finalAmount;
            $totalProductDiscounts += $discountPerUnit * $product['quantity'];
        }

        $orderDiscountValue = $data['discount_total'] ?? 0;
        $orderDiscountType = $data['discount_total_type'] ?? 'fixed';
        $orderDiscountAmount = \App\Helpers\PurchaseHelper::calculateDiscount($subtotal, $orderDiscountValue, $orderDiscountType);

        $finalTotal = $subtotal - $orderDiscountAmount;

        $purchase = Purchase::create([
            'invoice' => $data['invoice'],
            'supplier_id' => $data['supplier_id'],
            'order_date' => $data['order_date'],
            'due_date' => $data['due_date'],
            'total' => floor($finalTotal),
            'discount_total' => $orderDiscountValue,
            'discount_total_type' => $orderDiscountType,
            'total_discount' => $totalProductDiscounts,
        ]);

        foreach ($products as $product) {
            $finalAmount = \App\Helpers\PurchaseHelper::calculateTotal($product['price'], $product['quantity'], $product['discount'], $product['discountType']);

            POItem::create([
                'po_id' => $purchase->id,
                'product_id' => $product['id'],
                'quantity' => $product['quantity'],
                'price' => $product['price'],
                'discount' => $product['discount'] ?? 0,
                'discount_type' => $product['discountType'] ?? 'percentage',
                'total' => floor($finalAmount),
            ]);

            $productModel = Product::find($product['id']);
            if ($productModel) {
                if (isset($productModel->stock_quantity)) {
                    $productModel->increment('stock_quantity', $product['quantity']);
                } elseif (isset($productModel->quantity)) {
                    $productModel->increment('quantity', $product['quantity']);
                } elseif (isset($productModel->stock)) {
                    $productModel->increment('stock', $product['quantity']);
                }
            }

            Product::where('id', $product['id'])->update([
                'price' => $product['price'],
            ]);
        }

        return $purchase;
    }

    public function updatePurchase(Purchase $purchase, array $data)
    {
        $purchase->payment_type = $data['payment_type'];
        $purchase->status = $data['status'];
        $purchase->payment_date = $data['status'] === 'Paid' ? now() : null;
        $purchase->discount_total = $data['discount_total'] ?? 0;
        $purchase->discount_total_type = $data['discount_total_type'] ?? 'fixed';

        $subtotal = 0;
        $totalProductDiscounts = 0;

        foreach ($data['items'] as $itemId => $itemData) {
            $poItem = POItem::findOrFail($itemId);

            $quantity = (int) $itemData['quantity'];
            $price = (int) $itemData['price'];
            $discount = (float) $itemData['discount'];
            $discountType = $itemData['discountType'] ?? 'percentage';

            $discountPerUnit = \App\Helpers\PurchaseHelper::calculateDiscountPerUnit($price, $discount, $discountType);
            $finalAmount = \App\Helpers\PurchaseHelper::calculateTotal($price, $quantity, $discount, $discountType);

            $totalProductDiscounts += $discountPerUnit * $quantity;

            $poItem->update([
                'quantity' => $quantity,
                'price' => $price,
                'discount' => $discount,
                'discount_type' => $discountType,
                'total' => floor($finalAmount),
            ]);

            Product::where('id', $poItem->product_id)->update([
                'price' => $price,
            ]);

            $subtotal += $finalAmount;
        }

        $orderDiscount = \App\Helpers\PurchaseHelper::calculateDiscount($subtotal, $purchase->discount_total, $purchase->discount_total_type);

        $purchase->total = floor($subtotal - $orderDiscount);
        $purchase->save();

        return $purchase;
    }

    public function deletePurchase(Purchase $purchase)
    {
        DB::transaction(function () use ($purchase) {
            $isPaid = $purchase->status === 'Paid';

            POItem::where('po_id', $purchase->id)->delete();

            if (!$isPaid) {
                foreach ($purchase->items as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        if (isset($product->stock_quantity)) {
                            $product->decrement('stock_quantity', $item->quantity);
                        } elseif (isset($product->quantity)) {
                            $product->decrement('quantity', $item->quantity);
                        } elseif (isset($product->stock)) {
                            $product->decrement('stock', $item->quantity);
                        }
                    }
                }
            }

            $purchase->delete();
        });
    }

    public function bulkDeletePurchases(array $ids)
    {
        DB::transaction(function () use ($ids) {
            $purchaseOrders = Purchase::whereIn('id', $ids)->with('items')->get();

            if ($purchaseOrders->isEmpty()) {
                throw new \Exception('No purchase orders found with the provided IDs');
            }

            POItem::whereIn('po_id', $ids)->delete();

            foreach ($purchaseOrders as $po) {
                if ($po->status !== 'Paid') {
                    foreach ($po->items as $item) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            if (isset($product->stock_quantity)) {
                                $product->decrement('stock_quantity', $item->quantity);
                            } elseif (isset($product->quantity)) {
                                $product->decrement('quantity', $item->quantity);
                            } elseif (isset($product->stock)) {
                                $product->decrement('stock', $item->quantity);
                            }
                        }
                    }
                }
            }

            Purchase::whereIn('id', $ids)->delete();
        });
    }

    public function bulkMarkPaid(array $ids)
    {
        $updatedCount = 0;
        $purchaseOrders = Purchase::whereIn('id', $ids)->get();

        foreach ($purchaseOrders as $purchaseOrder) {
            if ($purchaseOrder->status === 'Paid') {
                continue;
            }

            $purchaseOrder->update([
                'status' => 'Paid',
                'payment_date' => now(),
                'updated_at' => now(),
            ]);
            $updatedCount++;
        }

        return $updatedCount;
    }
}