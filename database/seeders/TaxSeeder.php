<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tax;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;

        Tax::updateOrCreate(
            ['name' => 'No Tax', 'tenant_id' => $tenantId],
            [
                'rate' => 0,
                'is_active' => true,
            ]
        );

        Tax::updateOrCreate(
            ['name' => 'VAT', 'tenant_id' => $tenantId],
            [
                'rate' => 10,
                'is_active' => false,
            ]
        );
    }
}
