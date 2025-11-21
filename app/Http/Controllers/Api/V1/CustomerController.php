<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerRequest;
use App\Http\Requests\Api\V1\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;

/**
 * @group Customers
 *
 * APIs for managing customers
 */
class CustomerController extends Controller
{
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
}
