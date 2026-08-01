<?php

namespace Database\Seeders;

use App\Models\Categories;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;

        Categories::updateOrCreate(
            ['name' => 'TR', 'tenant_id' => $tenantId],
            ['description' => 'Transistor']
        );
        Categories::updateOrCreate(
            ['name' => 'FBT', 'tenant_id' => $tenantId],
            ['description' => 'Flyback']
        );
        Categories::updateOrCreate(
            ['name' => 'IC', 'tenant_id' => $tenantId],
            ['description' => 'Integrated Circuit']
        );
    }
}
