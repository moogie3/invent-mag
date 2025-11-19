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
     */
    public function store(Request $request)
    {
        //
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
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
