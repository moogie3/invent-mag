<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Add 'ppn' feature slug to Professional and Enterprise plans.
     * Phase 1: PDF Faktur Pajak + DJP CSV export.
     * Follows the pattern of 2026_04_18_071144_add_stock_opname_feature_to_plans.php
     */
    public function up(): void
    {
        $plans = Plan::whereIn('slug', ['professional', 'enterprise'])->get();

        foreach ($plans as $plan) {
            $features = $plan->features ?? [];
            if (! in_array('ppn', $features)) {
                $features[] = 'ppn';
                $plan->features = $features;
                $plan->save();
            }
        }
    }

    public function down(): void
    {
        $plans = Plan::whereIn('slug', ['professional', 'enterprise'])->get();

        foreach ($plans as $plan) {
            $features = $plan->features ?? [];
            $plan->features = array_values(array_filter($features, fn($f) => $f !== 'ppn'));
            $plan->save();
        }
    }
};
