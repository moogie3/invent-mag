<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Define all permissions
        $permissions = [
            // Products
            'view-products',
            'create-products',
            'edit-products',
            'delete-products',
            
            // Purchase Orders
            'view-purchase-orders',
            'create-purchase-orders',
            'edit-purchase-orders',
            'delete-purchase-orders',
            
            // Sales
            'view-sales',
            'create-sales',
            'edit-sales',
            'delete-sales',
            
            // Warehouses
            'view-warehouses',
            'create-warehouses',
            'edit-warehouses',
            'delete-warehouses',
            
            // Reports
            'view-reports',
            'view-financial-reports',
            'edit-transactions',
            
            // Accounting
            'view-accounting',
            'view-chart-of-accounts',
            'edit-chart-of-accounts',
            'delete-chart-of-accounts',
            'view-journal',
            'view-general-ledger',
            'view-trial-balance',
            
            // POS
            'access-pos',
            'view-pos',
            'create-pos',
            'edit-pos',
            'delete-pos-transactions',
            
            // Suppliers
            'view-supplier',
            'create-supplier',
            'edit-supplier',
            'delete-supplier',
            
            // Customers
            'view-customer',
            'create-customer',
            'edit-customer',
            'delete-customer',

            // User Management
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',

            // Role Management
            'view-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',

            // Sales Return Management
            'view-sales-returns',
            'create-sales-returns',
            'edit-sales-returns',
            'delete-sales-returns',

            // Other general permissions
            'access-dashboard',

            // Purchase Return Management
            'view-purchase-returns',
            'create-purchase-returns',
            'edit-purchase-returns',
            'delete-purchase-returns',

            // Sales Pipeline
            'view-sales-pipeline',
            'manage-sales-pipelines',
            'manage-pipeline-stages',
            'manage-sales-opportunities',

            // Payment
            'view-payments',
            'create-payments',
            'edit-payments',
            'delete-payments',

            // Customer Interaction
            'view-customer-interactions',
            'create-customer-interactions',
            'edit-customer-interactions',
            'delete-customer-interactions',

            // Additional missing ones
            'view-po',
            'create-po',
            'edit-po',
            'view-warehouse',
            'create-warehouse',
            'edit-warehouse',

            // Manual Journal Entries
            'view-manual-journal',
            'create-manual-journal',
            'edit-manual-journal',
            'delete-manual-journal',
            'post-manual-journal',
            'void-manual-journal',
            'reverse-manual-journal',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $this->assignToSuperuser();
        $this->assignToPos();
        $this->assignToStaff();
    }

    private function assignToSuperuser()
    {
        $role = Role::findOrCreate('superuser');
        $role->givePermissionTo(Permission::all());
    }

    private function assignToPos()
    {
        $role = Role::findOrCreate('pos');
        $permissions = [
            'view-products', 'create-products',
            'view-sales', 'create-sales',
            'access-pos', 'delete-pos-transactions',
            'view-customer', 'create-customer', 'edit-customer', 'delete-customer',
            'view-sales-returns', 'create-sales-returns', 'edit-sales-returns', 'delete-sales-returns',
            'access-dashboard',
        ];
        $role->syncPermissions($permissions);
    }
    private function assignToStaff()
    {
        $role = Role::findOrCreate('staff');
        $permissions = [
            'view-products', 'create-products', 'edit-products', 'delete-products',
            'view-purchase-orders', 'create-purchase-orders', 'edit-purchase-orders', 'delete-purchase-orders',
            'view-sales', 'create-sales', 'edit-sales', 'delete-sales',
            'view-warehouses', 'create-warehouses', 'edit-warehouses', 'delete-warehouses',
            'view-reports', 'view-financial-reports', 'edit-transactions',
            'view-accounting', 'view-chart-of-accounts', 'edit-chart-of-accounts', 'delete-chart-of-accounts',
            'view-journal', 'view-general-ledger', 'view-trial-balance',
            'view-manual-journal', 'create-manual-journal', 'edit-manual-journal', 'delete-manual-journal',
            'post-manual-journal', 'void-manual-journal', 'reverse-manual-journal',
            'access-pos', 'delete-pos-transactions',
            'view-supplier', 'create-supplier', 'edit-supplier', 'delete-supplier',
            'view-customer', 'create-customer', 'edit-customer', 'delete-customer',
            'view-sales-returns', 'create-sales-returns', 'edit-sales-returns', 'delete-sales-returns',
            'access-dashboard',
            'view-purchase-returns', 'create-purchase-returns', 'edit-purchase-returns', 'delete-purchase-returns',
            'view-sales-pipeline', 'manage-sales-pipelines', 'manage-pipeline-stages', 'manage-sales-opportunities',
            'view-payments', 'create-payments', 'edit-payments', 'delete-payments',
            'view-customer-interactions', 'create-customer-interactions', 'edit-customer-interactions', 'delete-customer-interactions',
        ];
        $role->syncPermissions($permissions);
    }
}

