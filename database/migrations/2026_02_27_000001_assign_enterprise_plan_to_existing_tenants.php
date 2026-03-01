<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Assign the enterprise plan to all existing tenants that have no plan assigned.
     * This ensures backward compatibility — existing tenants keep full access.
     */
    public function up(): void
    {
        // Only run if both tables exist
        if (! Schema::hasTable('plans') || ! Schema::hasTable('tenants')) {
            return;
        }

        $enterprisePlan = DB::table('plans')->where('slug', 'enterprise')->first();

        if (! $enterprisePlan) {
            return; // Plans haven't been seeded yet; the seeder will handle this
        }

        DB::table('tenants')
            ->whereNull('plan_id')
            ->update([
                'plan_id' => $enterprisePlan->id,
                'plan_status' => 'active',
                'plan_changed_at' => now(),
            ]);
    }

    /**
     * Reverse: set plan_id back to null for tenants that were auto-assigned.
     */
    public function down(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        $enterprisePlan = DB::table('plans')->where('slug', 'enterprise')->first();

        if ($enterprisePlan) {
            // Only revert tenants that were assigned during this migration
            // We can't perfectly distinguish, so we just null out enterprise-plan tenants
            // that don't have a trial_ends_at (i.e., were not created via registration flow)
            DB::table('tenants')
                ->where('plan_id', $enterprisePlan->id)
                ->whereNull('trial_ends_at')
                ->update([
                    'plan_id' => null,
                    'plan_status' => 'active',
                    'plan_changed_at' => null,
                ]);
        }
    }
};
