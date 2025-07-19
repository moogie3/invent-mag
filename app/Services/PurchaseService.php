<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\POItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\PurchaseHelper;
use App\Models\Supplier;
use App\Models\User;

class PurchaseService
{
    public function getPurchaseIndexData(array $filters, int $entries)
    {
        $query = Purchase::with(['items', 'supplier', 'user']);

        if (isset($filters['month']) && $filters['month']) {
            $query->whereMonth('order_date', $filters['month']);
        }

        if (isset($filters['year']) && $filters['year']) {
            $query->whereYear('order_date', $filters['year']);
        }

        $pos = $query->paginate($entries);
        $totalinvoice = Purchase::count();
        $items = POItem::all();
        $inCount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'IN');
        })->count();
        $inCountamount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'IN');
        })
            ->where('status', 'Unpaid')
            ->sum('total');

        $outCount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'OUT');
        })->count();
        $outCountamount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'OUT');
        })
            ->where('status', 'Unpaid')
            ->sum('total');

        $totalMonthly = Purchase::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total');
        $paymentMonthly = Purchase::whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->where('status', 'Paid')->sum('total');
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
        $pos = Purchase::with(['items', 'supplier'])->find($id);
        $suppliers = Supplier::all();
        $items = POItem::all();
        $isPaid = $pos->status == 'Paid';

        return compact('pos', 'suppliers', 'items', 'isPaid');
    }

    public function createPurchase(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $products = json_decode($data['products'], true);
            if (!$products || !is_array($products)) {
                throw new \Exception('Invalid product data');
            }

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
                    'price' => $productData['price'],
                    'discount' => $productData['discount'] ?? 0,
                    'discount_type' => $productData['discount_type'] ?? 'fixed',
                    'total' => $itemTotal,
                ]);
                $totalAmount += $itemTotal;

                $product = Product::find($productData['product_id']);
                if ($product) {
                    $product->increment('stock_quantity', $productData['quantity']);
                }
            }

            $finalTotal = PurchaseHelper::calculateDiscount($totalAmount, $purchase->discount_total, $purchase->discount_total_type);
            $purchase->update(['total' => $finalTotal]);

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

            // Revert old stock quantities
            foreach ($purchase->items as $oldItem) {
                $product = Product::find($oldItem->product_id);
                if ($product) {
                    $product->decrement('stock_quantity', $oldItem->quantity);
                }
            }

            $purchase->items()->delete();

            $purchase->update([
                'supplier_id' => $data['supplier_id'],
                'order_date' => $data['order_date'],
                'due_date' => $data['due_date'],
                'discount_total' => $data['discount_total'] ?? 0,
                'discount_total_type' => $data['discount_total_type'] ?? 'fixed',
                'status' => $data['status'] ?? 'Unpaid',
            ]);

            $totalAmount = 0;
            foreach ($products as $productData) {
                $itemTotal = PurchaseHelper::calculateTotal($productData['price'], $productData['quantity'], $productData['discount'] ?? 0, $productData['discount_type'] ?? 'fixed');
                POItem::create([
                    'po_id' => $purchase->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'price' => $productData['price'],
                    'discount' => $productData['discount'] ?? 0,
                    'discount_type' => $productData['discount_type'] ?? 'fixed',
                    'total' => $itemTotal,
                ]);
                $totalAmount += $itemTotal;

                $product = Product::find($productData['product_id']);
                if ($product) {
                    $product->increment('stock_quantity', $productData['quantity']);
                }
            }

            $finalTotal = PurchaseHelper::calculateDiscount($totalAmount, $purchase->discount_total, $purchase->discount_total_type);
            $purchase->update(['total' => $finalTotal]);

            return $purchase;
        });
    }

    public function deletePurchase(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            foreach ($purchase->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->decrement('stock_quantity', $item->quantity);
                }
            }
            $purchase->delete();
        });
    }

    public function bulkDeletePurchases(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            $purchases = Purchase::whereIn('id', $ids)->with('items')->get();
            foreach ($purchases as $purchase) {
                foreach ($purchase->items as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->decrement('stock_quantity', $item->quantity);
                    }
                }
                $purchase->delete();
            }
        });
    }

    public function bulkMarkPaid(array $ids)
    {
        $updatedCount = 0;
        DB::transaction(function () use ($ids, &$updatedCount) {
            $updatedCount = Purchase::whereIn('id', $ids)->update(['status' => 'Paid']);
        });
        return $updatedCount;
    }

    public function getPurchaseMetrics()
    {
        $totalinvoice = Purchase::count();
        $inCount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'IN');
        })->count();
        $inCountamount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'IN');
        })
            ->where('status', 'Unpaid')
            ->sum('total');

        $outCount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'OUT');
        })->count();
        $outCountamount = Purchase::whereHas('supplier', function ($query) {
            $query->where('location', 'OUT');
        })
            ->where('status', 'Unpaid')
            ->sum('total');

        $totalMonthly = Purchase::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total');
        $paymentMonthly = Purchase::whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->where('status', 'Paid')->sum('total');

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
}