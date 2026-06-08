<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    public function index()
    {
        $plans = Plan::ordered()->get();
        $allFeatures = static::getAllFeatures();

        return view('superadmin.features.index', compact('plans', 'allFeatures'));
    }

    public function update(Request $request)
    {
        $features = $request->input('features', []);

        foreach ($features as $planId => $planFeatures) {
            $plan = Plan::findOrFail($planId);
            $plan->update(['features' => array_values($planFeatures)]);
        }

        // Plans not submitted get empty features
        $submittedPlanIds = array_keys($features);
        Plan::whereNotIn('id', $submittedPlanIds)->update(['features' => []]);

        return redirect()->route('superadmin.features.index')
            ->with('success', 'Feature assignments updated successfully.');
    }

    public static function getAllFeatures(): array
    {
        return config('plans.features');
    }
}
