<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurrencySetting;
use App\Models\Unit;
use App\Services\CurrencyService;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    protected $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function edit(Request $request)
    {
        $entries = $request->input('entries', 10);
        $data = $this->currencyService->getCurrencyEditData($entries);
        return view('admin.currency.currency-edit', $data);
    }

    public function update(Request $request)
    {
        $request->validate([
            'currency_symbol' => 'required|string|max:5',
            'decimal_separator' => 'required|string|max:1',
            'thousand_separator' => 'required|string|max:1',
            'decimal_places' => 'required|integer|min:0|max:4',
        ]);

        $this->currencyService->updateCurrency($request->all());

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Currency settings updated successfully.']);
        }

        return redirect()->back()->with('success', 'Currency settings updated successfully.');
    }
}
