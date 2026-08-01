<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;

        Tax::updateOrCreate(
            ['name' => 'PPN 11%', 'tenant_id' => $tenantId],
            [
                'rate' => 11,
                'type' => Tax::TYPE_EXCLUSIVE,
                'description' => 'Pajak Pertambahan Nilai 11% (Standard VAT for Indonesia)',
                'is_active' => true,
                'is_default' => true,
                'is_ppn' => true,
            ]
        );

        Tax::updateOrCreate(
            ['name' => 'PPN 12%', 'tenant_id' => $tenantId],
            [
                'rate' => 12,
                'type' => Tax::TYPE_EXCLUSIVE,
                'description' => 'Pajak Pertambahan Nilai 12% (Higher VAT rate)',
                'is_active' => true,
                'is_default' => false,
                'is_ppn' => true,
            ]
        );

        Tax::updateOrCreate(
            ['name' => 'PPH 23', 'tenant_id' => $tenantId],
            [
                'rate' => 2,
                'type' => Tax::TYPE_EXCLUSIVE,
                'description' => 'Pajak Penghasilan Pasal 23 (Withholding tax on services)',
                'is_active' => true,
                'is_default' => false,
                'is_pph' => true,
            ]
        );

        Tax::updateOrCreate(
            ['name' => 'Non-PKP', 'tenant_id' => $tenantId],
            [
                'rate' => 0,
                'type' => Tax::TYPE_NONE,
                'description' => 'Non-taxable / VAT-exempt transaction',
                'is_active' => true,
                'is_default' => false,
            ]
        );

        Tax::updateOrCreate(
            ['name' => 'PPN Inclusive', 'tenant_id' => $tenantId],
            [
                'rate' => 11,
                'type' => Tax::TYPE_INCLUSIVE,
                'description' => 'PPN 11% with inclusive pricing (tax already included in item prices)',
                'is_active' => true,
                'is_default' => false,
            ]
        );
    }
}
