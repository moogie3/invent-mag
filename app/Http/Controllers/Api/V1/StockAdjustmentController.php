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
     * @queryParam per_page int The number of adjustments to return per page. Defaults to 15. Example: 25
     *
     * @apiResourceCollection App\Http\Resources\StockAdjustmentResource
     * @apiResourceModel App\Models\StockAdjustment
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $adjustments = StockAdjustment::with(['product', 'user'])->paginate($perPage);
        return StockAdjustmentResource::collection($adjustments);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @bodyParam product_id integer required The ID of the product. Example: 1
     * @bodyParam adjustment_type string required The type of adjustment (e.g., increase, decrease). Example: decrease
     * @bodyParam quantity_before numeric required The quantity before adjustment. Example: 100.00
     * @bodyParam quantity_after numeric required The quantity after adjustment. Example: 90.00
     * @bodyParam adjustment_amount numeric required The amount of adjustment. Example: 10.00
     * @bodyParam reason string A reason for the adjustment. Example: Damaged goods.
     * @bodyParam adjusted_by integer The ID of the user who made the adjustment. Example: 1
     *
     * @response 201 {
     *     "data": {
     *         "id": 1,
     *         "product_id": 1,
     *         "adjustment_type": "decrease",
     *         "quantity_before": 100.00,
     *         "quantity_after": 90.00,
     *         "adjustment_amount": 10.00,
     *         "reason": "Damaged goods.",
     *         "adjusted_by": 1,
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-26T12:00:00.000000Z"
     *     }
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'adjustment_type' => 'required|string|in:increase,decrease',
            'quantity_before' => 'required|numeric',
            'quantity_after' => 'required|numeric',
            'adjustment_amount' => 'required|numeric',
            'reason' => 'nullable|string',
            'adjusted_by' => 'required|exists:users,id',
        ]);

        $stock_adjustment = StockAdjustment::create($validated);

        return new StockAdjustmentResource($stock_adjustment);
    }

    /**
     * Display the specified stock adjustment.
     *
     * @urlParam stock_adjustment required The ID of the stock adjustment. Example: 1
     *
     * @apiResource App\Http\Resources\StockAdjustmentResource
     * @apiResourceModel App\Models\StockAdjustment
     */
    public function show(StockAdjustment $stock_adjustment)
    {
        return new StockAdjustmentResource($stock_adjustment->load(['product', 'user']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @urlParam stock_adjustment integer required The ID of the stock adjustment. Example: 1
     * @bodyParam product_id integer required The ID of the product. Example: 1
     * @bodyParam adjustment_type string required The type of adjustment (e.g., increase, decrease). Example: increase
     * @bodyParam quantity_before numeric required The quantity before adjustment. Example: 100.00
     * @bodyParam quantity_after numeric required The quantity after adjustment. Example: 110.00
     * @bodyParam adjustment_amount numeric required The amount of adjustment. Example: 10.00
     * @bodyParam reason string A reason for the adjustment. Example: Inventory count discrepancy.
     * @bodyParam adjusted_by integer The ID of the user who made the adjustment. Example: 1
     *
     * @response 200 {
     *     "data": {
     *         "id": 1,
     *         "product_id": 1,
     *         "adjustment_type": "increase",
     *         "quantity_before": 100.00,
     *         "quantity_after": 110.00,
     *         "adjustment_amount": 10.00,
     *         "reason": "Inventory count discrepancy.",
     *         "adjusted_by": 1,
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-27T12:00:00.000000Z"
     *     }
     * }
     */
    public function update(Request $request, StockAdjustment $stock_adjustment)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'adjustment_type' => 'required|string|in:increase,decrease',
            'quantity_before' => 'required|numeric',
            'quantity_after' => 'required|numeric',
            'adjustment_amount' => 'required|numeric',
            'reason' => 'nullable|string',
            'adjusted_by' => 'required|exists:users,id',
        ]);

        $stock_adjustment->update($validated);

        return new StockAdjustmentResource($stock_adjustment);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @urlParam stock_adjustment integer required The ID of the stock adjustment to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(StockAdjustment $stock_adjustment)
    {
        $stock_adjustment->delete();

        return response()->noContent();
    }
}
