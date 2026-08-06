<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tenants = DB::table('tenants')->where('tax_jurisdiction', 'ID')->get();

        foreach ($tenants as $tenant) {
            // Convert the previously seeded PPH 22 (1.5%) into PPh Final 0.5%
            // (PP 23/2018 — the UMKM final income tax). Renaming in place keeps
            // existing pph_tax_id references valid; the per-document snapshots
            // (pph_rate/pph_amount) are stored at transaction time, so historical
            // records are unaffected.
            $converted = DB::table('taxes')
                ->where('tenant_id', $tenant->id)
                ->where('name', 'PPH 22')
                ->where('rate', 1.5)
                ->where('is_pph', true)
                ->update([
                    'name' => 'PPh Final 0.5%',
                    'rate' => 0.5,
                    'description' => 'Pajak Penghasilan Final 0,5% berdasarkan PP 23/2018 (UMKM, omzet ≤ Rp4,8 miliar)',
                    'updated_at' => now(),
                ]);

            if ($converted > 0) {
                continue;
            }

            $exists = DB::table('taxes')
                ->where('tenant_id', $tenant->id)
                ->where('name', 'PPh Final 0.5%')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('taxes')->insert([
                'tenant_id' => $tenant->id,
                'name' => 'PPh Final 0.5%',
                'rate' => 0.5,
                'type' => 'exclusive',
                'description' => 'Pajak Penghasilan Final 0,5% berdasarkan PP 23/2018 (UMKM, omzet ≤ Rp4,8 miliar)',
                'is_active' => true,
                'is_default' => false,
                'is_ppn' => false,
                'is_pph' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('taxes')
            ->where('name', 'PPh Final 0.5%')
            ->where('is_pph', true)
            ->update([
                'name' => 'PPH 22',
                'rate' => 1.5,
                'description' => 'Pajak Penghasilan Pasal 22 (Withholding tax on goods purchases)',
                'updated_at' => now(),
            ]);
    }
};
