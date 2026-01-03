<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // User Management Permissions
        Permission::firstOrCreate(['name' => 'view-users']);
        Permission::firstOrCreate(['name' => 'create-users']);
        Permission::firstOrCreate(['name' => 'edit-users']);
        Permission::firstOrCreate(['name' => 'delete-users']);

        // Role Management Permissions
        Permission::firstOrCreate(['name' => 'view-roles']);
        Permission::firstOrCreate(['name' => 'create-roles']);
        Permission::firstOrCreate(['name' => 'edit-roles']);
        Permission::firstOrCreate(['name' => 'delete-roles']);

        // Product Management Permissions
        Permission::firstOrCreate(['name' => 'view-products']);
        Permission::firstOrCreate(['name' => 'create-products']);
        Permission::firstOrCreate(['name' => 'edit-products']);
        Permission::firstOrCreate(['name' => 'delete-products']);

        // Sales Management Permissions
        Permission::firstOrCreate(['name' => 'view-sales']);
        Permission::firstOrCreate(['name' => 'create-sales']);
        Permission::firstOrCreate(['name' => 'edit-sales']);
        Permission::firstOrCreate(['name' => 'delete-sales']);

        // Sales Return Management Permissions
        Permission::firstOrCreate(['name' => 'view-sales-returns']);
        Permission::firstOrCreate(['name' => 'create-sales-returns']);
        Permission::firstOrCreate(['name' => 'edit-sales-returns']);
        Permission::firstOrCreate(['name' => 'delete-sales-returns']);

        // Report Viewing Permissions
        Permission::firstOrCreate(['name' => 'view-reports']);

        // Other general permissions
        Permission::firstOrCreate(['name' => 'access-dashboard']);

        // PO Management Permissions
        Permission::firstOrCreate(['name' => 'view-po']);
        Permission::firstOrCreate(['name' => 'create-po']);
        Permission::firstOrCreate(['name' => 'edit-po']);
        Permission::firstOrCreate(['name' => 'delete-po']);

        // Purchase Return Management Permissions
        Permission::firstOrCreate(['name' => 'view-purchase-returns']);
        Permission::firstOrCreate(['name' => 'create-purchase-returns']);
        Permission::firstOrCreate(['name' => 'edit-purchase-returns']);
        Permission::firstOrCreate(['name' => 'delete-purchase-returns']);

        // Warehouse Management Permissions
        Permission::firstOrCreate(['name' => 'view-warehouse']);
        Permission::firstOrCreate(['name' => 'create-warehouse']);
        Permission::firstOrCreate(['name' => 'edit-warehouse']);
        Permission::firstOrCreate(['name' => 'delete-warehouse']);

        // POS Management Permissions
        Permission::firstOrCreate(['name' => 'view-pos']);
        Permission::firstOrCreate(['name' => 'create-pos']);
        Permission::firstOrCreate(['name' => 'edit-pos']);
        Permission::firstOrCreate(['name' => 'delete-pos']);

        // Customer Management Permissions
        Permission::firstOrCreate(['name' => 'view-customer']);
        Permission::firstOrCreate(['name' => 'create-customer']);
        Permission::firstOrCreate(['name' => 'edit-customer']);
        Permission::firstOrCreate(['name' => 'delete-customer']);

        // Supplier Management Permissions
        Permission::firstOrCreate(['name' => 'view-supplier']);
        Permission::firstOrCreate(['name' => 'create-supplier']);
        Permission::firstOrCreate(['name' => 'edit-supplier']);
        Permission::firstOrCreate(['name' => 'delete-supplier']);

        // Sales Pipeline Permissions
        Permission::firstOrCreate(['name' => 'view-sales-pipeline']);
        Permission::firstOrCreate(['name' => 'manage-sales-pipelines']);
        Permission::firstOrCreate(['name' => 'manage-pipeline-stages']);
        Permission::firstOrCreate(['name' => 'manage-sales-opportunities']);

        // Accounting Permissions
        Permission::firstOrCreate(['name' => 'view-accounting']);
        Permission::firstOrCreate(['name' => 'view-accounts']);

        // Category Management Permissions
        Permission::firstOrCreate(['name' => 'view-categories']);
        Permission::firstOrCreate(['name' => 'create-categories']);
    }
}
