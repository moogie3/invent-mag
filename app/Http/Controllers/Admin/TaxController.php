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

        $this->taxService->updateTax($validated);

        return redirect()->back()->with('success', 'Tax settings updated successfully!');
    }
}
