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
     * @responseField data object[] A list of sales items.
     * @responseField data[].id integer The ID of the sales item.
     * @responseField data[].sales_id integer The ID of the sales order.
     * @responseField data[].product_id integer The ID of the product.
     * @responseField data[].quantity integer The quantity of the product.
     * @responseField data[].discount number The discount applied to the item.
     * @responseField data[].discount_type string The type of discount.
     * @responseField data[].customer_price number The price charged to the customer.
     * @responseField data[].total number The total amount for the item.
     * @responseField data[].created_at string The date and time the item was created.
     * @responseField data[].updated_at string The date and time the item was last updated.
     * @responseField data[].sales object The sales order associated with the item.
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
     * @responseField id integer The ID of the sales item.
     * @responseField sales_id integer The ID of the sales order.
     * @responseField product_id integer The ID of the product.
     * @responseField quantity integer The quantity of the product.
     * @responseField discount number The discount applied to the item.
     * @responseField discount_type string The type of discount.
     * @responseField customer_price number The price charged to the customer.
     * @responseField total number The total amount for the item.
     * @responseField created_at string The date and time the item was created.
     * @responseField updated_at string The date and time the item was last updated.
     * @responseField sales object The sales order associated with the item.
     * @responseField product object The product associated with the item.
     */
    public function show(SalesItem $sales_item)
    {
        return new SalesItemResource($sales_item->load(['sales', 'product']));
    }
}
