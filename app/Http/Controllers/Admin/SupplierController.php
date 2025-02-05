<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(){
        $suppliers = Supplier::all();
        return view ('admin.supplier.index', ['suppliers' => $suppliers]);
    }

    public function create(){
        $suppliers = Supplier::all();
        return view('admin.supplier.supplier-create', compact('suppliers'));
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

    public function destroy($id)
    {
        Supplier::find($id)->delete();

        return redirect()->route('admin.supplier')->with('success', 'Unit deleted');
    }
}