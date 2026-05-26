<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['professional', 'enterprise'] as $slug) {
            $plan = Plan::where('slug', $slug)->first();
            if ($plan && !in_array('cash_flow', $plan->features ?? [])) {
                $plan->features = array_merge($plan->features ?? [], ['cash_flow']);
                $plan->save();
            }
        }
    }

    public function down(): void
    {
        foreach (['professional', 'enterprise'] as $slug) {
            $plan = Plan::where('slug', $slug)->first();
            if ($plan) {
                $plan->features = array_values(array_filter($plan->features ?? [], fn($f) => $f !== 'cash_flow'));
                $plan->save();
            }
        }
    }
};
