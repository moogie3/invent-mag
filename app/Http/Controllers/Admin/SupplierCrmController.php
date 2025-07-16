<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierInteraction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierCrmController extends Controller
{
    public function show(Request $request, $id)
    {
        try {
            $supplier = Supplier::with(['interactions.user'])->findOrFail($id);

            // Calculate lifetime value and favorite category from ALL purchases for accuracy
            $allPurchases = $supplier->purchases()->with('items.product')->get();
            $lifetimeValue = $allPurchases->sum('total_amount');
            $totalPurchasesCount = $allPurchases->count();
            $averageOrderValue = $totalPurchasesCount > 0 ? $lifetimeValue / $totalPurchasesCount : 0;

            $categoryCounts = [];
            $productQuantities = [];
            $totalProductsPurchased = 0;

            foreach ($allPurchases as $purchase) {
                if ($purchase->items) {
                    foreach ($purchase->items as $item) {
                        if ($item->product) {
                            // For Favorite Category
                            if ($item->product->category) {
                                $categoryName = $item->product->category->name;
                                if (!isset($categoryCounts[$categoryName])) {
                                    $categoryCounts[$categoryName] = 0;
                                }
                                $categoryCounts[$categoryName]++;
                            } else {
                                \Illuminate\Support\Facades\Log::warning("Product category is null for POItem ID: " . $item->id);
                            }

                            // For Most Purchased Product and Total Products Purchased
                            $productName = $item->product->name;
                            if (!isset($productQuantities[$productName])) {
                                $productQuantities[$productName] = 0;
                            }
                            $productQuantities[$productName] += $item->quantity;
                            $totalProductsPurchased += $item->quantity;
                        } else {
                            \Illuminate\Support\Facades\Log::warning("Product is null for POItem ID: " . $item->id);
                        }
                    }
                } else {
                    \Illuminate\Support\Facades\Log::warning("Purchase has no items for Purchase ID: " . $purchase->id);
                }
            }
            arsort($categoryCounts);
            $favoriteCategory = !empty($categoryCounts) ? key($categoryCounts) : 'N/A';

            arsort($productQuantities);
            $mostPurchasedProduct = !empty($productQuantities) ? key($productQuantities) : 'N/A';

            $lastInteractionDate = $supplier->interactions->max('interaction_date');

            // Paginate purchases for display in the modal
            $purchases = $supplier->purchases()->with('items.product')
                            ->orderByDesc('created_at')
                            ->paginate(10, ['*'], 'page', $request->input('page', 1));

            return response()->json([
                'supplier' => $supplier,
                'lifetimeValue' => $lifetimeValue,
                'totalPurchasesCount' => $totalPurchasesCount,
                'averageOrderValue' => $averageOrderValue,
                'favoriteCategory' => $favoriteCategory,
                'lastPurchaseDate' => $allPurchases->max('order_date'),
                'lastInteractionDate' => $lastInteractionDate,
                'mostPurchasedProduct' => $mostPurchasedProduct,
                'totalProductsPurchased' => $totalProductsPurchased,
                'purchases' => $purchases, // Pass paginated purchases data
            ]);
        } catch (\Exception $e) {
            // Log the exception for debugging
            \Illuminate\Support\Facades\Log::error("Error in SupplierCrmController@show: " . $e->getMessage());
            return response()->json(['error' => 'Failed to load SRM data: ' . $e->getMessage()], 500);
        }
    }

    public function storeInteraction(Request $request, $supplierId)
    {
        $request->validate([
            'type' => 'required|string',
            'notes' => 'required|string',
            'interaction_date' => 'required|date',
        ]);

        $interaction = new SupplierInteraction([
            'supplier_id' => $supplierId,
            'user_id' => Auth::id(),
            'type' => $request->type,
            'notes' => $request->notes,
            'interaction_date' => $request->interaction_date,
        ]);
        $interaction->save();

        return response()->json($interaction->load('user'));
    }

    public function getHistoricalPurchases(Request $request, $id)
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
                    'total_amount' => $purchase->grand_total,
                    'discount_amount' => $purchase->discount_total,
                    'purchase_items' => $purchase->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product' => $item->product,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'total' => $item->total,
                        ];
                    }),
                ];
            });

        return response()->json([
            'historical_purchases' => $historicalPurchases,
        ]);
    }

    public function getProductHistory(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $productHistory = $supplier->purchases()
            ->with('items.product')
            ->orderByDesc('order_date')
            ->get()
            ->flatMap(function ($purchase) {
                return $purchase->items->map(function ($item) use ($purchase) {
                    return [
                        'order_date' => $purchase->order_date,
                        'invoice' => $purchase->invoice,
                        'product_name' => $item->product ? $item->product->name : 'N/A',
                        'quantity' => $item->quantity,
                        'price_at_purchase' => $item->price ?? 0,
                        'supplier_latest_price' => $item->product ? ($item->product->price ?? 0) : 0,
                        'line_total' => $item->total
                    ];
                });
            });

        return response()->json([
            'product_history' => $productHistory,
        ]);
    }
}