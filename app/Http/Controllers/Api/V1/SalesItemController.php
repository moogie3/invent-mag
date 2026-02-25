<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesItemResource;
use App\Models\SalesItem;
use Illuminate\Http\Request;

/**
 * @group Sales Items
 *
 * APIs for managing sales items
 */
class SalesItemController extends Controller
{
    /**
     * Display a listing of the sales items.
     *
     * @group Sales Items
     * @authenticated
     * @queryParam per_page int The number of items to return per page. Defaults to 15. Example: 25
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"sales_id":1,"product_id":1,"quantity":1,...}],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $items = SalesItem::with(['sales', 'product'])->paginate($perPage);
        return SalesItemResource::collection($items);
    }

    /**
     * Display the specified sales item.
     *
     * @group Sales Items
     * @authenticated
     * @urlParam sales_item required The ID of the sales item. Example: 1
     *
     * @response 200 scenario="Success" {"data":{"id":1,"sales_id":1,"product_id":1,"quantity":1,...}}
     * @response 404 scenario="Not Found" {"message": "Sales item not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function show(SalesItem $sales_item)
    {
        return new SalesItemResource($sales_item->load(['sales', 'product']));
    }
}
