<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerRequest;
use App\Http\Requests\Api\V1\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CrmService;
use App\Services\CustomerService;
use Illuminate\Http\Request;

/**
 * @group Customers
 *
 * APIs for managing customers
 */
class CustomerController extends Controller
{
    protected $customerService;
    protected $crmService;

    public function __construct(CustomerService $customerService, CrmService $crmService)
    {
        $this->customerService = $customerService;
        $this->crmService = $crmService;
    }

    /**
     * Display a listing of the customers.
     *
     * Retrieves a paginated list of customers. You can specify the number of customers per page.
     *
     * @queryParam per_page int The number of customers to return per page. Defaults to 15. Example: 25
     *
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $customers = Customer::paginate($perPage);
        return CustomerResource::collection($customers);
    }

    /**
     * Create a new customer.
     *
     * @bodyParam name string required The name of the customer. Example: "John Doe"
     * @bodyParam address string required The address of the customer. Example: "123 Main St"
     * @bodyParam phone_number string required The phone number of the customer. Example: "555-1234"
     * @bodyParam email string The email of the customer. Must be unique. Example: "john.doe@example.com"
     * @bodyParam payment_terms string required The payment terms. Example: "Net 30"
     *
     * @response 201 {
     *  "data": {
     *      "id": 1,
     *      "name": "John Doe",
     *      "address": "123 Main St",
     *      "phone_number": "555-1234",
     *      "email": "john.doe@example.com",
     *      "payment_terms": "Net 30"
     *  }
     * }
     */
    public function store(StoreCustomerRequest $request)
    {
        $customer = Customer::create($request->validated());
        return new CustomerResource($customer);
    }

    /**
     * Display the specified customer.
     *
     * Retrieves a single customer by its ID.
     *
     * @urlParam customer integer required The ID of the customer. Example: 1
     *
     */
    public function show(Customer $customer)
    {
        return new CustomerResource($customer);
    }

    /**
     * Update the specified customer.
     *
     * @urlParam customer integer required The ID of the customer to update. Example: 1
     * @bodyParam name string The name of the customer. Example: "Jane Doe"
     *
     * @response {
     *  "data": {
     *      "id": 1,
     *      "name": "Jane Doe",
     *      ...
     *  }
     * }
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());
        return new CustomerResource($customer);
    }

    /**
     * Delete the specified customer.
     *
     * @urlParam customer integer required The ID of the customer to delete. Example: 1
     *
     * @response 204 ""
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->noContent();
    }

    /**
     * @group Customers
     * @title Quick Create Customer
     * @bodyParam name string required The name of the customer. Example: "Quick Customer"
     * @bodyParam email string required The email of the customer. Example: "quick@example.com"
     * @bodyParam phone_number string required The phone number. Example: "555-555-5555"
     *
     * @response {
     *  "success": true,
     *  "message": "Customer created successfully",
     *  "customer": {}
     * }
     */
    public function quickCreate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone_number' => 'required|string|max:255',
        ]);

        try {
            $customer = $this->customerService->quickCreateCustomer($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully',
                'customer' => new CustomerResource($customer),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating customer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @group Customers
     * @title Get Customer Metrics
     *
     * @response {
     *  "total_customers": 100,
     *  "new_this_month": 5
     * }
     */
    public function getMetrics()
    {
        $metrics = $this->customerService->getMetrics();
        return response()->json($metrics);
    }

    /**
     * @group Customers
     * @title Get Customer Historical Purchases
     * @urlParam customer integer required The ID of the customer. Example: 1
     *
     * @response {
     *  "success": true,
     *  "historical_purchases": []
     * }
     */
    public function getHistoricalPurchases(Customer $customer)
    {
        try {
            $historicalPurchases = $this->crmService->getHistoricalPurchases($customer);
            return response()->json([
                'success' => true,
                'historical_purchases' => $historicalPurchases,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load historical purchases: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @group Customers
     * @title Get Customer Product History
     * @urlParam id integer required The ID of the customer. Example: 1
     *
     * @response {
     *  "data": []
     * }
     */
    public function getProductHistory(Request $request, $id)
    {
        try {
            $productHistory = $this->crmService->getCustomerProductHistory($id);
            return response()->json($productHistory);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load product history: ' . $e->getMessage()], 500);
        }
    }
}
