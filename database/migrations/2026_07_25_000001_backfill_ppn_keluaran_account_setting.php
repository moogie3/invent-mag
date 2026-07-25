<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $settings = json_decode($tenant->accounting_settings, true) ?? [];

            if (isset($settings['ppn_keluaran_account_id'])) {
                continue;
            }

            $tenantId = $tenant->id;
            $outputVat = DB::table('accounts')
                ->where('tenant_id', $tenantId)
                ->where('code', '2130-' . $tenantId)
                ->first();

            $settings['ppn_keluaran_account_id'] = $outputVat?->id;

            DB::table('tenants')
                ->where('id', $tenant->id)
                ->update(['accounting_settings' => DB::raw("'" . addslashes(json_encode($settings)) . "'")]);
        }
    }

    public function down(): void
    {
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $settings = json_decode($tenant->accounting_settings, true) ?? [];
            unset($settings['ppn_keluaran_account_id']);

            DB::table('tenants')
                ->where('id', $tenant->id)
                ->update(['accounting_settings' => DB::raw("'" . addslashes(json_encode($settings)) . "'")]);
        }
    }
};
