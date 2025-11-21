<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\POSResource;
use App\Models\POS;
use Illuminate\Http\Request;

/**
 * @group POS
 *
 * APIs for managing Point of Sale
 */
class POSController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @queryParam per_page int The number of items to return per page. Defaults to 15. Example: 25
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $pos = POS::paginate($perPage);
        return POSResource::collection($pos);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @bodyParam invoice string required The invoice number. Example: INV-2023-001
     * @bodyParam supplier_id integer required The ID of the supplier. Example: 1
     * @bodyParam order_date date required The date of the order. Example: 2023-10-26
     * @bodyParam due_date date The due date of the payment. Example: 2023-11-26
     * @bodyParam payment_type string required The payment type (e.g., Cash, Credit Card). Example: Cash
     * @bodyParam total numeric required The total amount of the purchase order. Example: 1500.00
     * @bodyParam status string required The status of the purchase order (e.g., Pending, Paid). Example: Pending
     * @bodyParam payment_date date The date of payment. Example: 2023-10-26
     *
     * @response 201 {
     *     "data": {
     *         "id": 1,
     *         "invoice": "INV-2023-001",
     *         "supplier_id": 1,
     *         "order_date": "2023-10-26",
     *         "due_date": "2023-11-26",
     *         "payment_type": "Cash",
     *         "total": 1500.00,
     *         "status": "Pending",
     *         "payment_date": "2023-10-26",
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
            'order_date' => 'required|date',
            'due_date' => 'nullable|date',
            'payment_type' => 'required|string|max:255',
            'total' => 'required|numeric',
            'status' => 'required|string|max:255',
            'payment_date' => 'nullable|date',
        ]);

        $po = POS::create($validated);

        return new POSResource($po);
    }

    /**
     * Display the specified resource.
     *
     * @urlParam po required The ID of the resource.
     */
    public function show(POS $po)
    {
        return new POSResource($po);
    }

    /**
     * Update the specified resource in storage.
     *
     * @urlParam po integer required The ID of the purchase order. Example: 1
     * @bodyParam invoice string The invoice number. Example: INV-2023-002
     * @bodyParam supplier_id integer The ID of the supplier. Example: 2
     * @bodyParam order_date date The date of the order. Example: 2023-10-27
     * @bodyParam due_date date The due date of the payment. Example: 2023-11-27
     * @bodyParam payment_type string The payment type (e.g., Cash, Credit Card). Example: Credit Card
     * @bodyParam total numeric The total amount of the purchase order. Example: 2000.00
     * @bodyParam status string The status of the purchase order (e.g., Pending, Paid). Example: Paid
     * @bodyParam payment_date date The date of payment. Example: 2023-10-27
     *
     * @response 200 {
     *     "data": {
     *         "id": 1,
     *         "invoice": "INV-2023-002",
     *         "supplier_id": 2,
     *         "order_date": "2023-10-27",
     *         "due_date": "2023-11-27",
     *         "payment_type": "Credit Card",
     *         "total": 2000.00,
     *         "status": "Paid",
     *         "payment_date": "2023-10-27",
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-27T12:00:00.000000Z"
     *     }
     * }
     */
    public function update(Request $request, POS $po)
    {
        $validated = $request->validate([
            'invoice' => 'required|string|max:255',
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'due_date' => 'nullable|date',
            'payment_type' => 'required|string|max:255',
            'total' => 'required|numeric',
            'status' => 'required|string|max:255',
            'payment_date' => 'nullable|date',
        ]);

        $po->update($validated);

        return new POSResource($po);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @urlParam po integer required The ID of the purchase order to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(POS $po)
    {
        $po->delete();

        return response()->noContent();
    }
}
