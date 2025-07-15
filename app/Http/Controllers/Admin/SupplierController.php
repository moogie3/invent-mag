<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request){
        $entries = $request->input('entries', 10);//pagination
        $suppliers = Supplier::paginate($entries);

        $inCount = Supplier::where('location', 'IN')->count();
        $outCount = Supplier::where('location', 'OUT')->count();

        $totalsupplier = Supplier::count();
        return view ('admin.supplier.index', compact('suppliers','entries','totalsupplier','inCount','outCount'));
    }

    public function store(Request $request){
        $request->validate([
            'code' => 'required',
            'name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'location' => 'required|in:IN,OUT',
            'payment_terms' => 'required',
            'email' => 'nullable|email',
            'image' => 'nullable|image|mimes:jpeg,jpg,png',
        ]);

        $isSupplierExist = Supplier::where('name', $request->name)->exists();

        if ($isSupplierExist) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'This supplier already exists.', 'errors' => ['name' => ['This supplier already exists.']]], 422);
            }
            return back()
            ->withErrors([
                'name' => 'This supplier already exist'
            ])
            ->withInput();
        }

        $data = $request->except(['_token', 'image']);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = \Illuminate\Support\Str::random(10) . '_' . $image->getClientOriginalName();
            $image->storeAs('public/image', $imageName);
            $data['image'] = $imageName;
        }

        Supplier::create($data);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Supplier created successfully.']);
        }
        return redirect()->route('admin.supplier')->with('success','Supplier created');
    }

    public function update(Request $request, $id){
        $data = $request->except(["_token", 'image']);
        $request->validate([
            'code' => 'required',
            'name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'location' => 'required',
            'payment_terms' => 'required',
            'email' => 'nullable|email',
            'image' => 'nullable|image|mimes:jpeg,jpg,png',
        ]);

        $suppliers = Supplier::find($id);

        if ($request->hasFile('image')) {
            $oldImagePath = 'public/image/' . basename($suppliers->image);

            if (!empty($suppliers->image) && \Illuminate\Support\Facades\Storage::exists($oldImagePath)) {
                \Illuminate\Support\Facades\Storage::delete($oldImagePath);
            }

            $image = $request->file('image');
            $imageName = \Illuminate\Support\Str::random(10) . '_' . $image->getClientOriginalName();
            $image->storeAs('public/image', $imageName);

            $data['image'] = $imageName;
        }

        $suppliers->update($data);
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Supplier updated successfully.']);
        }
        return redirect()->route('admin.supplier')->with('success', 'Supplier updated');
    }

    public function destroy($id)
    {
        $supplier = Supplier::find($id);

        if (!empty($supplier->image)) {
            \Illuminate\Support\Facades\Storage::delete('public/image/' . basename($supplier->image));
        }

        $supplier->delete();

        return redirect()->route('admin.supplier')->with('success', 'Supplier deleted');
    }
}