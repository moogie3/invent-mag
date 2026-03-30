<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\CustomerInteraction;
use App\Models\SupplierInteraction;
use App\Models\Sales;
use App\Models\Purchase;
use App\Helpers\CurrencyHelper;
use App\Helpers\PurchaseHelper;
use App\Helpers\SalesHelper;
use Illuminate\Support\Facades\Auth;

class CrmService
{
    public function getCustomerCrmData($id, $page)
    {
        $customer = Customer::with(['interactions.user'])->findOrFail($id);

        $allSales = $customer->sales()->with(['salesItems.product.category', 'salesReturns.items'])->get();
        $lifetimeValue = $allSales->sum(function($sale) {
            return $sale->getAdjustedTotal();
        });
        $totalSalesCount = $allSales->count();
        $averageOrderValue = $totalSalesCount > 0 ? $lifetimeValue / $totalSalesCount : 0;

        $categoryCounts = [];
        $productQuantities = [];
        $totalProductsPurchased = 0;

        foreach ($allSales as $sale) {
            foreach ($sale->salesItems as $item) {
                if ($item->product) {
                    $returnedQty = $sale->getReturnedQuantityForProduct($item->product_id);
                    $adjustedQty = max(0, $item->quantity - $returnedQty);

                    if ($adjustedQty > 0) {
                        if ($item->product->category) {
                            $categoryName = $item->product->category->name;
                            if (!isset($categoryCounts[$categoryName])) {
                                $categoryCounts[$categoryName] = 0;
                            }
                            $categoryCounts[$categoryName] += $adjustedQty;
                        }

                        $productName = $item->product->name;
                        if (!isset($productQuantities[$productName])) {
                            $productQuantities[$productName] = 0;
                        }
                        $productQuantities[$productName] += $adjustedQty;
                        $totalProductsPurchased += $adjustedQty;
                    }
                }
            }
        }
        arsort($categoryCounts);
        $favoriteCategory = !empty($categoryCounts) ? key($categoryCounts) : 'N/A';

        arsort($productQuantities);
        $mostPurchasedProduct = !empty($productQuantities) ? key($productQuantities) : 'N/A';

        $lastInteractionDate = $customer->interactions->max('interaction_date');

        $sales = $customer->sales()->with(['salesItems.product', 'salesReturns.items', 'payments'])
                        ->orderByDesc('created_at')
                        ->paginate(10, ['*'], 'page', $page);

        $sales->getCollection()->transform(function ($sale) {
            $sale->returned_amount = $sale->getTotalReturnedAmount();

            $summary = SalesHelper::calculateInvoiceSummaryWithReturns(
                $sale->salesItems,
                $sale->order_discount ?? 0,
                $sale->order_discount_type ?? 'fixed',
                $sale->tax_rate ?? 0,
                $sale->relationLoaded('salesReturns') ? $sale->salesReturns : collect(),
            );

            $paid = $sale->payments->sum('amount');
            $sale->total_paid = $paid;
            $sale->summary_subtotal = $summary['subtotal'];
            $sale->summary_order_discount = $summary['orderDiscount'];
            $sale->summary_tax_amount = $summary['taxAmount'];
            $sale->grand_total_adjusted = $summary['finalTotal'];
            $sale->balance = max(0, round($summary['finalTotal'] - $paid, 2));

            $sale->salesItems->transform(function ($item) use ($sale) {
                $returnedQty = $sale->getReturnedQuantityForProduct($item->product_id);
                $adjustedQty = max(0, (int) $item->quantity - $returnedQty);
                $item->returned_quantity = $returnedQty;
                $item->adjusted_quantity = $adjustedQty;
                $item->adjusted_total = SalesHelper::calculateTotal(
                    $item->customer_price,
                    $adjustedQty,
                    $item->discount,
                    $item->discount_type
                );
                return $item;
            });
            return $sale;
        });

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

        $allPurchases = $supplier->purchases()->with(['items.product', 'purchaseReturns.items'])->get();
        $lifetimeValue = $allPurchases->sum(function($purchase) {
            return $purchase->getAdjustedTotal();
        });
        $totalPurchasesCount = $allPurchases->count();
        $averagePurchaseValue = $totalPurchasesCount > 0 ? $lifetimeValue / $totalPurchasesCount : 0;

        $categoryCounts = [];
        $productQuantities = [];
        $totalProductsPurchased = 0;

        foreach ($allPurchases as $purchase) {
            if ($purchase->items) {
                foreach ($purchase->items as $item) {
                    if ($item->product) {
                        $returnedQty = $purchase->getReturnedQuantityForProduct($item->product_id);
                        $adjustedQty = max(0, $item->quantity - $returnedQty);

                        if ($adjustedQty > 0) {
                            if ($item->product->category) {
                                $categoryName = $item->product->category->name;
                                if (!isset($categoryCounts[$categoryName])) {
                                    $categoryCounts[$categoryName] = 0;
                                }
                                $categoryCounts[$categoryName] += $adjustedQty;
                            }

                            $productName = $item->product->name;
                            if (!isset($productQuantities[$productName])) {
                                $productQuantities[$productName] = 0;
                            }
                            $productQuantities[$productName] += $adjustedQty;
                            $totalProductsPurchased += $adjustedQty;
                        }
                    }
                }
            }
        }
        arsort($categoryCounts);
        $favoriteCategory = !empty($categoryCounts) ? key($categoryCounts) : 'N/A';

        arsort($productQuantities);
        $mostPurchasedProduct = !empty($productQuantities) ? key($productQuantities) : 'N/A';

        $lastInteractionDate = $supplier->interactions->max('interaction_date');

        $purchases = $supplier->purchases()->with(['items.product', 'purchaseReturns.items', 'payments'])
                        ->orderByDesc('created_at')
                        ->paginate(10, ['*'], 'page', $page);

        $purchases->getCollection()->transform(function ($purchase) {
            $purchase->returned_amount = $purchase->getTotalReturnedAmount();

            $summary = PurchaseHelper::calculateInvoiceSummaryWithReturns(
                $purchase->items,
                $purchase->discount_total ?? 0,
                $purchase->discount_total_type ?? 'fixed',
                $purchase->relationLoaded('purchaseReturns') ? $purchase->purchaseReturns : collect(),
            );

            $paid = $purchase->payments->sum('amount');
            $purchase->total_paid = $paid;
            $purchase->summary_subtotal = $summary['subtotal'];
            $purchase->summary_order_discount = $summary['orderDiscount'];
            $purchase->grand_total_adjusted = $summary['finalTotal'];
            $purchase->balance = max(0, round($summary['finalTotal'] - $paid, 2));

            $purchase->items->transform(function ($item) use ($purchase) {
                $returnedQty = $purchase->getReturnedQuantityForProduct($item->product_id);
                $adjustedQty = max(0, (int) $item->quantity - $returnedQty);
                $item->returned_quantity = $returnedQty;
                $item->adjusted_quantity = $adjustedQty;
                $item->adjusted_total = PurchaseHelper::calculateTotal(
                    $item->price,
                    $adjustedQty,
                    $item->discount,
                    $item->discount_type
                );
                return $item;
            });
            return $purchase;
        });

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
        $purchases = Purchase::where('supplier_id', $id)
            ->with(['items.product', 'purchaseReturns.items', 'payments'])
            ->orderBy('order_date', 'desc')
            ->get();

        $groupedPurchases = [];

        foreach ($purchases as $purchase) {
            $returnedAmount = $purchase->getTotalReturnedAmount();

            $summary = PurchaseHelper::calculateInvoiceSummaryWithReturns(
                $purchase->items,
                $purchase->discount_total ?? 0,
                $purchase->discount_total_type ?? 'fixed',
                $purchase->relationLoaded('purchaseReturns') ? $purchase->purchaseReturns : collect(),
            );

            $paid = $purchase->payments->sum('amount');
            $balance = max(0, round($summary['finalTotal'] - $paid, 2));

            $purchase->items->transform(function ($item) use ($purchase) {
                $returnedQty = $purchase->getReturnedQuantityForProduct($item->product_id);
                $adjustedQty = max(0, (int) $item->quantity - $returnedQty);
                $item->returned_quantity = $returnedQty;
                $item->adjusted_quantity = $adjustedQty;
                $item->adjusted_total = PurchaseHelper::calculateTotal(
                    $item->price,
                    $adjustedQty,
                    $item->discount,
                    $item->discount_type
                );
                return $item;
            });

            $groupedPurchases[] = [
                'id' => $purchase->id,
                'invoice' => $purchase->invoice,
                'order_date' => $purchase->order_date,
                'due_date' => $purchase->due_date,
                'status' => $purchase->status,
                'payment_method' => $purchase->payment_method,
                'items' => $purchase->items,
                'summary_subtotal' => $summary['subtotal'],
                'summary_order_discount' => $summary['orderDiscount'],
                'grand_total_adjusted' => $summary['finalTotal'],
                'returned_amount' => $returnedAmount,
                'total_paid' => $paid,
                'balance' => $balance,
            ];
        }

        return $groupedPurchases;
    }

    public function getHistoricalPurchases(Customer $customer)
    {
        $sales = Sales::where('customer_id', $customer->id)
            ->with(['salesItems.product', 'salesReturns.items'])
            ->orderBy('order_date', 'desc')
            ->get();

        $historicalPurchases = [];
        $latestProductPrices = [];

        foreach ($sales as $sale) {
            foreach ($sale->salesItems as $item) {
                if ($item->product) {
                    $returnedQty = $sale->getReturnedQuantityForProduct($item->product_id);
                    $adjustedQty = max(0, $item->quantity - $returnedQty);

                    if ($adjustedQty > 0) {
                        $productId = $item->product->id;
                        $purchaseDate = $sale->order_date;

                        $adjustedTotal = SalesHelper::calculateTotal(
                            $item->customer_price,
                            $adjustedQty,
                            $item->discount,
                            $item->discount_type
                        );

                        $historicalPurchases[] = [
                            'sale_id' => $sale->id,
                            'invoice' => $sale->invoice,
                            'order_date' => $purchaseDate,
                            'product_id' => $productId,
                            'product_name' => $item->product->name,
                            'quantity' => $adjustedQty,
                            'original_quantity' => $item->quantity,
                            'returned_quantity' => $returnedQty,
                            'price_at_purchase' => $item->customer_price,
                            'line_total' => $adjustedTotal,
                        ];

                        if (!isset($latestProductPrices[$productId]) || $purchaseDate > $latestProductPrices[$productId]['date']) {
                            $latestProductPrices[$productId] = [
                                'price' => $item->customer_price,
                                'date' => $purchaseDate,
                            ];
                        }
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
            ->with(['product', 'sales.salesReturns.items'])
            ->get();

        $productHistory = $salesItems->groupBy('product.name')
            ->map(function ($items, $productName) {
                $history = $items->sortByDesc('sales.order_date')->map(function ($item) {
                    if (!$item->product || !$item->sales) {
                        return null;
                    }

                    $returnedQty = $item->sales->getReturnedQuantityForProduct($item->product_id);
                    $adjustedQty = max(0, $item->quantity - $returnedQty);

                    if ($adjustedQty <= 0) {
                        return null;
                    }

                    return [
                        'invoice' => $item->sales->invoice,
                        'order_date' => $item->sales->order_date,
                        'quantity' => $adjustedQty,
                        'original_quantity' => $item->quantity,
                        'returned_quantity' => $returnedQty,
                        'price_at_purchase' => $item->customer_price,
                    ];
                })->filter()->values();

                $lastPrice = $history->isNotEmpty() ? $history->first()['price_at_purchase'] : null;

                return [
                    'product_name' => $productName,
                    'last_price' => $lastPrice,
                    'history' => $history,
                ];
            })
            ->filter(function ($item) {
                return $item['history']->isNotEmpty();
            })
            ->sortBy('product_name')
            ->values();

        return $productHistory;
    }

    public function getSupplierProductHistory($id)
    {
        $supplier = Supplier::findOrFail($id);

        return $supplier->purchases()
            ->with(['items.product', 'purchaseReturns.items'])
            ->orderByDesc('order_date')
            ->get()
            ->flatMap(function ($purchase) {
                return $purchase->items->map(function ($item) use ($purchase) {
                    $returnedQty = $purchase->getReturnedQuantityForProduct($item->product_id);
                    $adjustedQty = max(0, $item->quantity - $returnedQty);

                    if ($adjustedQty <= 0) return null;

                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product ? $item->product->name : 'N/A',
                        'order_date' => $purchase->order_date,
                        'invoice' => $purchase->invoice,
                        'quantity' => $adjustedQty,
                        'original_quantity' => $item->quantity,
                        'returned_quantity' => $returnedQty,
                        'price_at_purchase' => $item->price ?? 0,
                        'line_total' => PurchaseHelper::calculateTotal($item->price, $adjustedQty, $item->discount, $item->discount_type)
                    ];
                })->filter();
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
