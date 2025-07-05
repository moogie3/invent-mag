<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerCrmController extends Controller
{
    public function show(Request $request, $id)
    {
        $customer = Customer::with(['interactions.user'])->findOrFail($id);

        // Calculate lifetime value and favorite category from ALL sales for accuracy
        $allSales = $customer->sales()->with('items.product.category')->get();
        $lifetimeValue = $allSales->sum('grand_total');

        $categoryCounts = [];
        foreach ($allSales as $sale) {
            foreach ($sale->items as $item) {
                if ($item->product && $item->product->category) {
                    $categoryName = $item->product->category->name;
                    if (!isset($categoryCounts[$categoryName])) {
                        $categoryCounts[$categoryName] = 0;
                    }
                    $categoryCounts[$categoryName]++;
                }
            }
        }
        arsort($categoryCounts);
        $favoriteCategory = !empty($categoryCounts) ? key($categoryCounts) : 'N/A';

        // Paginate sales for display in the modal
        $sales = $customer->sales()->with('items.product')
                        ->orderByDesc('created_at')
                        ->paginate(10, ['*'], 'page', $request->input('page', 1));

        return response()->json([
            'customer' => $customer,
            'lifetimeValue' => $lifetimeValue,
            'favoriteCategory' => $favoriteCategory,
            'lastPurchaseDate' => $allSales->max('created_at'),
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
            'user_id' => auth()->id(),
            'type' => $request->type,
            'notes' => $request->notes,
            'interaction_date' => $request->interaction_date,
        ]);
        $interaction->save();

        return response()->json($interaction->load('user'));
    }
}
