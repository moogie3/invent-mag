<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesResource;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sales;
use App\Services\SalesService;
use Illuminate\Http\Request;

/**
 * @group Sales Orders
 *
 * APIs for managing sales orders
 */
class SalesController extends Controller
{
    protected $salesService;

    public function __construct(SalesService $salesService)
    {
        $this->salesService = $salesService;
    }

    /**
     * Display a listing of the sales orders.
     *
     * Retrieves a paginated list of sales orders.
     *
     * @group Sales Orders
     * @authenticated
     * @queryParam per_page int The number of sales orders to return per page. Defaults to 15. Example: 25
     *
     * @responseField data object[] A list of sales orders.
     * @responseField data[].id integer The ID of the sales order.
     * @responseField data[].invoice string The invoice number.
     * @responseField data[].customer_id integer The ID of the customer.
     * @responseField data[].user_id integer The ID of the user.
     * @responseField data[].order_date string The order date.
     * @responseField data[].due_date string The due date.
     * @responseField data[].payment_type string The payment type.
     * @responseField data[].order_discount number The total order discount.
     * @responseField data[].order_discount_type string The type of discount.
     * @responseField data[].total number The total amount.
     * @responseField data[].status string The status of the sales order.
     * @responseField data[].tax_rate number The tax rate applied.
     * @responseField data[].total_tax number The total tax amount.
     * @responseField data[].amount_received number The amount received.
     * @responseField data[].change_amount number The change amount.
     * @responseField data[].is_pos boolean Whether it's a point of sale transaction.
     * @responseField data[].sales_opportunity_id integer The ID of the sales opportunity.
     * @responseField data[].created_at string The date and time the sales order was created.
     * @responseField data[].updated_at string The date and time the sales order was last updated.
     * @responseField data[].customer object The customer associated with the sales order.
     * @responseField data[].user object The user associated with the sales order.
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
        $data = $this->salesService->getSalesIndexData($filters, $perPage);
        return SalesResource::collection($data['sales']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @group Sales Orders
     * @authenticated
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
     * @responseField id integer The ID of the sales order.
     * @responseField invoice string The invoice number.
     * @responseField customer_id integer The ID of the customer.
     * @responseField user_id integer The ID of the user.
     * @responseField order_date string The order date.
     * @responseField due_date string The due date.
     * @responseField payment_type string The payment type.
     * @responseField order_discount number The total order discount.
     * @responseField order_discount_type string The type of discount.
     * @responseField total number The total amount.
     * @responseField status string The status of the sales order.
     * @responseField tax_rate number The tax rate applied.
     * @responseField total_tax number The total tax amount.
     * @responseField amount_received number The amount received.
     * @responseField change_amount number The change amount.
     * @responseField is_pos boolean Whether it's a point of sale transaction.
     * @responseField sales_opportunity_id integer The ID of the sales opportunity.
     * @responseField created_at string The date and time the sales order was created.
     * @responseField updated_at string The date and time the sales order was last updated.
     */
    public function store(\App\Http\Requests\Api\V1\StoreSaleRequest $request)
    {
        $sale = $this->salesService->createSale($request->validated());
        return new SalesResource($sale);
    }

    /**
     * Display the specified sales order.
     *
     * Retrieves a single sales order by its ID.
     *
     * @group Sales Orders
     * @authenticated
     * @urlParam sale required The ID of the sales order. Example: 1
     *
     * @responseField id integer The ID of the sales order.
     * @responseField invoice string The invoice number.
     * @responseField customer_id integer The ID of the customer.
     * @responseField user_id integer The ID of the user.
     * @responseField order_date string The order date.
     * @responseField due_date string The due date.
     * @responseField payment_type string The payment type.
     * @responseField order_discount number The total order discount.
     * @responseField order_discount_type string The type of discount.
     * @responseField total number The total amount.
     * @responseField status string The status of the sales order.
     * @responseField tax_rate number The tax rate applied.
     * @responseField total_tax number The total tax amount.
     * @responseField amount_received number The amount received.
     * @responseField change_amount number The change amount.
     * @responseField is_pos boolean Whether it's a point of sale transaction.
     * @responseField sales_opportunity_id integer The ID of the sales opportunity.
     * @responseField created_at string The date and time the sales order was created.
     * @responseField updated_at string The date and time the sales order was last updated.
     * @responseField customer object The customer associated with the sales order.
     * @responseField user object The user associated with the sales order.
     */
    public function show(Sales $sale)
    {
        return new SalesResource($sale->load(['customer', 'user']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @group Sales Orders
     * @authenticated
     * @urlParam sale integer required The ID of the sales order. Example: 1
     * @bodyParam invoice string The invoice number. Example: INV-SALES-002
     * @bodyParam customer_id integer The ID of the customer. Example: 2
     * @bodyParam user_id integer The ID of the user. Example: 1
     * @bodyParam order_date date The date of the order. Example: 2023-10-27
     * @bodyParam due_date date The due date of the payment. Example: 2023-11-27
     * @bodyParam payment_type string The payment type (e.g., Cash, Card, Transfer, eWallet). Example: Card
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
     * @responseField id integer The ID of the sales order.
     * @responseField invoice string The invoice number.
     * @responseField customer_id integer The ID of the customer.
     * @responseField user_id integer The ID of the user.
     * @responseField order_date string The order date.
     * @responseField due_date string The due date.
     * @responseField payment_type string The payment type.
     * @responseField order_discount number The total order discount.
     * @responseField order_discount_type string The type of discount.
     * @responseField total number The total amount.
     * @responseField status string The status of the sales order.
     * @responseField tax_rate number The tax rate applied.
     * @responseField total_tax number The total tax amount.
     * @responseField amount_received number The amount received.
     * @responseField change_amount number The change amount.
     * @responseField is_pos boolean Whether it's a point of sale transaction.
     * @responseField sales_opportunity_id integer The ID of the sales opportunity.
     * @responseField created_at string The date and time the sales order was created.
     * @responseField updated_at string The date and time the sales order was last updated.
     */
    public function update(\App\Http\Requests\Api\V1\UpdateSaleRequest $request, Sales $sale)
    {
        $sale = $this->salesService->updateSale($sale, $request->validated());
        return new SalesResource($sale);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @group Sales Orders
     * @authenticated
     * @urlParam sale integer required The ID of the sales order to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(Sales $sale)
    {
        $this->salesService->deleteSale($sale);
        return response()->noContent();
    }

    /**
     * Get Expiring Soon Sales
     *
     * @group Sales Orders
     * @authenticated
     *
     * @responseField id integer The ID of the sales order.
     * @responseField invoice string The invoice number.
     * @responseField customer object The customer details.
     * @responseField order_date string The order date.
     * @responseField due_date string The due date.
     * @responseField total number The total amount.
     * @responseField status string The status of the sales order.
     * @responseField remaining_days integer The number of days until expiry.
     * @response 200 scenario="Success" [{"id":1,"invoice":"INV-SALES-001","customer":{"id":1,"name":"Customer 1"},"order_date":"2025-11-20","due_date":"2025-12-20","total":1000,"status":"pending","remaining_days":19}]
     */
    public function getExpiringSoonSales()
    {
        $expiringSales = $this->salesService->getExpiringSales();
        return response()->json($expiringSales);
    }

    /**
     * Add Payment to Sales Order
     *
     * @group Sales Orders
     * @authenticated
     * @urlParam id integer required The ID of the sales order. Example: 1
     * @bodyParam amount number required The payment amount. Example: 100.00
     * @bodyParam payment_date date required The date of the payment. Example: 2023-10-27
     * @bodyParam payment_method string required The method of payment. Example: Bank Transfer
     * @bodyParam notes string nullable Any notes about the payment.
     *
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField message string A message describing the result of the request.
     */
    public function addPayment(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $sale = Sales::findOrFail($id);
        $this->salesService->addPayment($sale, $request->all());
        return response()->json(['success' => true, 'message' => 'Payment added successfully.']);
    }

    /**
     * Get Past Customer Price for a Product
     *
     * @group Sales Orders
     * @authenticated
     * @urlParam customer integer required The ID of the customer. Example: 1
     * @urlParam product integer required The ID of the product. Example: 1
     *
     * @responseField past_price number The past price of the product for the customer.
     */
    public function getCustomerPrice(Customer $customer, Product $product)
    {
        $pastPrice = $this->salesService->getPastCustomerPriceForProduct($customer, $product);
        return response()->json(['past_price' => $pastPrice]);
    }

    /**
     * Get Sales Metrics
     *
     * @group Sales Orders
     * @authenticated
     *
     * @responseField total_sales number Total sales amount.
     * @responseField total_paid number Total amount paid for sales.
     * @responseField total_due number Total amount due for sales.
     */
    public function getSalesMetrics()
    {
        $metrics = $this->salesService->getSalesMetrics();
        return response()->json($metrics);
    }

    /**
     * Bulk Delete Sales Orders
     *
     * @group Sales Orders
     * @authenticated
     * @bodyParam ids array required An array of sales order IDs to delete. Example: [1, 2, 3]
     * @bodyParam ids.* integer required A sales order ID.
     *
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField message string A message describing the result of the request.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:sales,id',
        ]);

        $this->salesService->bulkDeleteSales($request->ids);
        return response()->json([
            'success' => true,
            'message' => "Successfully deleted sales order(s)",
        ]);
    }

    /**
     * Bulk Mark Sales Orders as Paid
     *
     * @group Sales Orders
     * @authenticated
     * @bodyParam ids array required An array of sales order IDs to mark as paid. Example: [1, 2, 3]
     * @bodyParam ids.* integer required A sales order ID.
     *
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField message string A message describing the result of the request.
     * @responseField updated_count integer The number of sales orders successfully marked as paid.
     */
    public function bulkMarkPaid(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:sales,id',
        ]);

        $updatedCount = $this->salesService->bulkMarkPaid($request->ids);
        return response()->json([
            'success' => true,
            'message' => "Successfully marked {$updatedCount} sales order(s) as paid.",
            'updated_count' => $updatedCount,
        ]);
    }
}