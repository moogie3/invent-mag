<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // efaktur_api = Phase 2 (PJAP API direct DJP submission) — Enterprise only
        $enterprise = Plan::where('slug', 'enterprise')->first();
        if ($enterprise) {
            $features = $enterprise->features ?? [];
            if (! in_array('efaktur_api', $features)) {
                $features[] = 'efaktur_api';
                $enterprise->features = $features;
                $enterprise->save();
            }
        }
    }

    public function down(): void
    {
        $enterprise = Plan::where('slug', 'enterprise')->first();
        if ($enterprise) {
            $features = $enterprise->features ?? [];
            if (($key = array_search('efaktur_api', $features)) !== false) {
                unset($features[$key]);
                $enterprise->features = array_values($features);
                $enterprise->save();
            }
        }
    }
};
