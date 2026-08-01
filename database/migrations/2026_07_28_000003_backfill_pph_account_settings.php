<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $tenantId = $tenant->id;
            $settings = is_array($tenant->accounting_settings) ? $tenant->accounting_settings : json_decode($tenant->accounting_settings, true) ?? [];

            if (isset($settings['pph_receivable_account_id']) && isset($settings['pph_payable_account_id'])) {
                continue;
            }

            $pphReceivable = DB::table('accounts')
                ->where('tenant_id', $tenantId)
                ->where('code', '1170-'.$tenantId)
                ->first();

            $pphPayable = DB::table('accounts')
                ->where('tenant_id', $tenantId)
                ->where('code', '2150-'.$tenantId)
                ->first();

            $settings['pph_receivable_account_id'] = $pphReceivable?->id;
            $settings['pph_payable_account_id'] = $pphPayable?->id;

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
            unset($settings['pph_receivable_account_id'], $settings['pph_payable_account_id']);

            DB::table('tenants')
                ->where('id', $tenant->id)
                ->update(['accounting_settings' => DB::raw("'".addslashes(json_encode($settings))."'")]);
        }
    }
};
