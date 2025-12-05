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
    public function show(POItem $po_item)
    {
        return new POItemResource($po_item->load(['purchaseOrder', 'product']));
    }




}
