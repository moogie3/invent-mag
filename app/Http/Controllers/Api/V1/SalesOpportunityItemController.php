<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesOpportunityItemResource;
use App\Models\SalesOpportunityItem;
use Illuminate\Http\Request;

/**
 * @group Sales Opportunity Items
 *
 * APIs for managing sales opportunity items
 */
class SalesOpportunityItemController extends Controller
{
    /**
     * Display a listing of the sales opportunity items.
     *
     * @group Sales Opportunity Items
     * @authenticated
     * @queryParam per_page int The number of items to return per page. Defaults to 15. Example: 25
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"sales_opportunity_id":1,"product_id":1,"quantity":1,...}],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $items = SalesOpportunityItem::with(['salesOpportunity', 'product'])->paginate($perPage);
        return SalesOpportunityItemResource::collection($items);
    }

    /**
     * Display the specified sales opportunity item.
     *
     * @group Sales Opportunity Items
     * @authenticated
     * @urlParam sales_opportunity_item required The ID of the sales opportunity item. Example: 1
     *
     * @response 200 scenario="Success" {"data":{"id":1,"sales_opportunity_id":1,"product_id":1,"quantity":1,...}}
     * @response 404 scenario="Not Found" {"message": "Sales opportunity item not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function show(SalesOpportunityItem $sales_opportunity_item)
    {
        return new SalesOpportunityItemResource($sales_opportunity_item->load(['salesOpportunity', 'product']));
    }
}
