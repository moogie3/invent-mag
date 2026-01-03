<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure permissions are seeded first
        $this->call(PermissionSeeder::class);

        $superuserRole = Role::updateOrCreate(['name' => 'superuser', 'guard_name' => 'web']);
        Role::updateOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        Role::updateOrCreate(['name' => 'pos', 'guard_name' => 'web']);
        Role::updateOrCreate(['name' => 'accountant', 'guard_name' => 'web']);

        // Assign all permissions to the superuser role
        $allPermissions = Permission::all();
        $superuserRole->syncPermissions($allPermissions);
    }
}

