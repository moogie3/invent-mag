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
     * @response 200 scenario="Success" {"data":[{"id":1,"product_id":1,"adjustment_type":"increase",...}],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
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
     * @response 200 scenario="Success" {"data":{"id":1,"product_id":1,"adjustment_type":"increase",...}}
     * @response 404 scenario="Not Found" {"message": "Stock adjustment not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function show(StockAdjustment $stock_adjustment)
    {
        return new StockAdjustmentResource($stock_adjustment->load(['product', 'adjustedBy']));
    }
}
