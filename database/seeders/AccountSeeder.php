<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\OpeningBalance;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;
        $tenantName = app('currentTenant')->name;

        if (isset($this->command)) {
            $this->command->info("Seeding Chart of Accounts for tenant: {$tenantName} (ID: {$tenantId})");
        }

        $locale = App::getLocale() ?: 'en';

        $accountsData = [
            // Group 1: Assets
            [
                'name' => 'accounting.accounts.assets.name', 'code' => '1000-'.$tenantId, 'type' => 'asset', 'level' => 0,
                'children' => [
                    ['name' => 'accounting.accounts.current_assets.name', 'code' => '1100-'.$tenantId, 'type' => 'asset', 'level' => 1,
                        'children' => [
                            ['name' => 'accounting.accounts.cash.name', 'code' => '1110-'.$tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.cash.description'],
                            ['name' => 'accounting.accounts.bank.name', 'code' => '1120-'.$tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.bank.description'],
                            ['name' => 'accounting.accounts.accounts_receivable.name', 'code' => '1130-'.$tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.accounts_receivable.description'],
                            ['name' => 'accounting.accounts.allowance_for_doubtful_accounts.name', 'code' => '1135-'.$tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.allowance_for_doubtful_accounts.description'], // Contra-asset
                            ['name' => 'accounting.accounts.inventory.name', 'code' => '1140-'.$tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.inventory.description'],
                            ['name' => 'accounting.accounts.prepaid_expenses.name', 'code' => '1150-'.$tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.prepaid_expenses.description'],
                            ['name' => 'accounting.accounts.input_vat.name', 'code' => '1160-'.$tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.input_vat.description'],
                            ['name' => 'accounting.accounts.pph_receivable.name', 'code' => '1170-'.$tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.pph_receivable.description'],
                        ],
                    ],
                    ['name' => 'accounting.accounts.fixed_assets.name', 'code' => '1200-'.$tenantId, 'type' => 'asset', 'level' => 1,
                        'children' => [
                            ['name' => 'accounting.accounts.land.name', 'code' => '1210-'.$tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.land.description'],
                            ['name' => 'accounting.accounts.buildings.name', 'code' => '1220-'.$tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.buildings.description'],
                            ['name' => 'accounting.accounts.accumulated_depreciation_buildings.name', 'code' => '1225-'.$tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.accumulated_depreciation_buildings.description'], // Contra-asset
                            ['name' => 'accounting.accounts.equipment.name', 'code' => '1230-'.$tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.equipment.description'],
                            ['name' => 'accounting.accounts.accumulated_depreciation_equipment.name', 'code' => '1235-'.$tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.accumulated_depreciation_equipment.description'], // Contra-asset
                        ],
                    ],
                ],
            ],
            // Group 2: Liabilities
            [
                'name' => 'accounting.accounts.liabilities.name', 'code' => '2000-'.$tenantId, 'type' => 'liability', 'level' => 0,
                'children' => [
                    ['name' => 'accounting.accounts.current_liabilities.name', 'code' => '2100-'.$tenantId, 'type' => 'liability', 'level' => 1,
                        'children' => [
                            ['name' => 'accounting.accounts.accounts_payable.name', 'code' => '2110-'.$tenantId, 'type' => 'liability', 'level' => 2, 'description' => 'accounting.accounts.accounts_payable.description'],
                            ['name' => 'accounting.accounts.accrued_expenses.name', 'code' => '2120-'.$tenantId, 'type' => 'liability', 'level' => 2, 'description' => 'accounting.accounts.accrued_expenses.description'],
                            ['name' => 'accounting.accounts.output_vat.name', 'code' => '2130-'.$tenantId, 'type' => 'liability', 'level' => 2, 'description' => 'accounting.accounts.output_vat.description'],
                            ['name' => 'accounting.accounts.salary_payable.name', 'code' => '2140-'.$tenantId, 'type' => 'liability', 'level' => 2, 'description' => 'accounting.accounts.salary_payable.description'],
                            ['name' => 'accounting.accounts.pph_payable.name', 'code' => '2150-'.$tenantId, 'type' => 'liability', 'level' => 2, 'description' => 'accounting.accounts.pph_payable.description'],
                        ],
                    ],
                    ['name' => 'accounting.accounts.long_term_liabilities.name', 'code' => '2200-'.$tenantId, 'type' => 'liability', 'level' => 1,
                        'children' => [
                            ['name' => 'accounting.accounts.bank_loans.name', 'code' => '2210-'.$tenantId, 'type' => 'liability', 'level' => 2, 'description' => 'accounting.accounts.bank_loans.description'],
                        ],
                    ],
                ],
            ],
            // Group 3: Equity
            [
                'name' => 'accounting.accounts.equity.name', 'code' => '3000-'.$tenantId, 'type' => 'equity', 'level' => 0,
                'children' => [
                    ['name' => 'accounting.accounts.opening_balance_equity.name', 'code' => '3010-'.$tenantId, 'type' => 'equity', 'level' => 1, 'description' => 'accounting.accounts.opening_balance_equity.description'],
                    ['name' => 'accounting.accounts.owners_equity.name', 'code' => '3100-'.$tenantId, 'type' => 'equity', 'level' => 1, 'description' => 'accounting.accounts.owners_equity.description'],
                    ['name' => 'accounting.accounts.retained_earnings.name', 'code' => '3200-'.$tenantId, 'type' => 'equity', 'level' => 1, 'description' => 'accounting.accounts.retained_earnings.description'],
                ],
            ],
            // Group 4: Revenue
            [
                'name' => 'accounting.accounts.revenue.name', 'code' => '4000-'.$tenantId, 'type' => 'revenue', 'level' => 0,
                'children' => [
                    ['name' => 'accounting.accounts.sales_revenue.name', 'code' => '4100-'.$tenantId, 'type' => 'revenue', 'level' => 1, 'description' => 'accounting.accounts.sales_revenue.description'],
                    ['name' => 'accounting.accounts.sales_returns.name', 'code' => '4110-'.$tenantId, 'type' => 'revenue', 'level' => 1, 'description' => 'accounting.accounts.sales_returns.description'], // Contra-revenue
                    ['name' => 'accounting.accounts.sales_discounts.name', 'code' => '4120-'.$tenantId, 'type' => 'revenue', 'level' => 1, 'description' => 'accounting.accounts.sales_discounts.description'], // Contra-revenue
                ],
            ],
            // Group 5: Cost of Goods Sold (HPP)
            [
                'name' => 'accounting.accounts.cost_of_goods_sold_group.name', 'code' => '5000-'.$tenantId, 'type' => 'expense', 'level' => 0,
                'children' => [
                    ['name' => 'accounting.accounts.purchases.name', 'code' => '5100-'.$tenantId, 'type' => 'expense', 'level' => 1, 'description' => 'accounting.accounts.purchases.description'],
                    ['name' => 'accounting.accounts.freight_in.name', 'code' => '5110-'.$tenantId, 'type' => 'expense', 'level' => 1, 'description' => 'accounting.accounts.freight_in.description'],
                    ['name' => 'accounting.accounts.purchase_returns.name', 'code' => '5120-'.$tenantId, 'type' => 'expense', 'level' => 1, 'description' => 'accounting.accounts.purchase_returns.description'], // Contra-expense
                    ['name' => 'accounting.accounts.purchase_discounts.name', 'code' => '5130-'.$tenantId, 'type' => 'expense', 'level' => 1, 'description' => 'accounting.accounts.purchase_discounts.description'], // Contra-expense
                    ['name' => 'accounting.accounts.cost_of_goods_sold.name', 'code' => '5200-'.$tenantId, 'type' => 'expense', 'level' => 1, 'description' => 'accounting.accounts.cost_of_goods_sold.description'],
                ],
            ],
            // Group 6: Expenses
            [
                'name' => 'accounting.accounts.expenses.name', 'code' => '6000-'.$tenantId, 'type' => 'expense', 'level' => 0,
                'children' => [
                    ['name' => 'accounting.accounts.selling_expenses.name', 'code' => '6100-'.$tenantId, 'type' => 'expense', 'level' => 1,
                        'children' => [
                            ['name' => 'accounting.accounts.salaries_selling.name', 'code' => '6110-'.$tenantId, 'type' => 'expense', 'level' => 2, 'description' => 'accounting.accounts.salaries_selling.description'],
                            ['name' => 'accounting.accounts.advertising_expenses.name', 'code' => '6120-'.$tenantId, 'type' => 'expense', 'level' => 2, 'description' => 'accounting.accounts.advertising_expenses.description'],
                        ],
                    ],
                    ['name' => 'accounting.accounts.administrative_and_general_expenses.name', 'code' => '6200-'.$tenantId, 'type' => 'expense', 'level' => 1,
                        'children' => [
                            ['name' => 'accounting.accounts.salaries_admin.name', 'code' => '6210-'.$tenantId, 'type' => 'expense', 'level' => 2, 'description' => 'accounting.accounts.salaries_admin.description'],
                            ['name' => 'accounting.accounts.rent_expense.name', 'code' => '6220-'.$tenantId, 'type' => 'expense', 'level' => 2, 'description' => 'accounting.accounts.rent_expense.description'],
                            ['name' => 'accounting.accounts.utility_expenses.name', 'code' => '6230-'.$tenantId, 'type' => 'expense', 'level' => 2, 'description' => 'accounting.accounts.utility_expenses.description'],
                            ['name' => 'accounting.accounts.depreciation_expense.name', 'code' => '6240-'.$tenantId, 'type' => 'expense', 'level' => 2, 'description' => 'accounting.accounts.depreciation_expense.description'],
                        ],
                    ],
                    ['name' => 'accounting.accounts.salary_expense.name', 'code' => '6300-'.$tenantId, 'type' => 'expense', 'level' => 1, 'description' => 'accounting.accounts.salary_expense.description'],
                ],
            ],
        ];

        $createdCount = $this->seedAccounts($accountsData, $tenantId);

        if (isset($this->command)) {
            $this->command->info('Chart of Accounts seeded successfully!');
            $this->command->info("Total accounts created/updated: {$createdCount}");
        }

        // Show summary by type
        $accountsByType = Account::where('tenant_id', $tenantId)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        if (isset($this->command)) {
            $this->command->newLine();
            $this->command->info('Account Summary by Type:');
            foreach ($accountsByType as $type => $count) {
                $this->command->info('  - '.ucfirst($type).": {$count} accounts");
            }
        }

        // Auto-configure accounting integration settings
        $tenant = app('currentTenant');
        $codePrefix = $tenantId;
        $accountSettings = [
            'sales_revenue_account_id' => Account::where('tenant_id', $tenantId)->where('code', '4100-'.$codePrefix)->value('id'),
            'accounts_receivable_account_id' => Account::where('tenant_id', $tenantId)->where('code', '1130-'.$codePrefix)->value('id'),
            'cost_of_goods_sold_account_id' => Account::where('tenant_id', $tenantId)->where('code', '5200-'.$codePrefix)->value('id'),
            'inventory_account_id' => Account::where('tenant_id', $tenantId)->where('code', '1140-'.$codePrefix)->value('id'),
            'accounts_payable_account_id' => Account::where('tenant_id', $tenantId)->where('code', '2110-'.$codePrefix)->value('id'),
            'cash_account_id' => Account::where('tenant_id', $tenantId)->where('code', '1110-'.$codePrefix)->value('id'),
            'ppn_keluaran_account_id' => Account::where('tenant_id', $tenantId)->where('code', '2130-'.$codePrefix)->value('id'),
            'ppn_masukan_account_id' => Account::where('tenant_id', $tenantId)->where('code', '1160-'.$codePrefix)->value('id'),
            'pph_receivable_account_id' => Account::where('tenant_id', $tenantId)->where('code', '1170-'.$codePrefix)->value('id'),
            'pph_payable_account_id' => Account::where('tenant_id', $tenantId)->where('code', '2150-'.$codePrefix)->value('id'),
        ];

        $tenant->accounting_settings = $accountSettings;
        $tenant->save();

        if (isset($this->command)) {
            $this->command->newLine();
            $this->command->info('Accounting integration settings configured for tenant.');
        }

        // Seed opening balances for a healthy company
        $this->seedOpeningBalances($tenantId, $codePrefix);
    }

    private function seedOpeningBalances(int $tenantId, string $codePrefix): void
    {
        $balanceDate = now()->startOfMonth()->subMonth()->toDateString();

        $balances = [
            '1110-'.$codePrefix => ['debit' => 50000,  'credit' => 0,      'name' => 'Cash'],
            '1120-'.$codePrefix => ['debit' => 200000, 'credit' => 0,      'name' => 'Bank'],
            '1130-'.$codePrefix => ['debit' => 75000,  'credit' => 0,      'name' => 'Accounts Receivable'],
            '1135-'.$codePrefix => ['debit' => 0,      'credit' => 2500,   'name' => 'Allowance for Doubtful Accounts'],
            '1140-'.$codePrefix => ['debit' => 100000, 'credit' => 0,      'name' => 'Inventory'],
            '1150-'.$codePrefix => ['debit' => 5000,   'credit' => 0,      'name' => 'Prepaid Expenses'],
            '1210-'.$codePrefix => ['debit' => 500000, 'credit' => 0,      'name' => 'Land'],
            '1220-'.$codePrefix => ['debit' => 300000, 'credit' => 0,      'name' => 'Buildings'],
            '1225-'.$codePrefix => ['debit' => 0,      'credit' => 50000,  'name' => 'Accum. Deprec. Buildings'],
            '1230-'.$codePrefix => ['debit' => 100000, 'credit' => 0,      'name' => 'Equipment'],
            '1235-'.$codePrefix => ['debit' => 0,      'credit' => 20000,  'name' => 'Accum. Deprec. Equipment'],
            '2110-'.$codePrefix => ['debit' => 0,      'credit' => 45000,  'name' => 'Accounts Payable'],
            '2120-'.$codePrefix => ['debit' => 0,      'credit' => 10000,  'name' => 'Accrued Expenses'],
            '2140-'.$codePrefix => ['debit' => 0,      'credit' => 15000,  'name' => 'Salary Payable'],
            '2210-'.$codePrefix => ['debit' => 0,      'credit' => 300000, 'name' => 'Bank Loans'],
            '3100-'.$codePrefix => ['debit' => 0,      'credit' => 887500, 'name' => 'Owner\'s Equity'],
        ];

        $totalDebit = collect($balances)->sum('debit');
        $totalCredit = collect($balances)->sum('credit');

        if (isset($this->command)) {
            $this->command->newLine();
            $this->command->info('Seeding opening balances for a healthy company...');
            $this->command->info('Total Debits: $'.number_format($totalDebit, 2));
            $this->command->info('Total Credits: $'.number_format($totalCredit, 2));
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            if (isset($this->command)) {
                $this->command->error('Opening balances do not balance! Debits: $'.$totalDebit.' Credits: $'.$totalCredit);
            }

            return;
        }

        $transactions = [];
        foreach ($balances as $code => $data) {
            $account = Account::where('tenant_id', $tenantId)->where('code', $code)->first();
            if (! $account) {
                if (isset($this->command)) {
                    $this->command->warn("Account {$code} not found, skipping opening balance.");
                }

                continue;
            }

            $type = $data['debit'] > 0 ? 'debit' : 'credit';
            $amount = $data['debit'] > 0 ? $data['debit'] : $data['credit'];

            OpeningBalance::updateOrCreate(
                ['account_id' => $account->id, 'balance_date' => $balanceDate, 'tenant_id' => $tenantId],
                [
                    'debit_amount' => $data['debit'],
                    'credit_amount' => $data['credit'],
                    'balance' => $data['debit'] - $data['credit'],
                    'notes' => 'Opening balance seeded for '.$data['name'],
                ]
            );

            $transactions[] = [
                'account_code' => $account->code,
                'type' => $type,
                'amount' => $amount,
            ];

            if (isset($this->command)) {
                $this->command->info("  {$data['name']}: \${$data['debit']} / \${$data['credit']}");
            }
        }

        // Create Journal Entry with Transactions so Trial Balance reports can see opening balances
        if (! empty($transactions)) {
            $accountingService = app(AccountingService::class);
            $accountingService->createOpeningBalanceEntry(
                Carbon::parse($balanceDate),
                $transactions
            );
            if (isset($this->command)) {
                $this->command->info('Opening balance journal entry created with '.count($transactions).' transactions.');
            }
        }

        if (isset($this->command)) {
            $this->command->info('Opening balances seeded successfully!');
        }
    }

    private function seedAccounts(array $accounts, int $tenantId, ?int $parentId = null): int
    {
        $count = 0;

        foreach ($accounts as $accountData) {
            $children = $accountData['children'] ?? [];
            unset($accountData['children']);

            $nameKey = $accountData['name'];
            $accountData['name'] = trans($nameKey);
            if (isset($accountData['description'])) {
                $descKey = $accountData['description'];
                $accountData['description'] = trans($descKey);
            }

            // Allow manual entries for all account types
            // Manual journal entries can be used for adjustments, corrections, accruals, etc.
            $accountData['allow_manual_entry'] = true;

            $account = Account::updateOrCreate(
                [
                    'code' => $accountData['code'],
                    'tenant_id' => $tenantId,
                ],
                $accountData + ['parent_id' => $parentId]
            );

            $count++;

            if (! empty($children)) {
                $count += $this->seedAccounts($children, $tenantId, $account->id);
            }
        }

        return $count;
    }
}
