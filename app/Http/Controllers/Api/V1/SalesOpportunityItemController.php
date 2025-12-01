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
     * @responseField data object[] A list of sales opportunity items.
     * @responseField data[].id integer The ID of the sales opportunity item.
     * @responseField data[].sales_opportunity_id integer The ID of the sales opportunity.
     * @responseField data[].product_id integer The ID of the product.
     * @responseField data[].quantity integer The quantity of the product.
     * @responseField data[].price number The price of the product.
     * @responseField data[].created_at string The date and time the item was created.
     * @responseField data[].updated_at string The date and time the item was last updated.
     * @responseField data[].opportunity object The sales opportunity associated with the item.
     * @responseField data[].product object The product associated with the item.
     * @responseField links object Links for pagination.
     * @responseField links.first string The URL of the first page.
     * @responseField links.last string The URL of the last page.
     * @responseField links.prev string The URL of the previous page.
     * @responseField links.next string The URL of the next page.
     * @responseField meta object Metadata for pagination.
     * @responseField meta.current_page integer The current page number.
     * @responseField meta.from integer The starting number of the results on the current page.
     * @responseField meta.last_page integer The last page number.
     * @responseField meta.path string The URL path.
     * @responseField meta.per_page integer The number of results per page.
     * @responseField meta.to integer The ending number of the results on the current page.
     * @responseField meta.total integer The total number of results.
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
     * @responseField id integer The ID of the sales opportunity item.
     * @responseField sales_opportunity_id integer The ID of the sales opportunity.
     * @responseField product_id integer The ID of the product.
     * @responseField quantity integer The quantity of the product.
     * @responseField price number The price of the product.
     * @responseField created_at string The date and time the item was created.
     * @responseField updated_at string The date and time the item was last updated.
     * @responseField opportunity object The sales opportunity associated with the item.
     * @responseField product object The product associated with the item.
     */
    public function show(SalesOpportunityItem $sales_opportunity_item)
    {
        return new SalesOpportunityItemResource($sales_opportunity_item->load(['salesOpportunity', 'product']));
    }
}
