<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Seed the single superuser for personal use.
     */
    public function run(): void
    {
        $tenant = app('currentTenant');
        $tenantId = $tenant->id;

        $email = env('SEED_ADMIN_EMAIL', 'admin@localhost');

        if (User::where('tenant_id', $tenantId)->exists()) {
            $this->command->info('Admin user already exists, skipping.');

            return;
        }

        $user = User::updateOrCreate(
            ['email' => $email, 'tenant_id' => $tenantId],
            [
                'name' => env('SEED_ADMIN_NAME', 'Admin'),
                'shopname' => env('SEED_ADMIN_SHOPNAME', $tenant->name),
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'password')),
                // Standalone install: there is no mailer pointed at anything
                // real, so an unverified seed account would bounce every login
                // to /admin/email/verify with no way out.
                'email_verified_at' => now(),
                'avatar' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $role = Role::where('name', 'superuser')->first();
        if ($role) {
            $user->assignRole($role);
            $this->command->info('Assigned superuser role');
        }

        $cashAccount = Account::where('code', '1110-'.$tenantId)->first();
        $accountsPayableAccount = Account::where('code', '2110-'.$tenantId)->first();
        $inventoryAccount = Account::where('code', '1140-'.$tenantId)->first();
        $salesRevenueAccount = Account::where('code', '4100-'.$tenantId)->first();
        $accountsReceivableAccount = Account::where('code', '1130-'.$tenantId)->first();
        $costOfGoodsSoldAccount = Account::where('code', '5200-'.$tenantId)->first();

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
            $this->command->info('Configured default accounting settings');
        }

        $this->command->info("Admin user created: {$email} (password: '".env('SEED_ADMIN_PASSWORD', 'password')."')");
    }
}
