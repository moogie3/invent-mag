<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tax;

class TaxController extends Controller
{
    public function index()
{
    $tax = Tax::first(); // Get the first tax record (or modify as needed)
    return view('admin.tax.tax', compact('tax'));
}

    public function update(Request $request)
{
    // Debugging: See what Laravel receives
    // dd($request->all());

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'rate' => 'required|numeric|min:0',
        'is_active' => 'boolean', // ✅ Ensures correct type
    ]);

    // Find or create tax settings
    $tax = Tax::firstOrNew();
    $tax->name = $validated['name'];
    $tax->rate = $validated['rate'];
    $tax->is_active = $validated['is_active']; // ✅ Ensure boolean handling
    $tax->save();

    return redirect()->back()->with('success', 'Tax settings updated successfully!');
}


}
