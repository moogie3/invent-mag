<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CRITICAL FIX: Move accounting_settings from users table to tenants table.
 *
 * The bug: accounting_settings were stored per-user, meaning journal entries
 * would silently fail (or use wrong accounts) when non-admin users created
 * sales/purchases. Accounting configuration is a tenant-level concern.
 *
 * Migration strategy:
 *  1. Add accounting_settings JSON column to tenants table.
 *  2. For each tenant, copy the accounting_settings from the first user
 *     who has them configured (typically the superuser/admin who set it up).
 *  3. Leave users.accounting_settings in place for this release but stop
 *     reading/writing it in application code (deprecated).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add column to tenants table
        Schema::table('tenants', function (Blueprint $table) {
            $table->json('accounting_settings')->nullable()->after('backup_encrypted');
        });

        // Step 2: Migrate existing data — copy from the first configured user per tenant
        // We use DB:: directly here since Eloquent models are not stable in migrations.
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            // Find any user in this tenant that has accounting_settings configured
            $user = DB::table('users')
                ->where('tenant_id', $tenant->id)
                ->whereNotNull('accounting_settings')
                ->where('accounting_settings', '!=', 'null')
                ->where('accounting_settings', '!=', '[]')
                ->where('accounting_settings', '!=', '{}')
                ->first();

            if ($user && $user->accounting_settings) {
                DB::table('tenants')
                    ->where('id', $tenant->id)
                    ->update(['accounting_settings' => $user->accounting_settings]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('accounting_settings');
        });
    }
};
