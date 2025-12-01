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
     * @responseField data object[] A list of purchase order items.
     * @responseField data[].id integer The ID of the PO item.
     * @responseField data[].po_id integer The ID of the purchase order.
     * @responseField data[].product_id integer The ID of the product.
     * @responseField data[].quantity number The quantity of the product.
     * @responseField data[].remaining_quantity number The remaining quantity of the product.
     * @responseField data[].price number The price of the product.
     * @responseField data[].discount number The discount applied.
     * @responseField data[].discount_type string The type of discount applied.
     * @responseField data[].total number The total price for the item.
     * @responseField data[].expiry_date string The expiry date of the product.
     * @responseField data[].created_at string The date and time the item was created.
     * @responseField data[].updated_at string The date and time the item was last updated.
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
     * @responseField id integer The ID of the PO item.
     * @responseField po_id integer The ID of the purchase order.
     * @responseField product_id integer The ID of the product.
     * @responseField quantity number The quantity of the product.
     * @responseField remaining_quantity number The remaining quantity of the product.
     * @responseField price number The price of the product.
     * @responseField discount number The discount applied.
     * @responseField discount_type string The type of discount applied.
     * @responseField total number The total price for the item.
     * @responseField expiry_date string The expiry date of the product.
     * @responseField created_at string The date and time the item was created.
     * @responseField updated_at string The date and time the item was last updated.
     * @responseField purchaseOrder object The purchase order associated with the item.
     * @responseField product object The product associated with the item.
     */
    public function show(POItem $po_item)
    {
        return new POItemResource($po_item->load(['purchaseOrder', 'product']));
    }




}
