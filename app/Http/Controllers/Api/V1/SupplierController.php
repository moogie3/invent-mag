<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSupplierRequest;
use App\Http\Requests\Api\V1\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\CrmService;
use App\Services\SupplierService;
use Illuminate\Http\Request;

/**
 * @group Suppliers
 *
 * APIs for managing suppliers
 */
class SupplierController extends Controller
{
    protected $supplierService;
    protected $crmService;

    public function __construct(SupplierService $supplierService, CrmService $crmService)
    {
        $this->supplierService = $supplierService;
        $this->crmService = $crmService;
    }

    /**
     * Display a listing of the suppliers.
     *
     * Retrieves a paginated list of suppliers.
     *
     * @group Suppliers
     * @authenticated
     * @queryParam per_page int The number of suppliers to return per page. Defaults to 15. Example: 25
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"code":"SUP-001","name":"Supplier A",...}],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $data = $this->supplierService->getSupplierIndexData($perPage);
        return SupplierResource::collection($data['suppliers']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @group Suppliers
     * @authenticated
     * @bodyParam code string required The unique code for the supplier. Example: SUP-001
     * @bodyParam name string required The name of the supplier. Example: Supplier A
     * @bodyParam address string The address of the supplier. Example: 123 Main St
     * @bodyParam phone_number string The phone number of the supplier. Example: 555-1234
     * @bodyParam location string The location of the supplier. Example: IN
     * @bodyParam payment_terms string The payment terms with the supplier. Example: Net 30
     * @bodyParam email string The email address of the supplier. Example: supplierA@example.com
     * @bodyParam image string The URL or path to the supplier's image. Example: http://example.com/image1.jpg
     *
     * @response 201 scenario="Success" {"data":{"id":1,"code":"SUP-001","name":"Supplier A",...}}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function store(StoreSupplierRequest $request)
    {
        $result = $this->supplierService->createSupplier($request->validated());

        return new SupplierResource($result['supplier']);
    }

    /**
     * Display the specified supplier.
     *
     * Retrieves a single supplier by its ID.
     *
     * @group Suppliers
     * @authenticated
     * @urlParam supplier required The ID of the supplier. Example: 1
     *
     * @response 200 scenario="Success" {"data":{"id":1,"code":"SUP-001","name":"Supplier A",...}}
     * @response 404 scenario="Not Found" {"message": "Supplier not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function show(Supplier $supplier)
    {
        return new SupplierResource($supplier);
    }

    /**
     * Update the specified resource in storage.
     *
     * @group Suppliers
     * @authenticated
     * @urlParam supplier integer required The ID of the supplier. Example: 1
     * @bodyParam code string required The unique code for the supplier. Example: SUP-002
     * @bodyParam name string required The name of the supplier. Example: Supplier B
     * @bodyParam address string The address of the supplier. Example: 456 Oak Ave
     * @bodyParam phone_number string The phone number of the supplier. Example: 555-5678
     * @bodyParam location string The location of the supplier. Example: OUT
     * @bodyParam payment_terms string The payment terms with the supplier. Example: Net 60
     * @bodyParam email string The email address of the supplier. Example: supplierb@example.com
     * @bodyParam image string The URL or path to the supplier's image. Example: http://example.com/image2.jpg
     *
     * @response 200 scenario="Success" {"data":{"id":1,"code":"SUP-002","name":"Supplier B",...}}
     * @response 404 scenario="Not Found" {"message": "Supplier not found."}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $result = $this->supplierService->updateSupplier($supplier, $request->validated());

        return new SupplierResource($result['supplier']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @group Suppliers
     * @authenticated
     * @urlParam supplier integer required The ID of the supplier to delete. Example: 1
     *
     * @response 204 scenario="Success"
     * @response 404 scenario="Not Found" {"message": "Supplier not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function destroy(Supplier $supplier)
    {
        $this->supplierService->deleteSupplier($supplier);

        return response()->noContent();
    }

    /**
     * Get Supplier Metrics
     *
     * @group Suppliers
     * @authenticated
     *
     * @response 200 scenario="Success" {"total_suppliers":10,"new_this_month":2}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function getMetrics()
    {
        $metrics = $this->supplierService->getSupplierMetrics();
        return response()->json($metrics);
    }

    /**
     * Get Supplier Historical Purchases
     *
     * @group Suppliers
     * @authenticated
     * @urlParam id integer required The ID of the supplier. Example: 1
     *
     * @response 200 scenario="Success" {"historical_purchases":[{"id":1,"invoice":"PO-001","order_date":"2023-01-01",...}]}
     * @response 404 scenario="Not Found" {"message": "Supplier not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     * @response 500 scenario="Server Error" {"message": "Failed to load historical purchases: Internal server error."}
     */
    public function getHistoricalPurchases(Request $request, $id)
    {
        $historicalPurchases = $this->crmService->getSupplierHistoricalPurchases($id);

        return response()->json([
            'historical_purchases' => $historicalPurchases,
        ]);
    }

    /**
     * Get Supplier Product History
     *
     * @group Suppliers
     * @authenticated
     * @urlParam id integer required The ID of the supplier. Example: 1
     *
     * @response 200 scenario="Success" {"product_history":[{"product_name":"Product A","last_supplied_date":"2023-01-01",...}]}
     * @response 404 scenario="Not Found" {"message": "Supplier not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     * @response 500 scenario="Server Error" {"message": "Failed to load product history: Internal server error."}
     */
    public function getProductHistory(Request $request, $id)
    {
        $productHistory = $this->crmService->getSupplierProductHistory($id);

        return response()->json([
            'product_history' => $productHistory,
        ]);
    }
}