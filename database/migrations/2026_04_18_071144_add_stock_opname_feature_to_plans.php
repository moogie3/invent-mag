<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Plan;

return new class extends Migration
{
    public function up(): void
    {
        $plans = Plan::whereIn('slug', ['professional', 'enterprise'])->get();
        foreach ($plans as $plan) {
            $features = $plan->features ?? [];
            if (!in_array('stock_opname', $features)) {
                $features[] = 'stock_opname';
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
            if (($key = array_search('stock_opname', $features)) !== false) {
                unset($features[$key]);
                $plan->features = array_values($features);
                $plan->save();
            }
        }
    }
};