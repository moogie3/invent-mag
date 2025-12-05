<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePurchaseRequest;
use App\Http\Requests\Api\V1\UpdatePurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

/**
 * @group Purchase Orders
 *
 * APIs for managing purchase orders
 */
class PurchaseController extends Controller
{
    protected $purchaseService;

    public function __construct(PurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    /**
     * Display a listing of the purchase orders.
     *
     * Retrieves a paginated list of purchase orders.
     *
     * @group Purchase Orders
     * @authenticated
     * @queryParam per_page int The number of purchase orders to return per page. Defaults to 15. Example: 25
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"invoice":"PO-001","supplier":{"id":1,"name":"Supplier 1"},...}],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $filters = $request->only(['month', 'year']);
        $data = $this->purchaseService->getPurchaseIndexData($filters, $perPage);
        return PurchaseResource::collection($data['pos']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @group Purchase Orders
     * @authenticated
     * @bodyParam invoice string required The invoice number. Example: "INV-2023-001"
     * @bodyParam supplier_id integer required The ID of the supplier. Example: 1
     * @bodyParam user_id integer required The ID of the user. Example: 1
     * @bodyParam order_date date required The date of the order. Example: "2023-10-26"
     * @bodyParam due_date date The due date of the payment. Example: 2023-11-26
     * @bodyParam payment_type string required The payment type (e.g., Cash, Credit Card). Example: "Cash"
     * @bodyParam discount_total numeric The total discount applied. Example: 5.00
     * @bodyParam discount_total_type string The type of discount (e.g., percentage, fixed). Example: "fixed"
     * @bodyParam total numeric required The total amount of the purchase order. Example: 1500.00
     * @bodyParam status string required The status of the purchase order (e.g., Pending, Paid). Example: "Pending"
     *
     * @response 201 scenario="Success" {"data":{"id":1,"invoice":"INV-2023-001",...}}
     * @response 422 scenario="Validation Error" {"message":"The invoice field is required.","errors":{"invoice":["The invoice field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function store(StorePurchaseRequest $request)
    {
        $purchase = $this->purchaseService->createPurchase($request->validated());
        return new PurchaseResource($purchase);
    }

    /**
     * Display the specified purchase order.
     *
     * Retrieves a single purchase order by its ID.
     *
     * @group Purchase Orders
     * @authenticated
     * @urlParam purchase required The ID of the purchase order. Example: 4
     *
     * @response 200 scenario="Success" {"data":{"id":4,"invoice":"PO-004",...}}
     * @response 404 scenario="Not Found" {"message": "Purchase order not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function show(Purchase $purchase)
    {
        return new PurchaseResource($purchase->load(['supplier', 'user', 'items']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @group Purchase Orders
     * @authenticated
     * @urlParam purchase integer required The ID of the purchase order. Example: 1
     * @bodyParam invoice string The invoice number. Example: INV-2023-002
     * @bodyParam supplier_id integer The ID of the supplier. Example: 2
     * @bodyParam user_id integer The ID of the user. Example: 1
     * @bodyParam order_date date The date of the order. Example: 2023-10-27
     * @bodyParam due_date date The due date of the payment. Example: 2023-11-27
     * @bodyParam payment_type string The payment type (e.g., Cash, Card, Transfer, eWallet). Example: Card
     * @bodyParam discount_total numeric The total discount applied. Example: 10.00
     * @bodyParam discount_total_type string The type of discount (e.g., percentage, fixed). Example: percentage
     * @bodyParam total numeric The total amount of the purchase order. Example: 2000.00
     * @bodyParam status string The status of the purchase order (e.g., Pending, Paid). Example: Paid
     *
     * @response 200 scenario="Success" {"data":{"id":1,"invoice":"INV-2023-002",...}}
     * @response 404 scenario="Not Found" {"message": "Purchase order not found."}
     * @response 422 scenario="Validation Error" {"message":"The status field is required.","errors":{"status":["The status field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function update(UpdatePurchaseRequest $request, Purchase $purchase)
    {
        $purchase = $this->purchaseService->updatePurchase($purchase, $request->validated());
        return new PurchaseResource($purchase);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @group Purchase Orders
     * @authenticated
     * @urlParam purchase integer required The ID of the purchase order to delete. Example: 1
     *
     * @response 204 scenario="Success"
     * @response 404 scenario="Not Found" {"message": "Purchase order not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function destroy(Purchase $purchase)
    {
        $this->purchaseService->deletePurchase($purchase);
        return response()->noContent();
    }

    /**
     * Get Expiring Soon Purchases
     *
     * @group Purchase Orders
     * @authenticated
     *
     * @response 200 scenario="Success" [{"id":1,"invoice":"PO-001","supplier":{"id":1,"name":"Supplier 1"},"order_date":"2025-11-20","due_date":"2025-12-20","total":1000,"status":"pending","remaining_days":19}]
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function getExpiringSoonPurchases()
    {
        $expiringPurchases = $this->purchaseService->getExpiringPurchases();
        return response()->json($expiringPurchases);
    }

    /**
     * Add Payment to Purchase Order
     *
     * @group Purchase Orders
     * @authenticated
     * @urlParam id integer required The ID of the purchase order. Example: 1
     * @bodyParam amount number required The payment amount. Example: 100
     * @bodyParam payment_date date required The date of the payment. Example: 2023-10-27
     * @bodyParam payment_method string required The method of payment. Example: Bank Transfer
     * @bodyParam notes string nullable Any notes about the payment.
     *
     * @response 200 scenario="Success" {"success":true,"message":"Payment added successfully."}
     * @response 404 scenario="Not Found" {"message": "Purchase order not found."}
     * @response 422 scenario="Validation Error" {"message":"The amount must be a number.","errors":{"amount":["The amount must be a number."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function addPayment(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $purchase = Purchase::findOrFail($id);
        $this->purchaseService->addPayment($purchase, $request->all());
        return response()->json(['success' => true, 'message' => 'Payment added successfully.']);
    }

    /**
     * Get Purchase Metrics
     *
     * @group Purchase Orders
     * @authenticated
     *
     * @response 200 scenario="Success" {"total_purchases":10,"total_paid":5000,"total_due":2500}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function getPurchaseMetrics()
    {
        $metrics = $this->purchaseService->getPurchaseMetrics();
        return response()->json($metrics);
    }

    /**
     * Bulk Delete Purchase Orders
     *
     * @group Purchase Orders
     * @authenticated
     * @bodyParam ids array required An array of purchase order IDs to delete. Example: "[1, 2, 3]"
     * @bodyParam ids.* integer required A purchase order ID.
     *
     * @response 200 scenario="Success" {"success":true,"message":"Successfully deleted purchase order(s)"}
     * @response 422 scenario="Validation Error" {"message":"The ids field is required.","errors":{"ids":["The ids field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:po,id',
        ]);

        $this->purchaseService->bulkDeletePurchases($request->ids);
        return response()->json([
            'success' => true,
            'message' => "Successfully deleted purchase order(s)",
        ]);
    }

    /**
     * Bulk Mark Purchase Orders as Paid
     *
     * @group Purchase Orders
     * @authenticated
     * @bodyParam ids array required An array of purchase order IDs to mark as paid. Example: "[1, 2, 3]"
     * @bodyParam ids.* integer required A purchase order ID.
     *
     * @response 200 scenario="Success" {"success":true,"message":"Successfully marked 3 purchase order(s) as paid.","updated_count":3}
     * @response 422 scenario="Validation Error" {"message":"The ids field is required.","errors":{"ids":["The ids field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function bulkMarkPaid(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:po,id',
        ]);

        $updatedCount = $this->purchaseService->bulkMarkPaid($request->ids);
        return response()->json([
            'success' => true,
            'message' => "Successfully marked {$updatedCount} purchase order(s) as paid.",
            'updated_count' => $updatedCount,
        ]);
    }
}
