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
     * @queryParam per_page int The number of purchase orders to return per page. Defaults to 15. Example: 25
     *
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $purchases = Purchase::with(['supplier', 'user', 'items'])->paginate($perPage);
        return PurchaseResource::collection($purchases);
    }

    /**
     * Store a newly created resource in storage.
     *
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
     * @response 201 {
     *     "data": {
     *         "id": 1,
     *         "invoice": "INV-2023-001",
     *         "supplier_id": 1,
     *         "user_id": 1,
     *         "order_date": "2023-10-26",
     *         "due_date": "2023-11-26",
     *         "payment_type": "Cash",
     *         "discount_total": 5.00,
     *         "discount_total_type": "fixed",
     *         "total": 1500.00,
     *         "status": "Pending",
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-26T12:00:00.000000Z"
     *     }
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice' => 'required|string|max:255',
            'supplier_id' => 'required|exists:suppliers,id',
            'user_id' => 'required|exists:users,id',
            'order_date' => 'required|date',
            'due_date' => 'nullable|date',
            'payment_type' => 'required|string|max:255',
            'discount_total' => 'nullable|numeric',
            'discount_total_type' => 'nullable|string|in:percentage,fixed',
            'total' => 'required|numeric',
            'status' => 'required|string|max:255',
        ]);

        $purchase = Purchase::create($validated);

        return new PurchaseResource($purchase);
    }

    /**
     * Display the specified purchase order.
     *
     * Retrieves a single purchase order by its ID.
     *
     * @urlParam purchase required The ID of the purchase order. Example: 1
     *
     */
    public function show(Purchase $purchase)
    {
        return new PurchaseResource($purchase->load(['supplier', 'user', 'items']));
    }

    /**
     * Update the specified resource in storage.
     *
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
     * @response 200 {
     *     "data": {
     *         "id": 1,
     *         "invoice": "INV-2023-002",
     *         "supplier_id": 2,
     *         "user_id": 1,
     *         "order_date": "2023-10-27",
     *         "due_date": "2023-11-27",
     *         "payment_type": "Credit Card",
     *         "discount_total": 10.00,
     *         "discount_total_type": "percentage",
     *         "total": 2000.00,
     *         "status": "Paid",
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-27T12:00:00.000000Z"
     *     }
     * }
     */
    public function update(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'invoice' => 'required|string|max:255',
            'supplier_id' => 'required|exists:suppliers,id',
            'user_id' => 'required|exists:users,id',
            'order_date' => 'required|date',
            'due_date' => 'nullable|date',
            'payment_type' => 'required|string|max:255',
            'discount_total' => 'nullable|numeric',
            'discount_total_type' => 'nullable|string|in:percentage,fixed',
            'total' => 'required|numeric',
            'status' => 'required|string|max:255',
        ]);

        $purchase->update($validated);

        return new PurchaseResource($purchase);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @urlParam purchase integer required The ID of the purchase order to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(Purchase $purchase)
    {
        $purchase->delete();

        return response()->noContent();
    }

    /**
     * @group Purchase Orders
     * @title Get Expiring Soon Purchases
     * @response {
     *  "data": []
     * }
     */
    public function getExpiringSoonPurchases()
    {
        $expiringPurchases = $this->purchaseService->getExpiringPurchases();
        return response()->json($expiringPurchases);
    }

    /**
     * @group Purchase Orders
     * @title Add Payment to Purchase Order
     * @urlParam id integer required The ID of the purchase order. Example: 1
     * @bodyParam amount number required The payment amount. Example: 100.00
     * @bodyParam payment_date date required The date of the payment. Example: "2023-10-27"
     * @bodyParam payment_method string required The method of payment. Example: "Bank Transfer"
     * @bodyParam notes string nullable Any notes about the payment.
     *
     * @response {
     *  "success": true,
     *  "message": "Payment added successfully."
     * }
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
     * @group Purchase Orders
     * @title Get Purchase Metrics
     * @response {
     *  "total_purchases": 50,
     *  "total_paid": 25000,
     *  "total_due": 5000
     * }
     */
    public function getPurchaseMetrics()
    {
        $metrics = $this->purchaseService->getPurchaseMetrics();
        return response()->json($metrics);
    }

    /**
     * @group Purchase Orders
     * @title Bulk Delete Purchase Orders
     * @bodyParam ids array required An array of purchase order IDs to delete. Example: [1, 2, 3]
     * @bodyParam ids.* integer required A purchase order ID.
     *
     * @response {
     *  "success": true,
     *  "message": "Successfully deleted purchase order(s)"
     * }
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
     * @group Purchase Orders
     * @title Bulk Mark Purchase Orders as Paid
     * @bodyParam ids array required An array of purchase order IDs to mark as paid. Example: [1, 2, 3]
     * @bodyParam ids.* integer required A purchase order ID.
     *
     * @response {
     *  "success": true,
     *  "message": "Successfully marked 3 purchase order(s) as paid.",
     *  "updated_count": 3
     * }
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
