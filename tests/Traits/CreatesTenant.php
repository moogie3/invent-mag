<?php

namespace Tests\Traits;

use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Role;

trait CreatesTenant
{
    protected Tenant $tenant;
    protected User $user;

    public function setupTenant()
    {
        // Create a tenant
        $this->tenant = Tenant::create(['name' => 'Test Tenant', 'domain' => 'test.localhost']);

        // Set the tenant as the current tenant
        $this->tenant->makeCurrent();

        // Create a user for this tenant
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create necessary accounts for testing
        $inventoryAccount = \App\Models\Account::updateOrCreate(
            ['name' => 'Inventory ' . $this->tenant->id],
            [
                'code' => '1200' . $this->tenant->id,
                'type' => 'asset',
                'tenant_id' => $this->tenant->id,
            ]
        );

        $cashAccount = \App\Models\Account::updateOrCreate(
            ['name' => 'Cash ' . $this->tenant->id],
            [
                'code' => '1000' . $this->tenant->id,
                'type' => 'asset',
                'tenant_id' => $this->tenant->id,
            ]
        );

        $accountsPayable = \App\Models\Account::updateOrCreate(
            ['name' => 'Accounts Payable ' . $this->tenant->id],
            [
                'code' => '2000' . $this->tenant->id,
                'type' => 'liability',
                'tenant_id' => $this->tenant->id,
            ]
        );

        $accountsReceivable = \App\Models\Account::updateOrCreate(
            ['name' => 'Accounts Receivable ' . $this->tenant->id],
            [
                'code' => '1100' . $this->tenant->id,
                'type' => 'asset',
                'tenant_id' => $this->tenant->id,
            ]
        );

        $salesRevenue = \App\Models\Account::updateOrCreate(
            ['name' => 'Sales Revenue ' . $this->tenant->id],
            [
                'code' => '4000' . $this->tenant->id,
                'type' => 'revenue',
                'tenant_id' => $this->tenant->id,
            ]
        );

        $cogs = \App\Models\Account::updateOrCreate(
            ['name' => 'Cost of Goods Sold ' . $this->tenant->id],
            [
                'code' => '5000' . $this->tenant->id,
                'type' => 'expense',
                'tenant_id' => $this->tenant->id,
            ]
        );

        // Update user's accounting settings
        $this->user->update([
            'accounting_settings' => [
                'inventory_account_id' => $inventoryAccount->id,
                'cash_account_id' => $cashAccount->id,
                'accounts_payable_account_id' => $accountsPayable->id,
                'accounts_receivable_account_id' => $accountsReceivable->id,
                'sales_revenue_account_id' => $salesRevenue->id,
                'cost_of_goods_sold_account_id' => $cogs->id,
            ],
        ]);

        // Assign a role to the user
        $role = Role::firstOrCreate(['name' => 'superuser', 'guard_name' => 'web']);
        $this->user->assignRole($role);

        // Act as this user for the test
        $this->actingAs($this->user);
    }
}
