<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tenants = DB::table('tenants')->where('tax_jurisdiction', 'ID')->get();

        foreach ($tenants as $tenant) {
            $exists = DB::table('taxes')
                ->where('tenant_id', $tenant->id)
                ->where('name', 'PPH 22')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('taxes')->insert([
                'tenant_id' => $tenant->id,
                'name' => 'PPH 22',
                'rate' => 1.5,
                'type' => 'exclusive',
                'description' => 'Pajak Penghasilan Pasal 22 (Withholding tax on goods purchases)',
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
            ->where('name', 'PPH 22')
            ->where('is_pph', true)
            ->delete();
    }
};
