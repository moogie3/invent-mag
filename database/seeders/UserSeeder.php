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
        $cashAccount = Account::where('code', '1110-' . $tenantId)->first();
        $accountsPayableAccount = Account::where('code', '2110-' . $tenantId)->first();
        $inventoryAccount = Account::where('code', '1140-' . $tenantId)->first();
        $salesRevenueAccount = Account::where('code', '4100-' . $tenantId)->first();
        $accountsReceivableAccount = Account::where('code', '1130-' . $tenantId)->first();
        $costOfGoodsSoldAccount = Account::where('code', '5200-' . $tenantId)->first();


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