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
        try {
            $validatedData = $request->validate([
                'currency_symbol' => 'required|string|max:5',
                'decimal_separator' => 'required|string|max:1',
                'thousand_separator' => 'required|string|max:1',
                'decimal_places' => 'required|integer|min:0|max:4',
                'position' => 'required|string|in:prefix,suffix',
                'currency_code' => 'required|string|max:3',
                'locale' => 'required|string|max:10',
            ]);

            $result = $this->currencyService->updateCurrency($validatedData);

            if (!$result['success']) {
                $message = $result['message'] ?? 'An unexpected error occurred.';
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $message], 500);
                }
                return redirect()->route('admin.setting.currency.edit')->with('error', $message);
            }

            $message = 'Currency settings updated successfully.';
            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->route('admin.setting.currency.edit')->with('success', $message);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json(['errors' => $e->errors()], 422);
            }
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {

            $message = 'An unexpected error occurred. Please try again.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return redirect()->route('admin.setting.currency.edit')->with('error', $message);
        }
    }
}
