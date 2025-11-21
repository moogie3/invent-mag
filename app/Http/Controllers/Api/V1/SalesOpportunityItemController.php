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
     * @queryParam per_page int The number of items to return per page. Defaults to 15. Example: 25
     *
     * @apiResourceCollection App\Http\Resources\SalesOpportunityItemResource
     * @apiResourceModel App\Models\SalesOpportunityItem
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $items = SalesOpportunityItem::with(['opportunity', 'product'])->paginate($perPage);
        return SalesOpportunityItemResource::collection($items);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @bodyParam sales_opportunity_id integer required The ID of the sales opportunity. Example: 1
     * @bodyParam product_id integer required The ID of the product. Example: 1
     * @bodyParam quantity integer required The quantity of the product. Example: 5
     * @bodyParam price numeric required The price of the product. Example: 100.00
     *
     * @response 201 {
     *     "data": {
     *         "id": 1,
     *         "sales_opportunity_id": 1,
     *         "product_id": 1,
     *         "quantity": 5,
     *         "price": 100.00,
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-26T12:00:00.000000Z"
     *     }
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_opportunity_id' => 'required|exists:sales_opportunities,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer',
            'price' => 'required|numeric',
        ]);

        $sales_opportunity_item = SalesOpportunityItem::create($validated);

        return new SalesOpportunityItemResource($sales_opportunity_item);
    }

    /**
     * Display the specified sales opportunity item.
     *
     * @urlParam sales_opportunity_item required The ID of the sales opportunity item. Example: 1
     *
     * @apiResource App\Http\Resources\SalesOpportunityItemResource
     * @apiResourceModel App\Models\SalesOpportunityItem
     */
    public function show(SalesOpportunityItem $sales_opportunity_item)
    {
        return new SalesOpportunityItemResource($sales_opportunity_item->load(['opportunity', 'product']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @urlParam sales_opportunity_item integer required The ID of the sales opportunity item. Example: 1
     * @bodyParam sales_opportunity_id integer required The ID of the sales opportunity. Example: 1
     * @bodyParam product_id integer required The ID of the product. Example: 1
     * @bodyParam quantity integer required The quantity of the product. Example: 10
     * @bodyParam price numeric required The price of the product. Example: 100.00
     *
     * @response 200 {
     *     "data": {
     *         "id": 1,
     *         "sales_opportunity_id": 1,
     *         "product_id": 1,
     *         "quantity": 10,
     *         "price": 100.00,
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-27T12:00:00.000000Z"
     *     }
     * }
     */
    public function update(Request $request, SalesOpportunityItem $sales_opportunity_item)
    {
        $validated = $request->validate([
            'sales_opportunity_id' => 'required|exists:sales_opportunities,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer',
            'price' => 'required|numeric',
        ]);

        $sales_opportunity_item->update($validated);

        return new SalesOpportunityItemResource($sales_opportunity_item);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @urlParam sales_opportunity_item integer required The ID of the sales opportunity item to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(SalesOpportunityItem $sales_opportunity_item)
    {
        $sales_opportunity_item->delete();

        return response()->noContent();
    }
}
