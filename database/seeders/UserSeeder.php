<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = app('currentTenant');
        $tenantId = $tenant->id;
        $tenantName = $tenant->name;
        $tenantNameSlug = strtolower(str_replace(' ', '-', $tenantName));

        $user = User::updateOrCreate(
            ['email' => 'admin-' . $tenantNameSlug . '@gmail.com', 'tenant_id' => $tenantId],
            [
                'name' => 'admin-' . $tenantNameSlug,
                'password' => Hash::make('password'),
                'avatar' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $role = Role::where('name', 'superuser')->first();
        if ($role) {
            $user->assignRole($role);
        }

        // Set default accounting settings
        $cashAccount = Account::where('name', 'accounting.accounts.cash.name - ' . $tenantName)->first();
        $accountsPayableAccount = Account::where('name', 'accounting.accounts.accounts_payable.name - ' . $tenantName)->first();
        $inventoryAccount = Account::where('name', 'accounting.accounts.inventory.name - ' . $tenantName)->first();
        $salesRevenueAccount = Account::where('name', 'accounting.accounts.sales_revenue.name - ' . $tenantName)->first();
        $accountsReceivableAccount = Account::where('name', 'accounting.accounts.accounts_receivable.name - ' . $tenantName)->first();
        $costOfGoodsSoldAccount = Account::where('name', 'accounting.accounts.cost_of_goods_sold.name - ' . $tenantName)->first();


        if ($cashAccount && $accountsPayableAccount && $inventoryAccount && $salesRevenueAccount && $accountsReceivableAccount && $costOfGoodsSoldAccount) {
            $user->accounting_settings = [
                'cash_account_id' => $cashAccount->id,
                'accounts_payable_account_id' => $accountsPayableAccount->id,
                'inventory_account_id' => $inventoryAccount->id,
                'sales_revenue_account_id' => $salesRevenueAccount->id,
                'accounts_receivable_account_id' => $accountsReceivableAccount->id,
                'cost_of_goods_sold_account_id' => $costOfGoodsSoldAccount->id,
            ];
            $user->save();
        }
    }
}