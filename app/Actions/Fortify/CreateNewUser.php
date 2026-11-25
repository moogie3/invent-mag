<?php

namespace App\Actions\Fortify;

use App\Models\Account;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Models\Role;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'shopname' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $tenant = Tenant::query()->orderBy('id')->firstOrFail();
            $tenant->makeCurrent();

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

            $superuserRole = Role::firstOrCreate(['name' => 'superuser', 'guard_name' => 'web']);
            $user->assignRole($superuserRole);

            $tenantId = $tenant->id;
            $cashAccount = Account::where('code', '1110-'.$tenantId)->first();
            $accountsPayableAccount = Account::where('code', '2110-'.$tenantId)->first();
            $inventoryAccount = Account::where('code', '1140-'.$tenantId)->first();
            $salesRevenueAccount = Account::where('code', '4100-'.$tenantId)->first();
            $accountsReceivableAccount = Account::where('code', '1130-'.$tenantId)->first();
            $costOfGoodsSoldAccount = Account::where('code', '5200-'.$tenantId)->first();
            $outputVatAccount = Account::where('code', '2130-'.$tenantId)->first();
            $inputVatAccount = Account::where('code', '1160-'.$tenantId)->first();

            if ($cashAccount && $accountsPayableAccount && $inventoryAccount && $salesRevenueAccount && $accountsReceivableAccount && $costOfGoodsSoldAccount) {
                $tenant->accounting_settings = [
                    'cash_account_id' => $cashAccount->id,
                    'accounts_payable_account_id' => $accountsPayableAccount->id,
                    'inventory_account_id' => $inventoryAccount->id,
                    'sales_revenue_account_id' => $salesRevenueAccount->id,
                    'accounts_receivable_account_id' => $accountsReceivableAccount->id,
                    'cost_of_goods_sold_account_id' => $costOfGoodsSoldAccount->id,
                    'ppn_keluaran_account_id' => $outputVatAccount?->id,
                    'ppn_masukan_account_id' => $inputVatAccount?->id,
                    'pph_receivable_account_id' => Account::where('tenant_id', $tenantId)->where('code', '1170-'.$tenantId)->value('id'),
                    'pph_payable_account_id' => Account::where('tenant_id', $tenantId)->where('code', '2150-'.$tenantId)->value('id'),
                ];
                $tenant->save();
            }

            event(new Registered($user));

            return $user;
        });
    }
}
