<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\Request;

/**
 * @group Suppliers
 *
 * APIs for managing suppliers
 */
class SupplierController extends Controller
{
    /**
     * Display a listing of the suppliers.
     *
     * Retrieves a paginated list of suppliers.
     *
     * @queryParam per_page int The number of suppliers to return per page. Defaults to 15. Example: 25
     *
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $suppliers = Supplier::paginate($perPage);
        return SupplierResource::collection($suppliers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified supplier.
     *
     * Retrieves a single supplier by its ID.
     *
     * @urlParam supplier required The ID of the supplier. Example: 1
     *
     */
    public function show(Supplier $supplier)
    {
        return new SupplierResource($supplier);
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
