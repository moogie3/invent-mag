<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
     * @responseField data object[] A list of suppliers.
     * @responseField data[].id integer The ID of the supplier.
     * @responseField data[].code string The unique code for the supplier.
     * @responseField data[].name string The name of the supplier.
     * @responseField data[].address string The address of the supplier.
     * @responseField data[].phone_number string The phone number of the supplier.
     * @responseField data[].location string The location of the supplier.
     * @responseField data[].payment_terms string The payment terms with the supplier.
     * @responseField data[].email string The email address of the supplier.
     * @responseField data[].image string The URL or path to the supplier's image.
     * @responseField data[].created_at string The date and time the supplier was created.
     * @responseField data[].updated_at string The date and time the supplier was last updated.
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
     * @responseField id integer The ID of the supplier.
     * @responseField code string The unique code for the supplier.
     * @responseField name string The name of the supplier.
     * @responseField address string The address of the supplier.
     * @responseField phone_number string The phone number of the supplier.
     * @responseField location string The location of the supplier.
     * @responseField payment_terms string The payment terms with the supplier.
     * @responseField email string The email address of the supplier.
     * @responseField image string The URL or path to the supplier's image.
     * @responseField created_at string The date and time the supplier was created.
     * @responseField updated_at string The date and time the supplier was last updated.
     * @response 422 scenario="Creation Failed" {"success": false, "message": "Failed to create supplier."}
     */
    public function store(\App\Http\Requests\Api\V1\StoreSupplierRequest $request)
    {
        $result = $this->supplierService->createSupplier($request->validated());

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }

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
     * @responseField id integer The ID of the supplier.
     * @responseField code string The unique code for the supplier.
     * @responseField name string The name of the supplier.
     * @responseField address string The address of the supplier.
     * @responseField phone_number string The phone number of the supplier.
     * @responseField location string The location of the supplier.
     * @responseField payment_terms string The payment terms with the supplier.
     * @responseField email string The email address of the supplier.
     * @responseField image string The URL or path to the supplier's image.
     * @responseField created_at string The date and time the supplier was created.
     * @responseField updated_at string The date and time the supplier was last updated.
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
     * @responseField id integer The ID of the supplier.
     * @responseField code string The unique code for the supplier.
     * @responseField name string The name of the supplier.
     * @responseField address string The address of the supplier.
     * @responseField phone_number string The phone number of the supplier.
     * @responseField location string The location of the supplier.
     * @responseField payment_terms string The payment terms with the supplier.
     * @responseField email string The email address of the supplier.
     * @responseField image string The URL or path to the supplier's image.
     * @responseField created_at string The date and time the supplier was created.
     * @responseField updated_at string The date and time the supplier was last updated.
     * @response 422 scenario="Update Failed" {"success": false, "message": "Failed to update supplier."}
     */
    public function update(\App\Http\Requests\Api\V1\UpdateSupplierRequest $request, Supplier $supplier)
    {
        $result = $this->supplierService->updateSupplier($supplier, $request->validated());

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }

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
     * @response 500 scenario="Deletion Failed" {"success": false, "message": "Failed to delete supplier."}
     */
    public function destroy(Supplier $supplier)
    {
        $result = $this->supplierService->deleteSupplier($supplier);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 500);
        }

        return response()->noContent();
    }

    /**
     * Get Supplier Metrics
     *
     * @group Suppliers
     * @authenticated
     *
     * @responseField total_suppliers integer Total number of suppliers.
     * @responseField new_this_month integer Number of new suppliers this month.
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
     * @responseField historical_purchases array A list of historical purchases for the supplier.
     * @response 500 scenario="Error" {"message": "Failed to load historical purchases: <error message>"}
     */
    public function getHistoricalPurchases(Request $request, $id)
    {
        try {
            $historicalPurchases = $this->crmService->getSupplierHistoricalPurchases($id);

            return response()->json([
                'historical_purchases' => $historicalPurchases,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load historical purchases: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get Supplier Product History
     *
     * @group Suppliers
     * @authenticated
     * @urlParam id integer required The ID of the supplier. Example: 1
     *
     * @responseField product_history array A list of products historically supplied by the supplier.
     * @response 500 scenario="Error" {"message": "Failed to load product history: <error message>"}
     */
    public function getProductHistory(Request $request, $id)
    {
        try {
            $productHistory = $this->crmService->getSupplierProductHistory($id);

            return response()->json([
                'product_history' => $productHistory,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load product history: ' . $e->getMessage()], 500);
        }
    }
}