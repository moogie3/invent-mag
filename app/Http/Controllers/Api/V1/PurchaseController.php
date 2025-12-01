<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
     * @responseField data object[] A list of purchase orders.
     * @responseField data[].id integer The ID of the purchase order.
     * @responseField data[].invoice string The invoice number.
     * @responseField data[].supplier_id integer The ID of the supplier.
     * @responseField data[].user_id integer The ID of the user.
     * @responseField data[].order_date string The order date.
     * @responseField data[].due_date string The due date.
     * @responseField data[].payment_type string The payment type.
     * @responseField data[].discount_total number The total discount.
     * @responseField data[].discount_total_type string The type of discount.
     * @responseField data[].total number The total amount.
     * @responseField data[].status string The status of the purchase order.
     * @responseField data[].created_at string The date and time the purchase order was created.
     * @responseField data[].updated_at string The date and time the purchase order was last updated.
     * @responseField data[].supplier object The supplier associated with the purchase order.
     * @responseField data[].user object The user associated with the purchase order.
     * @responseField data[].items object[] The items in the purchase order.
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
     * @bodyParam invoice string required The invoice number. Example: INV-2023-001
     * @bodyParam supplier_id integer required The ID of the supplier. Example: 1
     * @bodyParam user_id integer required The ID of the user. Example: 1
     * @bodyParam order_date date required The date of the order. Example: 2023-10-26
     * @bodyParam due_date date The due date of the payment. Example: 2023-11-26
     * @bodyParam payment_type string required The payment type (e.g., Cash, Credit Card). Example: Cash
     * @bodyParam discount_total numeric The total discount applied. Example: 5.00
     * @bodyParam discount_total_type string The type of discount (e.g., percentage, fixed). Example: fixed
     * @bodyParam total numeric required The total amount of the purchase order. Example: 1500.00
     * @bodyParam status string required The status of the purchase order (e.g., Pending, Paid). Example: Pending
     *
     * @responseField id integer The ID of the purchase order.
     * @responseField invoice string The invoice number.
     * @responseField supplier_id integer The ID of the supplier.
     * @responseField user_id integer The ID of the user.
     * @responseField order_date string The order date.
     * @responseField due_date string The due date.
     * @responseField payment_type string The payment type.
     * @responseField discount_total number The total discount.
     * @responseField discount_total_type string The type of discount.
     * @responseField total number The total amount.
     * @responseField status string The status of the purchase order.
     * @responseField created_at string The date and time the purchase order was created.
     * @responseField updated_at string The date and time the purchase order was last updated.
     * @response 500 scenario="Creation Failed" {"success": false, "message": "Failed to create purchase order."}
     */
    public function store(\App\Http\Requests\Api\V1\StorePurchaseRequest $request)
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
     * @responseField id integer The ID of the purchase order.
     * @responseField invoice string The invoice number.
     * @responseField supplier_id integer The ID of the supplier.
     * @responseField user_id integer The ID of the user.
     * @responseField order_date string The order date.
     * @responseField due_date string The due date.
     * @responseField payment_type string The payment type.
     * @responseField discount_total number The total discount.
     * @responseField discount_total_type string The type of discount.
     * @responseField total number The total amount.
     * @responseField status string The status of the purchase order.
     * @responseField created_at string The date and time the purchase order was created.
     * @responseField updated_at string The date and time the purchase order was last updated.
     * @responseField supplier object The supplier associated with the purchase order.
     * @responseField user object The user associated with the purchase order.
     * @responseField items object[] The items in the purchase order.
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
     * @bodyParam payment_type string The payment type (e.g., Cash, Credit Card). Example: Credit Card
     * @bodyParam discount_total numeric The total discount applied. Example: 10.00
     * @bodyParam discount_total_type string The type of discount (e.g., percentage, fixed). Example: percentage
     * @bodyParam total numeric The total amount of the purchase order. Example: 2000.00
     * @bodyParam status string The status of the purchase order (e.g., Pending, Paid). Example: Paid
     *
     * @responseField id integer The ID of the purchase order.
     * @responseField invoice string The invoice number.
     * @responseField supplier_id integer The ID of the supplier.
     * @responseField user_id integer The ID of the user.
     * @responseField order_date string The order date.
     * @responseField due_date string The due date.
     * @responseField payment_type string The payment type.
     * @responseField discount_total number The total discount.
     * @responseField discount_total_type string The type of discount.
     * @responseField total number The total amount.
     * @responseField status string The status of the purchase order.
     * @responseField created_at string The date and time the purchase order was created.
     * @responseField updated_at string The date and time the purchase order was last updated.
     * @response 500 scenario="Update Failed" {"success": false, "message": "Failed to update purchase order."}
     */
    public function update(\App\Http\Requests\Api\V1\UpdatePurchaseRequest $request, Purchase $purchase)
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
     * @response 500 scenario="Deletion Failed" {"success": false, "message": "Failed to delete purchase order."}
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
     * @responseField data array A list of expiring purchase orders.
     * @responseField data[].id integer The ID of the purchase order.
     * @responseField data[].invoice string The invoice number.
     * @responseField data[].supplier object The supplier details.
     * @responseField data[].order_date string The order date.
     * @responseField data[].due_date string The due date.
     * @responseField data[].total number The total amount.
     * @responseField data[].status string The status of the purchase order.
     * @responseField data[].remaining_days integer The number of days until expiry.
     * @response 200 scenario="Success" [{"id":1,"invoice":"PO-001","supplier":{"id":1,"name":"Supplier 1"},"order_date":"2025-11-20","due_date":"2025-12-20","total":1000,"status":"pending","remaining_days":19}]
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
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField message string A message describing the result of the request.
     * @response 500 scenario="Payment Failed" {"success": false, "message": "Something went wrong: <error message>"}
     */
    public function addPayment(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $purchase = Purchase::findOrFail($id);
            $this->purchaseService->addPayment($purchase, $request->all());
            return response()->json(['success' => true, 'message' => 'Payment added successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get Purchase Metrics
     *
     * @group Purchase Orders
     * @authenticated
     *
     * @responseField total_purchases integer Total number of purchase orders.
     * @responseField total_paid number Total amount paid for purchase orders.
     * @responseField total_due number Total amount due for purchase orders.
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
     * @bodyParam ids array required An array of purchase order IDs to delete. Example: [1, 2, 3]
     * @bodyParam ids.* integer required A purchase order ID.
     *
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField message string A message describing the result of the request.
     * @response 500 scenario="Deletion Failed" {"success": false, "message": "Error deleting purchase orders. Please try again."}
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:po,id',
        ]);

        try {
            $this->purchaseService->bulkDeletePurchases($request->ids);
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted purchase order(s)",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting purchase orders. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Bulk Mark Purchase Orders as Paid
     *
     * @group Purchase Orders
     * @authenticated
     * @bodyParam ids array required An array of purchase order IDs to mark as paid. Example: [1, 2, 3]
     * @bodyParam ids.* integer required A purchase order ID.
     *
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField message string A message describing the result of the request.
     * @responseField updated_count integer The number of purchase orders successfully marked as paid.
     * @response 500 scenario="Update Failed" {"success": false, "message": "An error occurred while updating purchase orders."}
     */
    public function bulkMarkPaid(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:po,id',
        ]);

        try {
            $updatedCount = $this->purchaseService->bulkMarkPaid($request->ids);
            return response()->json([
                'success' => true,
                'message' => "Successfully marked {$updatedCount} purchase order(s) as paid.",
                'updated_count' => $updatedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating purchase orders.',
            ], 500);
        }
    }
}