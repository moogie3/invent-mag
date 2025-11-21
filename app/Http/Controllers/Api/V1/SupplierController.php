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
     *
     * @bodyParam code string required The unique code for the supplier. Example: SUP-001
     * @bodyParam name string required The name of the supplier. Example: Supplier A
     * @bodyParam address string The address of the supplier. Example: 123 Main St
     * @bodyParam phone_number string The phone number of the supplier. Example: 555-1234
     * @bodyParam location string The location of the supplier. Example: London
     * @bodyParam payment_terms string The payment terms with the supplier. Example: Net 30
     * @bodyParam email string The email address of the supplier. Example: supplierA@example.com
     * @bodyParam image string The URL or path to the supplier's image. Example: http://example.com/image1.jpg
     *
     * @response 201 {
     *     "data": {
     *         "id": 1,
     *         "code": "SUP-001",
     *         "name": "Supplier A",
     *         "address": "123 Main St",
     *         "phone_number": "555-1234",
     *         "location": "London",
     *         "payment_terms": "Net 30",
     *         "email": "supplierA@example.com",
     *         "image": "http://example.com/image1.jpg",
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-26T12:00:00.000000Z"
     *     }
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:suppliers,code',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone_number' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'payment_terms' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:suppliers,email',
            'image' => 'nullable|string',
        ]);

        $supplier = Supplier::create($validated);

        return new SupplierResource($supplier);
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
     *
     * @urlParam supplier integer required The ID of the supplier. Example: 1
     * @bodyParam code string required The unique code for the supplier. Example: SUP-002
     * @bodyParam name string required The name of the supplier. Example: Supplier B
     * @bodyParam address string The address of the supplier. Example: 456 Oak Ave
     * @bodyParam phone_number string The phone number of the supplier. Example: 555-5678
     * @bodyParam location string The location of the supplier. Example: New York
     * @bodyParam payment_terms string The payment terms with the supplier. Example: Net 60
     * @bodyParam email string The email address of the supplier. Example: supplierb@example.com
     * @bodyParam image string The URL or path to the supplier's image. Example: http://example.com/image2.jpg
     *
     * @response 200 {
     *     "data": {
     *         "id": 1,
     *         "code": "SUP-002",
     *         "name": "Supplier B",
     *         "address": "456 Oak Ave",
     *         "phone_number": "555-5678",
     *         "location": "New York",
     *         "payment_terms": "Net 60",
     *         "email": "supplierb@example.com",
     *         "image": "http://example.com/image2.jpg",
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-27T12:00:00.000000Z"
     *     }
     * }
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:suppliers,code,' . $supplier->id,
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone_number' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'payment_terms' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:suppliers,email,' . $supplier->id,
            'image' => 'nullable|string',
        ]);

        $supplier->update($validated);

        return new SupplierResource($supplier);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @urlParam supplier integer required The ID of the supplier to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return response()->noContent();
    }
}
