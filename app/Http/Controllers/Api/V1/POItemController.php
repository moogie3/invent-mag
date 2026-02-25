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
    public function __construct()
    {
        $this->middleware('permission:view-po-items')->only(['index', 'show']);
        $this->middleware('permission:create-po-items')->only(['store']);
        $this->middleware('permission:edit-po-items')->only(['update']);
        $this->middleware('permission:delete-po-items')->only(['destroy']);
    }

    /**
     * Display a listing of the purchase order items.
     *
     * @group Purchase Order Items
     * @authenticated
     * @queryParam per_page int The number of items to return per page. Defaults to 15. Example: 25
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"po_id":1,"product_id":1,"quantity":10,...}],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $items = POItem::with(['purchaseOrder', 'product'])->paginate($perPage);
        return POItemResource::collection($items);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @group Purchase Order Items
     * @authenticated
     * @bodyParam po_id integer required The ID of the purchase order. Example: 1
     * @bodyParam product_id integer required The ID of the product. Example: 1
     * @bodyParam quantity integer required The quantity of the product. Example: 10
     * @bodyParam price number required The price of the product. Example: 100.00
     *
     * @response 201 scenario="Success" {"data":{"id":1,"po_id":1,"product_id":1,"quantity":10,...}}
     * @response 422 scenario="Validation Error" {"message":"The po_id field is required.","errors":{"po_id":["The po_id field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function store(\App\Http\Requests\Api\V1\StorePOItemRequest $request)
    {
        try {
            $validated = $request->validated();
            $poItem = POItem::create($validated);
            return new POItemResource($poItem);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error($e->getMessage());
            dd($e->getMessage());
            throw $e;
        }
    }

    /**
     * Display the specified purchase order item.
     *
     * @group Purchase Order Items
     * @authenticated
     * @urlParam po_item required The ID of the purchase order item. Example: 1
     *
     * @response 200 scenario="Success" {"data":{"id":1,"po_id":1,"product_id":1,"quantity":10,...}}
     * @response 404 scenario="Not Found" {"message": "Purchase order item not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function show(POItem $poItem)
    {
        return new POItemResource($poItem->load(['purchaseOrder', 'product']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @group Purchase Order Items
     * @authenticated
     * @urlParam po_item required The ID of the purchase order item. Example: 1
     * @bodyParam quantity integer required The quantity of the product. Example: 10
     * @bodyParam price number required The price of the product. Example: 100.00
     *
     * @response 200 scenario="Success" {"data":{"id":1,"po_id":1,"product_id":1,"quantity":10,...}}
     * @response 404 scenario="Not Found" {"message": "Purchase order item not found."}
     * @response 422 scenario="Validation Error" {"message":"The quantity field is required.","errors":{"quantity":["The quantity field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function update(\App\Http\Requests\Api\V1\UpdatePOItemRequest $request, POItem $poItem)
    {
        $validated = $request->validated();
        $poItem->update($validated);
        return new POItemResource($poItem);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @group Purchase Order Items
     * @authenticated
     * @urlParam po_item required The ID of the purchase order item. Example: 1
     *
     * @response 204 scenario="Success"
     * @response 404 scenario="Not Found" {"message": "Purchase order item not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function destroy(POItem $poItem)
    {
        $poItem->delete();
        return response()->noContent();
    }
}
