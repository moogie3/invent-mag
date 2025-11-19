<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use Illuminate\Http\Request;

/**
 * @group Purchase Orders
 *
 * APIs for managing purchase orders
 */
class PurchaseController extends Controller
{
    /**
     * Display a listing of the purchase orders.
     *
     * Retrieves a paginated list of purchase orders.
     *
     * @queryParam per_page int The number of purchase orders to return per page. Defaults to 15. Example: 25
     *
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $purchases = Purchase::with(['supplier', 'user', 'items'])->paginate($perPage);
        return PurchaseResource::collection($purchases);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified purchase order.
     *
     * Retrieves a single purchase order by its ID.
     *
     * @urlParam purchase required The ID of the purchase order. Example: 1
     *
     */
    public function show(Purchase $purchase)
    {
        return new PurchaseResource($purchase->load(['supplier', 'user', 'items']));
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
