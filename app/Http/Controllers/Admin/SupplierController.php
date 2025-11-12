<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    protected $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    public function getMetrics()
    {
        $metrics = $this->supplierService->getSupplierMetrics();
        return response()->json($metrics);
    }

    public function index(Request $request)
    {
        $entries = $request->input('entries', 10);
        $data = $this->supplierService->getSupplierIndexData($entries);
        return view('admin.supplier.index', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'location' => 'required|in:IN,OUT',
            'payment_terms' => 'required',
            'email' => 'nullable|email',
            'image' => 'nullable|image|mimes:jpeg,jpg,png',
        ], [
            'image.image' => 'The image field must be a valid image file. Please ensure your form has enctype="multipart/form-data" and the file input name is "image".',
            'image.mimes' => 'The image must be a file of type: jpeg, jpg, png.',
        ]);

        $result = $this->supplierService->createSupplier($request->all());

        if (!$result['success']) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['message'], 'errors' => ['name' => [$result['message']]]], 422);
            }
            return back()->withErrors(['name' => $result['message']])->withInput();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Supplier created successfully.']);
        }
        return redirect()->route('admin.supplier')->with('success', 'Supplier created');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'code' => 'required',
            'name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'location' => 'required|in:IN,OUT',
            'payment_terms' => 'required',
            'email' => 'nullable|email',
            'image' => 'nullable|image|mimes:jpeg,jpg,png',
        ], [
            'image.image' => 'The image field must be a valid image file. Please ensure your form has enctype="multipart/form-data" and the file input name is "image".',
            'image.mimes' => 'The image must be a file of type: jpeg, jpg, png.',
        ]);

        try {
            $supplier = Supplier::findOrFail($id);
            $result = $this->supplierService->updateSupplier($supplier, $request->all());

            if (!$result['success']) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $result['message']], 422);
                }
                return back()->with('error', $result['message'])->withInput();
            }

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Supplier updated successfully.']);
            }
            return redirect()->route('admin.supplier')->with('success', 'Supplier updated');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'An unexpected error occurred.'], 500);
            }
            return back()->with('error', 'An unexpected error occurred.')->withInput();
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $result = $this->supplierService->deleteSupplier($supplier);

            if (!$result['success']) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $result['message']], 500);
                }
                return redirect()->route('admin.supplier')->with('error', $result['message']);
            }

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Supplier deleted successfully.']);
            }
            return redirect()->route('admin.supplier')->with('success', 'Supplier deleted');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'An unexpected error occurred.'], 500);
            }
            return redirect()->route('admin.supplier')->with('error', 'An unexpected error occurred.');
        }
    }
}
