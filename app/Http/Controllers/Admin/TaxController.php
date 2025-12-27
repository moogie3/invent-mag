<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use App\Services\TaxService;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    protected $taxService;

    public function __construct(TaxService $taxService)
    {
        $this->taxService = $taxService;
    }

    public function index()
    {
        $tax = $this->taxService->getTaxData();
        return view('admin.tax.tax', compact('tax'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        try {
            $this->taxService->updateTax($validated);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Tax settings updated successfully!']);
            }

            return redirect()->back()->with('success', 'Tax settings updated successfully!');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error updating tax settings: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Error updating tax settings: ' . $e->getMessage());
        }
    }
}
