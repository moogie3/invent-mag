<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure superuser role exists and has all permissions
        $superUserRole = Role::firstOrCreate(['name' => 'superuser']);
        $allPermissions = Permission::all();
        $superUserRole->syncPermissions($allPermissions);

        // Create Staff Role and assign permissions
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staffRole->givePermissionTo([
            'access-dashboard',
            'view-pos',
            'create-pos',
            'edit-pos',
            'view-sales',
            'create-sales',
            'edit-sales',
            'view-po',
            'create-po',
            'edit-po',
            'view-products',
            'create-products',
            'edit-products',
            'view-warehouse',
            'create-warehouse',
            'edit-warehouse',
        ]);

        // Create POS Role and assign permissions
        $posRole = Role::firstOrCreate(['name' => 'pos']);
        $posRole->givePermissionTo([
            'access-dashboard',
            'view-pos',
            'create-pos',
            'view-sales',
            'delete-sales',
            'view-warehouse',
            'view-products',
            'view-reports',
        ]);
    }
}