<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerRequest;
use App\Http\Requests\Api\V1\UpdateCustomerRequest;
use App\Http\Requests\Api\V1\QuickStoreCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CrmService;
use App\Services\CustomerService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
     * @group Customers
     * @authenticated
     * @queryParam per_page int The number of customers to return per page. Defaults to 15. Example: 25
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"name":"Walk In Customer","address":"-","phone_number":"0","email":"-","image":"http:\/\/localhost\/img\/default_placeholder.png","payment_terms":"0","created_at":"2025-12-02T05:06:57.000000Z","updated_at":"2025-12-02T05:06:57.000000Z"}],"links":{"first":"http:\/\/localhost\/api\/v1\/customers?page=1","last":null,"prev":null,"next":"http:\/\/localhost\/api\/v1\/customers?page=2"},"meta":{"current_page":1,"from":1,"last_page":1,"links":[...],"path":"http:\/\/localhost\/api\/v1\/customers","per_page":15,"to":15,"total":6}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $data = $this->customerService->getCustomerIndexData($perPage);
        return CustomerResource::collection($data['customers']);
    }

    /**
     * Create a new customer.
     *
     * @group Customers
     * @authenticated
     * @bodyParam name string required The name of the customer. Example: John Doe
     * @bodyParam address string required The address of the customer. Example: 123 Main St
     * @bodyParam phone_number string required The phone number of the customer. Example: 555-1234
     * @bodyParam email string The email of the customer. Must be unique. Example: john.doe@example.com
     * @bodyParam payment_terms string required The payment terms. Example: Net 30
     *
     * @response 201 scenario="Success" {"data":{"name":"John Doe","address":"123 Main St","phone_number":"555-1234","email":"john.doe@example.com","payment_terms":"Net 30","updated_at":"2025-12-02T06:45:31.000000Z","created_at":"2025-12-02T06:45:31.000000Z","id":11}}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function store(StoreCustomerRequest $request)
    {
        $result = $this->customerService->createCustomer($request->validated());

        return new CustomerResource($result['customer']);
    }

    /**
     * Display the specified customer.
     *
     * Retrieves a single customer by its ID.
     *
     * @group Customers
     * @authenticated
     * @urlParam customer integer required The ID of the customer. Example: 1
     *
     * @response 200 scenario="Success" {"data":{"id":1,"name":"Walk In Customer","address":"-","phone_number":"0","email":"-","image":"http:\/\/localhost\/img\/default_placeholder.png","payment_terms":"0","created_at":"2025-12-02T05:06:57.000000Z","updated_at":"2025-12-02T05:06:57.000000Z"}}
     * @response 404 scenario="Not Found" {"message": "Customer not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function show(Customer $customer)
    {
        return new CustomerResource($customer);
    }

    /**
     * Update the specified customer.
     *
     * @group Customers
     * @authenticated
     * @urlParam customer integer required The ID of the customer to update. Example: 1
     * @bodyParam name string The name of the customer. Example: Jane Doe
     * @bodyParam address string The address of the customer. Example: 456 Oak Ave
     * @bodyParam phone_number string The phone number of the customer. Example: 555-5678
     * @bodyParam email string The email of the customer. Must be unique. Example: jane.doe@example.com
     * @bodyParam payment_terms string The payment terms. Example: Net 60
     *
     * @response 200 scenario="Success" {"data":{"id":1,"name":"Jane Doe","address":"456 Oak Ave","phone_number":"555-5678","email":"jane.doe@example.com","image":"http:\/\/localhost\/img\/default_placeholder.png","payment_terms":"Net 60","created_at":"2025-12-02T05:06:57.000000Z","updated_at":"2025-12-02T08:00:00.000000Z"}}
     * @response 404 scenario="Not Found" {"message": "Customer not found."}
     * @response 422 scenario="Validation Error" {"message":"The email has already been taken.","errors":{"email":["The email has already been taken."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $result = $this->customerService->updateCustomer($customer, $request->validated());

        return new CustomerResource($result['customer']);
    }

    /**
     * Delete the specified customer.
     *
     * @group Customers
     * @authenticated
     * @urlParam customer integer required The ID of the customer to delete. Example: 1
     *
     * @response 204 scenario="Success"
     * @response 404 scenario="Not Found" {"message": "Customer not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function destroy(Customer $customer)
    {
        $this->customerService->deleteCustomer($customer);

        return response()->noContent();
    }

    /**
     * Quick Create Customer
     *
     * @group Customers
     * @authenticated
     * @bodyParam name string required The name of the customer. Example: "Quick Customer"
     * @bodyParam email string required The email of the customer. Example: "quick@example.com"
     * @bodyParam phone_number string required The phone number. Example: "555-555-5555"
     * @bodyParam address string required The address of the customer. Example: "123 Quick St"
     * @bodyParam payment_terms string required The payment terms. Example: "Net 30"
     *
     * @response 201 scenario="Success" {"data":{"id":12,"name":"Quick Customer","address":"123 Quick St","phone_number":"555-555-5555","email":"quick@example.com","payment_terms":"Net 30","created_at":"2025-12-02T09:00:00.000000Z","updated_at":"2025-12-02T09:00:00.000000Z"}}
     * @response 422 scenario="Validation Error" {"message":"The email field must be a valid email address.","errors":{"email":["The email field must be a valid email address."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function quickCreate(QuickStoreCustomerRequest $request)
    {
        $result = $this->customerService->quickCreateCustomer($request->validated());

        return new CustomerResource($result['customer']);
    }

    /**
     * Get Customer Metrics
     *
     * @group Customers
     * @authenticated
     *
     * @response 200 scenario="Success" {"total_customers":6,"new_this_month":2}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function getMetrics()
    {
        $metrics = $this->customerService->getCustomerMetrics();
        return response()->json($metrics);
    }

    /**
     * Get Customer Historical Purchases
     *
     * @group Customers
     * @authenticated
     * @urlParam customer integer required The ID of the customer. Example: 1
     *
     * @response 200 scenario="Success" {"success":true,"historical_purchases":[{"sale_id":54,"invoice":"INV-00054","order_date":"2025-11-22T05:06:58.000000Z","product_id":4,"product_name":"Low Stock LED","quantity":5,"price_at_purchase":"160.00","line_total":"800.00","customer_latest_price":"160.00"}]}
     * @response 404 scenario="Not Found" {"message": "Customer not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     * @response 500 scenario="Server Error" {"message": "Failed to load historical purchases: Internal server error."}
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
     * Get Customer Product History
     *
     * @group Customers
     * @authenticated
     * @urlParam id integer required The ID of the customer. Example: 1
     *
     * @response 200 scenario="Success" [{"product_name":"Expired Diode","last_price":"350.00","history":[{"invoice":"INV-00004","order_date":"2025-09-13T05:06:57.000000Z","quantity":2,"price_at_purchase":"350.00"}]}]
     * @response 404 scenario="Not Found" {"message": "Customer not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     * @response 500 scenario="Server Error" {"message": "Failed to load product history: Internal server error."}
     */
    public function getProductHistory(Request $request, $id)
    {
        try {
            $productHistory = $this->crmService->getCustomerProductHistory($id);
            return response()->json($productHistory);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Customer not found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load product history: ' . $e->getMessage()], 500);
        }
    }
}
