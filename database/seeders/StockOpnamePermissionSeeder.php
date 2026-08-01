<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StockOpnamePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view-stock-opname',
            'manage-stock-opname',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superuser = Role::where('name', 'superuser')->first();
        if ($superuser) {
            $superuser->givePermissionTo($permissions);
        }

        $staff = Role::where('name', 'staff')->first();
        if ($staff) {
            $staff->givePermissionTo($permissions);
        }
    }
}
