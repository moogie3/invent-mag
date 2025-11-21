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
     *
     * @bodyParam invoice string required The invoice number. Example: INV-SALES-001
     * @bodyParam customer_id integer required The ID of the customer. Example: 1
     * @bodyParam user_id integer required The ID of the user. Example: 1
     * @bodyParam order_date date required The date of the order. Example: 2023-10-26
     * @bodyParam due_date date The due date of the payment. Example: 2023-11-26
     * @bodyParam payment_type string required The payment type (e.g., Cash, Credit Card). Example: Cash
     * @bodyParam order_discount numeric The total discount applied. Example: 5.00
     * @bodyParam order_discount_type string The type of discount (e.g., percentage, fixed). Example: fixed
     * @bodyParam total numeric required The total amount of the sales order. Example: 1500.00
     * @bodyParam status string required The status of the sales order (e.g., Pending, Paid). Example: Pending
     * @bodyParam tax_rate numeric The tax rate applied. Example: 0.05
     * @bodyParam total_tax numeric The total tax amount. Example: 75.00
     * @bodyParam amount_received numeric The amount received. Example: 1575.00
     * @bodyParam change_amount numeric The change amount. Example: 0.00
     * @bodyParam is_pos boolean Is this a point of sale transaction. Example: true
     * @bodyParam sales_opportunity_id integer The ID of the sales opportunity. Example: 1
     *
     * @response 201 {
     *     "data": {
     *         "id": 1,
     *         "invoice": "INV-SALES-001",
     *         "customer_id": 1,
     *         "user_id": 1,
     *         "order_date": "2023-10-26",
     *         "due_date": "2023-11-26",
     *         "payment_type": "Cash",
     *         "order_discount": 5.00,
     *         "order_discount_type": "fixed",
     *         "total": 1500.00,
     *         "status": "Pending",
     *         "tax_rate": 0.05,
     *         "total_tax": 75.00,
     *         "amount_received": 1575.00,
     *         "change_amount": 0.00,
     *         "is_pos": true,
     *         "sales_opportunity_id": 1,
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-26T12:00:00.000000Z"
     *     }
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice' => 'required|string|max:255',
            'customer_id' => 'required|exists:customers,id',
            'user_id' => 'required|exists:users,id',
            'order_date' => 'required|date',
            'due_date' => 'nullable|date',
            'payment_type' => 'required|string|max:255',
            'order_discount' => 'nullable|numeric',
            'order_discount_type' => 'nullable|string|in:percentage,fixed',
            'total' => 'required|numeric',
            'status' => 'required|string|max:255',
            'tax_rate' => 'nullable|numeric',
            'total_tax' => 'nullable|numeric',
            'amount_received' => 'nullable|numeric',
            'change_amount' => 'nullable|numeric',
            'is_pos' => 'boolean',
            'sales_opportunity_id' => 'nullable|exists:sales_opportunities,id',
        ]);

        $sale = Sales::create($validated);

        return new SalesResource($sale);
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
     *
     * @urlParam sale integer required The ID of the sales order. Example: 1
     * @bodyParam invoice string The invoice number. Example: INV-SALES-002
     * @bodyParam customer_id integer The ID of the customer. Example: 2
     * @bodyParam user_id integer The ID of the user. Example: 1
     * @bodyParam order_date date The date of the order. Example: 2023-10-27
     * @bodyParam due_date date The due date of the payment. Example: 2023-11-27
     * @bodyParam payment_type string The payment type (e.g., Cash, Credit Card). Example: Credit Card
     * @bodyParam order_discount numeric The total discount applied. Example: 10.00
     * @bodyParam order_discount_type string The type of discount (e.g., percentage, fixed). Example: percentage
     * @bodyParam total numeric The total amount of the sales order. Example: 2000.00
     * @bodyParam status string The status of the sales order (e.g., Pending, Paid). Example: Paid
     * @bodyParam tax_rate numeric The tax rate applied. Example: 0.05
     * @bodyParam total_tax numeric The total tax amount. Example: 100.00
     * @bodyParam amount_received numeric The amount received. Example: 2000.00
     * @bodyParam change_amount numeric The change amount. Example: 0.00
     * @bodyParam is_pos boolean Is this a point of sale transaction. Example: false
     * @bodyParam sales_opportunity_id integer The ID of the sales opportunity. Example: 1
     *
     * @response 200 {
     *     "data": {
     *         "id": 1,
     *         "invoice": "INV-SALES-002",
     *         "customer_id": 2,
     *         "user_id": 1,
     *         "order_date": "2023-10-27",
     *         "due_date": "2023-11-27",
     *         "payment_type": "Credit Card",
     *         "order_discount": 10.00,
     *         "order_discount_type": "percentage",
     *         "total": 2000.00,
     *         "status": "Paid",
     *         "tax_rate": 0.05,
     *         "total_tax": 100.00,
     *         "amount_received": 2000.00,
     *         "change_amount": 0.00,
     *         "is_pos": false,
     *         "sales_opportunity_id": 1,
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-27T12:00:00.000000Z"
     *     }
     * }
     */
    public function update(Request $request, Sales $sale)
    {
        $validated = $request->validate([
            'invoice' => 'required|string|max:255',
            'customer_id' => 'required|exists:customers,id',
            'user_id' => 'required|exists:users,id',
            'order_date' => 'required|date',
            'due_date' => 'nullable|date',
            'payment_type' => 'required|string|max:255',
            'order_discount' => 'nullable|numeric',
            'order_discount_type' => 'nullable|string|in:percentage,fixed',
            'total' => 'required|numeric',
            'status' => 'required|string|max:255',
            'tax_rate' => 'nullable|numeric',
            'total_tax' => 'nullable|numeric',
            'amount_received' => 'nullable|numeric',
            'change_amount' => 'nullable|numeric',
            'is_pos' => 'boolean',
            'sales_opportunity_id' => 'nullable|exists:sales_opportunities,id',
        ]);

        $sale->update($validated);

        return new SalesResource($sale);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @urlParam sale integer required The ID of the sales order to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(Sales $sale)
    {
        $sale->delete();

        return response()->noContent();
    }
}
