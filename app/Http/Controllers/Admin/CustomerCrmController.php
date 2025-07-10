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

    public function getHistoricalPurchases(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $historicalPurchases = $customer->sales()
            ->with('salesItems.product')
            ->orderByDesc('order_date')
            ->get()
            ->flatMap(function ($sale) {
                return $sale->salesItems->map(function ($item) use ($sale) {
                    return [
                        'order_date' => $sale->order_date,
                        'invoice' => $sale->invoice,
                        'product_name' => $item->product ? $item->product->name : 'N/A',
                        'quantity' => $item->quantity,
                        'price_at_purchase' => $item->customer_price ?? 0, // Using customer_price from sales_items
                        'customer_latest_price' => $item->product ? ($item->product->price ?? 0) : 0, // Assuming 'price' on product is latest price
                        'line_total' => $item->price * $item->quantity
                    ];
                });
            });

        return response()->json([
            'historical_purchases' => $historicalPurchases,
        ]);
    }
}