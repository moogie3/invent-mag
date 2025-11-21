<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\POItemResource;
use App\Models\POItem;
use Illuminate\Http\Request;

/**
 * @group Purchase Order Items
 *
 * APIs for managing purchase order items
 */
class POItemController extends Controller
{
    /**
     * Display a listing of the purchase order items.
     *
     * @queryParam per_page int The number of items to return per page. Defaults to 15. Example: 25
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $items = POItem::with(['purchase', 'product'])->paginate($perPage);
        return POItemResource::collection($items);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @response 201 scenario="Success"
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'po_id' => 'required|exists:po,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric',
            'price' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'discount_type' => 'nullable|string',
            'total' => 'required|numeric',
            'expiry_date' => 'nullable|date',
            'remaining_quantity' => 'nullable|numeric',
        ]);

        $po_item = POItem::create($validated);

        return new POItemResource($po_item);
    }

    /**
     * Display the specified purchase order item.
     *
     * @urlParam po_item required The ID of the purchase order item. Example: 1
     */
    public function show(POItem $po_item)
    {
        return new POItemResource($po_item->load(['purchase', 'product']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @response 200 scenario="Success"
     */
    public function update(Request $request, POItem $po_item)
    {
        $validated = $request->validate([
            'po_id' => 'required|exists:po,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric',
            'price' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'discount_type' => 'nullable|string',
            'total' => 'required|numeric',
            'expiry_date' => 'nullable|date',
            'remaining_quantity' => 'nullable|numeric',
        ]);

        $po_item->update($validated);

        return new POItemResource($po_item);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @response 204 scenario="Success"
     */
    public function destroy(POItem $po_item)
    {
        $po_item->delete();

        return response()->noContent();
    }
}
