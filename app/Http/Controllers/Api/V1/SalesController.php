<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesResource;
use App\Models\Sales;
use Illuminate\Http\Request;

/**
 * @group Sales Orders
 *
 * APIs for managing sales orders
 */
class SalesController extends Controller
{
    /**
     * Display a listing of the sales orders.
     *
     * Retrieves a paginated list of sales orders.
     *
     * @queryParam per_page int The number of sales orders to return per page. Defaults to 15. Example: 25
     *
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $sales = Sales::with(['customer', 'user'])->paginate($perPage);
        return SalesResource::collection($sales);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified sales order.
     *
     * Retrieves a single sales order by its ID.
     *
     * @urlParam sale required The ID of the sales order. Example: 1
     *
     */
    public function show(Sales $sale)
    {
        return new SalesResource($sale->load(['customer', 'user']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
