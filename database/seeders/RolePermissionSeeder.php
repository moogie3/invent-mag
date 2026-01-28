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
            'access-pos',
            'view-sales',
            'create-sales',
            'edit-sales',
            'view-purchase-orders',
            'create-purchase-orders',
            'edit-purchase-orders',
            'view-products',
            'create-products',
            'edit-products',
            'view-warehouses',
            'create-warehouses',
            'edit-warehouses',
        ]);

        // Create POS Role and assign permissions
        $posRole = Role::firstOrCreate(['name' => 'pos']);
        $posRole->givePermissionTo([
            'access-dashboard',
            'access-pos',
            'view-sales',
            'view-warehouses',
            'view-products',
            'view-reports',
        ]);
    }
}