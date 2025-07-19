<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index(Request $request)
    {
        $entries = $request->input('entries', 10);
        $data = $this->customerService->getCustomerIndexData($entries);
        return view('admin.customer.index', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'payment_terms' => 'required',
            'email' => 'nullable|email',
            'image' => 'nullable|image|mimes:jpeg,jpg,png',
        ]);

        $result = $this->customerService->createCustomer($request->all());

        if (!$result['success']) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['message'], 'errors' => ['name' => [$result['message']]]], 422);
            }
            return back()->withErrors(['name' => $result['message']])->withInput();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Customer created successfully.']);
        }
        return redirect()->route('admin.customer')->with('success', 'Customer created');
    }

    public function quickCreate(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'payment_terms' => 'required',
            'email' => 'nullable|email',
            'image' => 'nullable|image|mimes:jpeg,jpg,png',
        ]);

        $result = $this->customerService->quickCreateCustomer($request->all());

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'customer' => $result['customer']
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'payment_terms' => 'required',
            'email' => 'nullable|email',
            'image' => 'nullable|image|mimes:jpeg,jpg,png',
        ]);

        $customer = Customer::find($id);
        $this->customerService->updateCustomer($customer, $request->all());

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Customer updated successfully.']);
        }
        return redirect()->route('admin.customer')->with('success', 'Customer updated');
    }

    public function destroy($id)
    {
        $customer = Customer::find($id);
        $this->customerService->deleteCustomer($customer);

        return redirect()->route('admin.customer')->with('success', 'Customer deleted');
    }

    public function getHistoricalPurchases(Customer $customer)
    {
        $historicalPurchases = $this->customerService->getHistoricalPurchases($customer);

        return response()->json([
            'success' => true,
            'historical_purchases' => $historicalPurchases,
        ]);
    }
}
