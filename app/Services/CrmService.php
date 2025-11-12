<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\CustomerInteraction;
use App\Models\SupplierInteraction;
use Illuminate\Support\Facades\Auth;
use App\Helpers\CurrencyHelper;
use App\Models\Sales;

class CrmService
{
    public function getCustomerCrmData($id, $page)
    {
        $customer = Customer::with(['interactions.user'])->findOrFail($id);

        $allSales = $customer->sales()->with('salesItems.product.category')->get();
        $lifetimeValue = $allSales->sum('total');
        $totalSalesCount = $allSales->count();
        $averageOrderValue = $totalSalesCount > 0 ? $lifetimeValue / $totalSalesCount : 0;

        $categoryCounts = [];
        $productQuantities = [];
        $totalProductsPurchased = 0;

        foreach ($allSales as $sale) {
            foreach ($sale->salesItems as $item) {
                if ($item->product) {
                    if ($item->product->category) {
                        $categoryName = $item->product->category->name;
                        if (!isset($categoryCounts[$categoryName])) {
                            $categoryCounts[$categoryName] = 0;
                        }
                        $categoryCounts[$categoryName]++;
                    }

                    $productName = $item->product->name;
                    if (!isset($productQuantities[$productName])) {
                        $productQuantities[$productName] = 0;
                    }
                    $productQuantities[$productName] += $item->quantity;
                    $totalProductsPurchased += $item->quantity;
                }
            }
        }
        arsort($categoryCounts);
        $favoriteCategory = !empty($categoryCounts) ? key($categoryCounts) : 'N/A';

        arsort($productQuantities);
        $mostPurchasedProduct = !empty($productQuantities) ? key($productQuantities) : 'N/A';

        $lastInteractionDate = $customer->interactions->max('interaction_date');

        $sales = $customer->sales()->with('salesItems.product')
                        ->orderByDesc('created_at')
                        ->paginate(10, ['*'], 'page', $page);

        return [
            'customer' => $customer,
            'lifetimeValue' => $lifetimeValue,
            'totalSalesCount' => $totalSalesCount,
            'averageOrderValue' => $averageOrderValue,
            'favoriteCategory' => $favoriteCategory,
            'lastPurchaseDate' => $allSales->max('created_at'),
            'lastInteractionDate' => $lastInteractionDate,
            'mostPurchasedProduct' => $mostPurchasedProduct,
            'totalProductsPurchased' => $totalProductsPurchased,
            'sales' => $sales,
            'currencySettings' => CurrencyHelper::getSettings(),
        ];
    }

    public function getSupplierCrmData($id, $page)
    {
        $supplier = Supplier::with(['interactions.user'])->findOrFail($id);

        $allPurchases = $supplier->purchases()->with('items.product')->get();
        $lifetimeValue = $allPurchases->sum('total');
        $totalPurchasesCount = $allPurchases->count();
        $averagePurchaseValue = $totalPurchasesCount > 0 ? $lifetimeValue / $totalPurchasesCount : 0;

        $categoryCounts = [];
        $productQuantities = [];
        $totalProductsPurchased = 0;

        foreach ($allPurchases as $purchase) {
            if ($purchase->items) {
                foreach ($purchase->items as $item) {
                    if ($item->product) {
                        if ($item->product->category) {
                            $categoryName = $item->product->category->name;
                            if (!isset($categoryCounts[$categoryName])) {
                                $categoryCounts[$categoryName] = 0;
                            }
                            $categoryCounts[$categoryName]++;
                        }

                        $productName = $item->product->name;
                        if (!isset($productQuantities[$productName])) {
                            $productQuantities[$productName] = 0;
                        }
                        $productQuantities[$productName] += $item->quantity;
                        $totalProductsPurchased += $item->quantity;
                    }
                }
            }
        }
        arsort($categoryCounts);
        $favoriteCategory = !empty($categoryCounts) ? key($categoryCounts) : 'N/A';

        arsort($productQuantities);
        $mostPurchasedProduct = !empty($productQuantities) ? key($productQuantities) : 'N/A';

        $lastInteractionDate = $supplier->interactions->max('interaction_date');

        $purchases = $supplier->purchases()->with('items.product')
                        ->orderByDesc('created_at')
                        ->paginate(10, ['*'], 'page', $page);

        return [
            'supplier' => $supplier,
            'lifetimeValue' => $lifetimeValue,
            'totalPurchasesCount' => $totalPurchasesCount,
            'averagePurchaseValue' => $averagePurchaseValue,
            'favoriteCategory' => $favoriteCategory,
            'lastPurchaseDate' => $allPurchases->max('order_date'),
            'lastInteractionDate' => $lastInteractionDate,
            'mostPurchasedProduct' => $mostPurchasedProduct,
            'totalProductsPurchased' => $totalProductsPurchased,
            'purchases' => $purchases,
            'currencySettings' => CurrencyHelper::getSettings(),
        ];
    }

    public function getSupplierHistoricalPurchases($id)
    {
        $supplier = Supplier::findOrFail($id);

        $historicalPurchases = $supplier->purchases()
            ->with('items.product')
            ->orderByDesc('order_date')
            ->get()
            ->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'invoice' => $purchase->invoice,
                    'order_date' => $purchase->order_date,
                    'due_date' => $purchase->due_date,
                    'payment_method' => $purchase->payment_type,
                    'status' => $purchase->status,
                    'total' => $purchase->total,
                    'discount_amount' => $purchase->discount_total,
                    'items' => $purchase->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_name' => $item->product->name,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'total' => $item->total,
                        ];
                    }),
                ];
            });

        return $historicalPurchases;
    }

    public function getHistoricalPurchases(Customer $customer)
    {
        $sales = Sales::where('customer_id', $customer->id)
            ->with('salesItems.product')
            ->orderBy('order_date', 'desc')
            ->get();

        $historicalPurchases = [];
        $latestProductPrices = [];

        foreach ($sales as $sale) {
            foreach ($sale->salesItems as $item) {
                if ($item->product) {
                    $productId = $item->product->id;
                    $purchaseDate = $sale->order_date;

                    $historicalPurchases[] = [
                        'sale_id' => $sale->id,
                        'invoice' => $sale->invoice,
                        'order_date' => $purchaseDate,
                        'product_id' => $productId,
                        'product_name' => $item->product->name,
                        'quantity' => $item->quantity,
                        'price_at_purchase' => $item->price,
                        'line_total' => $item->total,
                    ];

                    if (!isset($latestProductPrices[$productId]) || $purchaseDate > $latestProductPrices[$productId]['date']) {
                        $latestProductPrices[$productId] = [
                            'price' => $item->price,
                            'date' => $purchaseDate,
                        ];
                    }
                }
            }
        }

        foreach ($historicalPurchases as &$purchase) {
            $productId = $purchase['product_id'];
            if (isset($latestProductPrices[$productId])) {
                $purchase['customer_latest_price'] = $latestProductPrices[$productId]['price'];
            } else {
                $purchase['customer_latest_price'] = $purchase['price_at_purchase'];
            }
        }

        return $historicalPurchases;
    }

    public function storeCustomerInteraction(array $data, $customerId)
    {
        $interaction = new CustomerInteraction([
            'customer_id' => $customerId,
            'user_id' => Auth::id(),
            'type' => $data['type'],
            'notes' => $data['notes'],
            'interaction_date' => $data['interaction_date'],
        ]);
        $interaction->save();

        return $interaction->load('user');
    }

    public function storeSupplierInteraction(array $data, $supplierId)
    {
        $interaction = new SupplierInteraction([
            'supplier_id' => $supplierId,
            'user_id' => Auth::id(),
            'type' => $data['type'],
            'notes' => $data['notes'],
            'interaction_date' => $data['interaction_date'],
        ]);
        $interaction->save();

        return $interaction->load('user');
    }

    public function getCustomerProductHistory($id)
    {
        $customer = Customer::findOrFail($id);

        $salesItems = \App\Models\SalesItem::whereIn('sales_id', $customer->sales()->pluck('id'))
            ->with(['product', 'sale'])
            ->get();

        $productHistory = $salesItems->groupBy('product.name')
            ->map(function ($items, $productName) {
                $history = $items->sortByDesc('sale.order_date')->map(function ($item) {
                    return [
                        'invoice' => $item->sale->invoice,
                        'order_date' => $item->sale->order_date,
                        'quantity' => $item->quantity,
                        'price_at_purchase' => $item->customer_price,
                    ];
                })->values();

                $lastPrice = $history->first()['price_at_purchase'];

                return [
                    'product_name' => $productName,
                    'last_price' => $lastPrice,
                    'history' => $history,
                ];
            })
            ->sortBy('product_name')
            ->values();

        return $productHistory;
    }

    public function getSupplierProductHistory($id)
    {
        $supplier = Supplier::findOrFail($id);

        return $supplier->purchases()
            ->with('items.product')
            ->orderByDesc('order_date')
            ->get()
            ->flatMap(function ($purchase) {
                return $purchase->items->map(function ($item) use ($purchase) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product ? $item->product->name : 'N/A',
                        'order_date' => $purchase->order_date,
                        'invoice' => $purchase->invoice,
                        'quantity' => $item->quantity,
                        'price_at_purchase' => $item->price ?? 0,
                        'line_total' => $item->total
                    ];
                });
            })
            ->groupBy('product_name')
            ->map(function ($productPurchases, $productName) {
                $latestPurchase = $productPurchases->first();
                return [
                    'product_name' => $productName,
                    'last_price' => $latestPurchase ? $latestPurchase['price_at_purchase'] : 0,
                    'history' => $productPurchases->values()
                ];
            })
            ->values();
    }
}
