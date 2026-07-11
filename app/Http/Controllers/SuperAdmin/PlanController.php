<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\SuperAdmin\FeatureController;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\CurrencyService;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::withCount('tenants')->ordered()->get();
        return view('superadmin.plans.index', compact('plans'));
    }

    public function create()
    {
        $allFeatures = FeatureController::getAllFeatures();
        $predefinedCurrencies = (new CurrencyService)->getPredefinedCurrencies();
        return view('superadmin.plans.create', compact('allFeatures', 'predefinedCurrencies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'required|string|unique:plans,slug',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'currency_code' => 'nullable|string|max:3',
            'billing_cycle' => 'required|in:monthly,yearly',
            'description' => 'nullable|string',
            'max_users' => 'required|integer|min:-1',
            'max_warehouses' => 'required|integer|min:-1',
            'max_products' => 'required|integer|min:-1',
            'features' => 'nullable|array',
            'trial_days' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['features'] = $request->input('features', []);

        Plan::create($validated);

        return redirect()->route('superadmin.plans.index')
            ->with('success', 'Plan created successfully.');
    }

    public function edit(Plan $plan)
    {
        $allFeatures = FeatureController::getAllFeatures();
        $predefinedCurrencies = (new CurrencyService)->getPredefinedCurrencies();
        return view('superadmin.plans.edit', compact('plan', 'allFeatures', 'predefinedCurrencies'));
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'slug' => 'required|string|unique:plans,slug,' . $plan->id,
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'currency_code' => 'nullable|string|max:3',
            'billing_cycle' => 'required|in:monthly,yearly',
            'description' => 'nullable|string',
            'max_users' => 'required|integer|min:-1',
            'max_warehouses' => 'required|integer|min:-1',
            'max_products' => 'required|integer|min:-1',
            'features' => 'nullable|array',
            'trial_days' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['features'] = $request->input('features', []);

        $plan->update($validated);

        return redirect()->route('superadmin.plans.index')
            ->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->tenants()->count() > 0) {
            return back()->with('error', 'Cannot delete plan with active subscribers.');
        }

        $plan->delete();

        return redirect()->route('superadmin.plans.index')
            ->with('success', 'Plan deleted successfully.');
    }
}
