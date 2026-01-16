<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;

        Unit::updateOrCreate(
            ['name' => 'Pieces', 'tenant_id' => $tenantId],
            ['symbol' => 'PCS']
        );

        Unit::updateOrCreate(
            ['name' => 'Roll', 'tenant_id' => $tenantId],
            ['symbol' => 'Roll']
        );

        Unit::updateOrCreate(
            ['name' => 'Meters', 'tenant_id' => $tenantId],
            ['symbol' => 'M']
        );
    }
}
