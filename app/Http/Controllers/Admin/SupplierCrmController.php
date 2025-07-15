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
        $supplier = Supplier::with(['interactions.user'])->findOrFail($id);

        // Calculate lifetime value and favorite category from ALL purchases for accuracy
        $allPurchases = $supplier->purchases()->with('purchaseItems.product.category')->get();
        $lifetimeValue = $allPurchases->sum('total_amount');
        $totalPurchasesCount = $allPurchases->count();
        $averageOrderValue = $totalPurchasesCount > 0 ? $lifetimeValue / $totalPurchasesCount : 0;

        $categoryCounts = [];
        $productQuantities = [];
        $totalProductsPurchased = 0;

        foreach ($allPurchases as $purchase) {
            foreach ($purchase->purchaseItems as $item) {
                if ($item->product) {
                    // For Favorite Category
                    if ($item->product->category) {
                        $categoryName = $item->product->category->name;
                        if (!isset($categoryCounts[$categoryName])) {
                            $categoryCounts[$categoryName] = 0;
                        }
                        $categoryCounts[$categoryName]++;
                    }

                    // For Most Purchased Product and Total Products Purchased
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

        $lastInteractionDate = $supplier->interactions->max('interaction_date');

        // Paginate purchases for display in the modal
        $purchases = $supplier->purchases()->with('purchaseItems.product')
                        ->orderByDesc('created_at')
                        ->paginate(10, ['*'], 'page', $request->input('page', 1));

        return response()->json([
            'supplier' => $supplier,
            'lifetimeValue' => $lifetimeValue,
            'totalPurchasesCount' => $totalPurchasesCount,
            'averageOrderValue' => $averageOrderValue,
            'favoriteCategory' => $favoriteCategory,
            'lastPurchaseDate' => $allPurchases->max('created_at'),
            'lastInteractionDate' => $lastInteractionDate,
            'mostPurchasedProduct' => $mostPurchasedProduct,
            'totalProductsPurchased' => $totalProductsPurchased,
            'purchases' => $purchases, // Pass paginated purchases data
        ]);
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
            ->with('purchaseItems.product')
            ->orderByDesc('date')
            ->get()
            ->flatMap(function ($purchase) {
                return $purchase->purchaseItems->map(function ($item) use ($purchase) {
                    return [
                        'order_date' => $purchase->date,
                        'invoice' => $purchase->reference_number,
                        'product_name' => $item->product ? $item->product->name : 'N/A',
                        'quantity' => $item->quantity,
                        'price_at_purchase' => $item->price ?? 0,
                        'supplier_latest_price' => $item->product ? ($item->product->cost ?? 0) : 0,
                        'line_total' => $item->total
                    ];
                });
            });

        return response()->json([
            'historical_purchases' => $historicalPurchases,
        ]);
    }
}