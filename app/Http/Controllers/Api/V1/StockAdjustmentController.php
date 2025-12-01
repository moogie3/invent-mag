<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockAdjustmentResource;
use App\Models\StockAdjustment;
use Illuminate\Http\Request;

/**
 * @group Stock Adjustments
 *
 * APIs for managing stock adjustments
 */
class StockAdjustmentController extends Controller
{
    /**
     * Display a listing of the stock adjustments.
     *
     * @group Stock Adjustments
     * @authenticated
     * @queryParam per_page int The number of adjustments to return per page. Defaults to 15. Example: 25
     *
     * @responseField data object[] A list of stock adjustments.
     * @responseField data[].id integer The ID of the stock adjustment.
     * @responseField data[].product_id integer The ID of the product.
     * @responseField data[].adjustment_type string The type of adjustment (increase, decrease).
     * @responseField data[].quantity_before number The quantity before adjustment.
     * @responseField data[].quantity_after number The quantity after adjustment.
     * @responseField data[].adjustment_amount number The amount of adjustment.
     * @responseField data[].reason string The reason for the adjustment.
     * @responseField data[].adjusted_by integer The ID of the user who made the adjustment.
     * @responseField data[].created_at string The date and time the adjustment was created.
     * @responseField data[].updated_at string The date and time the adjustment was last updated.
     * @responseField data[].product object The product associated with the adjustment.
     * @responseField data[].user object The user who made the adjustment.
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
        $adjustments = StockAdjustment::with(['product', 'adjustedBy'])->paginate($perPage);
        return StockAdjustmentResource::collection($adjustments);
    }

    /**
     * Display the specified stock adjustment.
     *
     * @group Stock Adjustments
     * @authenticated
     * @urlParam stock_adjustment required The ID of the stock adjustment. Example: 1
     *
     * @responseField id integer The ID of the stock adjustment.
     * @responseField product_id integer The ID of the product.
     * @responseField adjustment_type string The type of adjustment (increase, decrease).
     * @responseField quantity_before number The quantity before adjustment.
     * @responseField quantity_after number The quantity after adjustment.
     * @responseField adjustment_amount number The amount of adjustment.
     * @responseField reason string The reason for the adjustment.
     * @responseField adjusted_by integer The ID of the user who made the adjustment.
     * @responseField created_at string The date and time the adjustment was created.
     * @responseField updated_at string The date and time the adjustment was last updated.
     * @responseField product object The product associated with the adjustment.
     * @responseField user object The user who made the adjustment.
     */
    public function show(StockAdjustment $stock_adjustment)
    {
        return new StockAdjustmentResource($stock_adjustment->load(['product', 'adjustedBy']));
    }
}
