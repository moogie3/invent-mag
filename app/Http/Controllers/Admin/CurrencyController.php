<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CurrencySetting;

class CurrencyController extends Controller
{
    public function edit() {
        $setting = CurrencySetting::first();
        return view('admin.currency.currency-edit', compact('setting'));
    }

    public function update(Request $request) {
        $request->validate([
        'currency_symbol' => 'required|string|max:5',
        'decimal_separator' => 'required|string|max:1',
        'thousand_separator' => 'required|string|max:1',
        'decimal_places' => 'required|integer|min:0|max:4',
    ]);

    $setting = CurrencySetting::first();
    $setting->update($request->all());

    return redirect()->back()->with('success', 'Currency settings updated successfully.');
    }
}