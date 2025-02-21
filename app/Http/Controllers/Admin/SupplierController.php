<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request){
        $entries = $request->input('entries', 10);
        $suppliers = Supplier::paginate($entries);
        return view ('admin.supplier.index', compact('suppliers','entries'));
    }

    public function create(){
        $suppliers = Supplier::all();
        return view('admin.supplier.supplier-create', compact('suppliers'));
    }

    public function edit($id)
    {
        $suppliers = Supplier::find($id);
        return view('admin.supplier.supplier-edit', ['suppliers' => $suppliers]);
    }

    public function store(Request $request){
        $request->validate([
            'code' => 'required',
            'name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'location' => 'required|in:IN,OUT',
            'payment_terms' => 'required'
        ]);

        $isSupplierExist = Supplier::where('name', $request->name)->exists();

        if ($isSupplierExist) {
            return back()
            ->withErrors([
                'name' => 'This supplier already exist'
            ])

            ->withInput();
        }


        $data = $request->except("_token");

        Supplier::create($data);

        return redirect()->route('admin.supplier')->with('success','Supplier created');
    }

    public function update(Request $request, $id){
        $data = $request->except("_token");
        $request->validate([
            'code' => 'required',
            'name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'location' => 'required',
            'payment_terms' => 'required'
        ]);

        $suppliers = Supplier::find($id);
        $suppliers->update($data);
        return redirect()->route('admin.supplier')->with('success', 'Supplier updated');
    }

    public function destroy($id)
    {
        Supplier::find($id)->delete();

        return redirect()->route('admin.supplier')->with('success', 'Unit deleted');
    }
}