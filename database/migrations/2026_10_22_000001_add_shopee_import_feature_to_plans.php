<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $plans = Plan::whereIn('slug', ['enterprise'])->get();
        foreach ($plans as $plan) {
            $features = $plan->features ?? [];
            if (! in_array('shopee_import', $features)) {
                $features[] = 'shopee_import';
                $plan->features = $features;
                $plan->save();
            }
        }
    }

    public function down(): void
    {
        $plans = Plan::whereIn('slug', ['enterprise'])->get();
        foreach ($plans as $plan) {
            $features = $plan->features ?? [];
            if (($key = array_search('shopee_import', $features)) !== false) {
                unset($features[$key]);
                $plan->features = array_values($features);
                $plan->save();
            }
        }
    }
};
