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
     * @queryParam per_page int The number of items to return per page. Defaults to 15. Example: 25
     *
     * @apiResourceCollection App\Http\Resources\SalesItemResource
     * @apiResourceModel App\Models\SalesItem
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $items = SalesItem::with(['sales', 'product'])->paginate($perPage);
        return SalesItemResource::collection($items);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @bodyParam sales_id integer required The ID of the sales order. Example: 1
     * @bodyParam product_id integer required The ID of the product. Example: 1
     * @bodyParam quantity integer required The quantity of the product. Example: 5
     * @bodyParam discount numeric The discount applied to the item. Example: 0.00
     * @bodyParam discount_type string The type of discount (e.g., percentage, fixed). Example: fixed
     * @bodyParam customer_price numeric The price charged to the customer. Example: 100.00
     * @bodyParam total numeric required The total amount for the item. Example: 500.00
     *
     * @response 201 {
     *     "data": {
     *         "id": 1,
     *         "sales_id": 1,
     *         "product_id": 1,
     *         "quantity": 5,
     *         "discount": 0.00,
     *         "discount_type": "fixed",
     *         "customer_price": 100.00,
     *         "total": 500.00,
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-26T12:00:00.000000Z"
     *     }
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_id' => 'required|exists:sales,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer',
            'discount' => 'nullable|numeric',
            'discount_type' => 'nullable|string|in:percentage,fixed',
            'customer_price' => 'required|numeric',
            'total' => 'required|numeric',
        ]);

        $sales_item = SalesItem::create($validated);

        return new SalesItemResource($sales_item);
    }

    /**
     * Display the specified sales item.
     *
     * @urlParam sales_item required The ID of the sales item. Example: 1
     *
     * @apiResource App\Http\Resources\SalesItemResource
     * @apiResourceModel App\Models\SalesItem
     */
    public function show(SalesItem $sales_item)
    {
        return new SalesItemResource($sales_item->load(['sales', 'product']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @urlParam sales_item integer required The ID of the sales item. Example: 1
     * @bodyParam sales_id integer required The ID of the sales order. Example: 1
     * @bodyParam product_id integer required The ID of the product. Example: 1
     * @bodyParam quantity integer required The quantity of the product. Example: 10
     * @bodyParam discount numeric The discount applied to the item. Example: 0.00
     * @bodyParam discount_type string The type of discount (e.g., percentage, fixed). Example: fixed
     * @bodyParam customer_price numeric The price charged to the customer. Example: 100.00
     * @bodyParam total numeric required The total amount for the item. Example: 1000.00
     *
     * @response 200 {
     *     "data": {
     *         "id": 1,
     *         "sales_id": 1,
     *         "product_id": 1,
     *         "quantity": 10,
     *         "discount": 0.00,
     *         "discount_type": "fixed",
     *         "customer_price": 100.00,
     *         "total": 1000.00,
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-27T12:00:00.000000Z"
     *     }
     * }
     */
    public function update(Request $request, SalesItem $sales_item)
    {
        $validated = $request->validate([
            'sales_id' => 'required|exists:sales,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer',
            'discount' => 'nullable|numeric',
            'discount_type' => 'nullable|string|in:percentage,fixed',
            'customer_price' => 'required|numeric',
            'total' => 'required|numeric',
        ]);

        $sales_item->update($validated);

        return new SalesItemResource($sales_item);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @urlParam sales_item integer required The ID of the sales item to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(SalesItem $sales_item)
    {
        $sales_item->delete();

        return response()->noContent();
    }
}
