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
     * @group Customers
     * @authenticated
     * @queryParam per_page int The number of customers to return per page. Defaults to 15. Example: 25
     *
     * @responseField data object[] A list of customers.
     * @responseField data[].id integer The ID of the customer.
     * @responseField data[].name string The name of the customer.
     * @responseField data[].address string The address of the customer.
     * @responseField data[].phone_number string The phone number of the customer.
     * @responseField data[].email string The email of the customer.
     * @responseField data[].payment_terms string The payment terms.
     * @responseField data[].created_at string The date and time the customer was created.
     * @responseField data[].updated_at string The date and time the customer was last updated.
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
     * @responseField id integer The ID of the customer.
     * @responseField name string The name of the customer.
     * @responseField address string The address of the customer.
     * @responseField phone_number string The phone number of the customer.
     * @responseField email string The email of the customer.
     * @responseField payment_terms string The payment terms.
     * @responseField created_at string The date and time the customer was created.
     * @responseField updated_at string The date and time the customer was last updated.
     * @response 422 scenario="Creation Failed" {"success": false, "message": "Failed to create customer."}
     */
    public function store(StoreCustomerRequest $request)
    {
        $result = $this->customerService->createCustomer($request->validated());

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }

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
     * @responseField id integer The ID of the customer.
     * @responseField name string The name of the customer.
     * @responseField address string The address of the customer.
     * @responseField phone_number string The phone number of the customer.
     * @responseField email string The email of the customer.
     * @responseField payment_terms string The payment terms.
     * @responseField created_at string The date and time the customer was created.
     * @responseField updated_at string The date and time the customer was last updated.
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
     * @responseField id integer The ID of the customer.
     * @responseField name string The name of the customer.
     * @responseField address string The address of the customer.
     * @responseField phone_number string The phone number of the customer.
     * @responseField email string The email of the customer.
     * @responseField payment_terms string The payment terms.
     * @responseField created_at string The date and time the customer was created.
     * @responseField updated_at string The date and time the customer was last updated.
     * @response 422 scenario="Update Failed" {"success": false, "message": "Failed to update customer."}
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $result = $this->customerService->updateCustomer($customer, $request->validated());

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }

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
     * @response 500 scenario="Deletion Failed" {"success": false, "message": "Failed to delete customer."}
     */
    public function destroy(Customer $customer)
    {
        $result = $this->customerService->deleteCustomer($customer);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 500);
        }

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
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField message string A message describing the result of the request.
     * @responseField customer object The created customer.
     * @responseField customer.id integer The ID of the customer.
     * @responseField customer.name string The name of the customer.
     * @responseField customer.email string The email of the customer.
     * @responseField customer.phone_number string The phone number of the customer.
     * @response 422 scenario="Creation Failed" {"success": false, "message": "Failed to create customer."}
     */
    public function quickCreate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone_number' => 'required|string|max:255',
            'address' => 'required|string',
            'payment_terms' => 'required|string',
        ]);

        $result = $this->customerService->quickCreateCustomer($request->all());

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'customer' => new CustomerResource($result['customer']),
        ]);
    }

    /**
     * Get Customer Metrics
     *
     * @group Customers
     * @authenticated
     *
     * @responseField total_customers integer The total number of customers.
     * @responseField new_this_month integer The number of new customers this month.
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
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField historical_purchases array A list of historical purchases.
     * @response 500 scenario="Failed to load" {"message": "Failed to load historical purchases: <error message>"}
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
     * @responseField data array A list of product history.
     * @response 500 scenario="Failed to load" {"message": "Failed to load product history: <error message>"}
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
