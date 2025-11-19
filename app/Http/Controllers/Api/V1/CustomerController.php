<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified customer.
     *
     * Retrieves a single customer by its ID.
     *
     * @urlParam id required The ID of the customer. Example: 1
     *
     */
    public function show(Customer $customer)
    {
        return new CustomerResource($customer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
