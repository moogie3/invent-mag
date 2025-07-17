<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerCrmController extends Controller
{
    public function show(Request $request, $id)
    {
        $customer = Customer::with(['interactions.user'])->findOrFail($id);

        // Calculate lifetime value and favorite category from ALL sales for accuracy
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

        $lastInteractionDate = $customer->interactions->max('interaction_date');

        // Paginate sales for display in the modal
        $sales = $customer->sales()->with('salesItems.product')
                        ->orderByDesc('created_at')
                        ->paginate(10, ['*'], 'page', $request->input('page', 1));

        return response()->json([
            'customer' => $customer,
            'lifetimeValue' => $lifetimeValue,
            'totalSalesCount' => $totalSalesCount,
            'averageOrderValue' => $averageOrderValue,
            'favoriteCategory' => $favoriteCategory,
            'lastPurchaseDate' => $allSales->max('created_at'),
            'lastInteractionDate' => $lastInteractionDate,
            'mostPurchasedProduct' => $mostPurchasedProduct,
            'totalProductsPurchased' => $totalProductsPurchased,
            'sales' => $sales, // Pass paginated sales data
        ]);
    }

    public function storeInteraction(Request $request, $customerId)
    {
        $request->validate([
            'type' => 'required|string',
            'notes' => 'required|string',
            'interaction_date' => 'required|date',
        ]);

        $interaction = new \App\Models\CustomerInteraction([
            'customer_id' => $customerId,
            'user_id' => Auth::id(), // Changed from auth()->id() to Auth::id()
            'type' => $request->type,
            'notes' => $request->notes,
            'interaction_date' => $request->interaction_date,
        ]);
        $interaction->save();

        return response()->json($interaction->load('user'));
    }

    public function getProductHistory(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        // Fetch all sales items for the customer, eager loading the product and the sale
        $salesItems = \App\Models\SalesItem::whereIn('sales_id', $customer->sales()->pluck('id'))
            ->with(['product', 'sale'])
            ->get();

        // Group sales items by product
        $productHistory = $salesItems->groupBy('product.name')
            ->map(function ($items, $productName) {
                // Sort history for each product by date
                $history = $items->sortByDesc('sale.order_date')->map(function ($item) {
                    return [
                        'invoice' => $item->sale->invoice,
                        'order_date' => $item->sale->order_date,
                        'quantity' => $item->quantity,
                        'price_at_purchase' => $item->customer_price,
                    ];
                })->values(); // Reset keys to be a simple array

                // Get the last price from the most recent sale
                $lastPrice = $history->first()['price_at_purchase'];

                return [
                    'product_name' => $productName,
                    'last_price' => $lastPrice,
                    'history' => $history,
                ];
            })
            ->sortBy('product_name') // Sort products alphabetically
            ->values(); // Reset keys

        return response()->json([
            'product_history' => $productHistory,
        ]);
    }
}