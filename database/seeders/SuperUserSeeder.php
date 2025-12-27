<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Account;
use Spatie\Permission\Models\Role;

class SuperUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::updateOrCreate(
            ['email' => 'superuser@example.com'],
            [
                'name' => 'Super User',
                'password' => bcrypt('password'),
            ]
        );

        $role = Role::where('name', 'superuser')->first();

        if ($role) {
            $user->assignRole($role);
        }
        
        $cashAccountId = Account::where('code', '1110')->first()->id;
        $accountsReceivableAccountId = Account::where('code', '1130')->first()->id;
        $inventoryAccountId = Account::where('code', '1140')->first()->id;
        $accountsPayableAccountId = Account::where('code', '2110')->first()->id;
        $salesRevenueAccountId = Account::where('code', '4100')->first()->id;
        $costOfGoodsSoldAccountId = Account::where('code', '5200')->first()->id;

        $user->update([
            'accounting_settings' => [
                'cash_account_id' => $cashAccountId,
                'accounts_receivable_account_id' => $accountsReceivableAccountId,
                'inventory_account_id' => $inventoryAccountId,
                'accounts_payable_account_id' => $accountsPayableAccountId,
                'sales_revenue_account_id' => $salesRevenueAccountId,
                'cost_of_goods_sold_account_id' => $costOfGoodsSoldAccountId,
            ]
        ]);
    }
}