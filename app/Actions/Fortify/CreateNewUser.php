<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Registered;
use App\Models\CurrencySetting;
use App\Models\Tax;
use App\Models\Account;
use Database\Seeders\AccountSeeder;
use Illuminate\Support\Facades\Log;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'shopname' => ['required', 'string', 'max:255', Rule::unique('tenants', 'name')],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $tenant = Tenant::create([
            'name' => $input['shopname'],
            'domain' => Str::slug($input['shopname']) . '.' . config('app.domain'),
        ]);

        $tenant = Tenant::findOrFail($tenant->id);

        // Make the tenant current
        $tenant->makeCurrent();

        // Now create user in tenant context
        $user = new User([
            'name' => $input['name'],
            'email' => $input['email'],
            'shopname' => $input['shopname'] ?? null,
            'address' => $input['address'] ?? null,
            'avatar' => $input['avatar'] ?? null,
            'timezone' => $input['timezone'] ?? null,
        ]);
        $user->password = $input['password'];
        $user->save();

        // Assign role inside tenant context

        $superuserRole = Role::firstOrCreate(['name' => 'superuser']);
        $user->assignRole($superuserRole);


        // Create default CurrencySetting for the new tenant

        try {
            CurrencySetting::create([
                'tenant_id' => $tenant->id,
                'currency_symbol' => 'Rp',
                'decimal_separator' => ',',
                'thousand_separator' => '.',
                'decimal_places' => 0,
                'position' => 'prefix',
                'currency_code' => 'IDR',
                'locale' => 'id-ID',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error creating CurrencySetting: ' . $e->getMessage());
            throw $e; // Re-throw to see the original error in test output
        }


        // Create default Tax for the new tenant

        try {
            Tax::create([
                'tenant_id' => $tenant->id,
                'name' => 'Default Tax',
                'rate' => 0.00,
                'is_active' => true,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error creating Tax: ' . $e->getMessage());
            throw $e; // Re-throw to see the original error in test output
        }

        // Seed accounts for the new tenant
        $accountSeeder = new AccountSeeder();
        $accountSeeder->run();

        // Set default accounting settings
        $tenantId = $tenant->id;
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


        event(new Registered($user));

        return $user;
    }
}