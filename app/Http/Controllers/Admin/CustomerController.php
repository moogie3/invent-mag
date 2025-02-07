<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(){
        $customers = Customer::all();
        return view ('admin.customer.index', ['customers' => $customers]);
    }

    public function create(){
        $customers = Customer::all();
        return view ('admin.customer.customer-create', compact('customers'));
    }

    public function edit($id)
    {
        $customers = Customer::find($id);
        return view('admin.customer.customer-edit', ['customers' => $customers]);
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
        ]);

        $isCustomerExists = Customer::where('name', $request->name)->exists();

        if ($isCustomerExists) {
            return back()
            ->withErrors([
                'name' => 'This customer already exist'
            ])

            ->withInput();
        }

        $data = $request->except("_token");

        Customer::create($data);

        return redirect()->route('admin.customer', )->with('success', 'Customer created');
    }

    public function update(Request $request, $id){
        $data = $request->except("_token");
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
        ]);

        $customers = Customer::find($id);
        $customers->update($data);
        return redirect()->route('admin.customer')->with('success', 'Customer updated');
    }

    public function destroy($id)
    {
        Customer::find($id)->delete();

        return redirect()->route('admin.customer')->with('success', 'Customer deleted');
    }
}