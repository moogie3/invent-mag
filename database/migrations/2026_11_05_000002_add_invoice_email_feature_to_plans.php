<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Invoice email delivery (§5.4) is available on ALL tiers: the slug is
     * declared on every plan so plan pages advertise it and the gate can be
     * tightened later by a single migration if needed.
     */
    private const FEATURES = ['invoice_email'];

    public function up(): void
    {
        foreach (Plan::all() as $plan) {
            $features = $plan->features ?? [];
            $added = false;

            foreach (self::FEATURES as $feature) {
                if (! in_array($feature, $features, true)) {
                    $features[] = $feature;
                    $added = true;
                }
            }

            if ($added) {
                $plan->features = $features;
                $plan->save();
            }
        }
    }

    public function down(): void
    {
        foreach (Plan::all() as $plan) {
            $features = $plan->features ?? [];
            $filtered = array_values(array_diff($features, self::FEATURES));

            if ($filtered !== $features) {
                $plan->features = $filtered;
                $plan->save();
            }
        }
    }
};
