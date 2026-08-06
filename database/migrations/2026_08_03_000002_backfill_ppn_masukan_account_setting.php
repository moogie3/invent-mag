<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $settings = is_array($tenant->accounting_settings) ? $tenant->accounting_settings : json_decode($tenant->accounting_settings, true) ?? [];

            if (isset($settings['ppn_masukan_account_id'])) {
                continue;
            }

            $tenantId = $tenant->id;
            $inputVat = DB::table('accounts')
                ->where('tenant_id', $tenantId)
                ->where('code', '1160-'.$tenantId)
                ->first();

            $settings['ppn_masukan_account_id'] = $inputVat?->id;

            DB::table('tenants')
                ->where('id', $tenant->id)
                ->update(['accounting_settings' => DB::raw("'".addslashes(json_encode($settings))."'")]);
        }
    }

    public function down(): void
    {
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $settings = is_array($tenant->accounting_settings) ? $tenant->accounting_settings : json_decode($tenant->accounting_settings, true) ?? [];
            unset($settings['ppn_masukan_account_id']);

            DB::table('tenants')
                ->where('id', $tenant->id)
                ->update(['accounting_settings' => DB::raw("'".addslashes(json_encode($settings))."'")]);
        }
    }
};
