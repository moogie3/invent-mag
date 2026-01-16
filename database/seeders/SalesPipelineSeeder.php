<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SalesPipeline;

class SalesPipelineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;
        $tenantName = app('currentTenant')->name;

        SalesPipeline::updateOrCreate(
            ['name' => 'Default Pipeline - ' . $tenantName, 'tenant_id' => $tenantId],
            [
                'description' => 'The default sales pipeline.',
                'is_default' => true,
            ]
        );
    }
}
