<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Account;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
        public function run(): void
        {
            $tenantId = app('currentTenant')->id;
        $tenantName = app('currentTenant')->name;

        $accountsData = [
            // Group 1: Assets
            [
                'name' => 'accounting.accounts.assets.name - ' . $tenantName, 'code' => '1000-' . $tenantId, 'type' => 'asset', 'level' => 0,
                'children' => [
                    ['name' => 'accounting.accounts.current_assets.name - ' . $tenantName, 'code' => '1100-' . $tenantId, 'type' => 'asset', 'level' => 1,
                        'children' => [
                            ['name' => 'accounting.accounts.cash.name - ' . $tenantName, 'code' => '1110-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.cash.description'],
                            ['name' => 'accounting.accounts.bank.name - ' . $tenantName, 'code' => '1120-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.bank.description'],
                            ['name' => 'accounting.accounts.accounts_receivable.name - ' . $tenantName, 'code' => '1130-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.accounts_receivable.description'],
                            ['name' => 'accounting.accounts.allowance_for_doubtful_accounts.name - ' . $tenantName, 'code' => '1135-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.allowance_for_doubtful_accounts.description'], // Contra-asset
                            ['name' => 'accounting.accounts.inventory.name - ' . $tenantName, 'code' => '1140-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.inventory.description'],
                            ['name' => 'accounting.accounts.prepaid_expenses.name - ' . $tenantName, 'code' => '1150-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.prepaid_expenses.description'],
                            ['name' => 'accounting.accounts.input_vat.name - ' . $tenantName, 'code' => '1160-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.input_vat.description'],
                        ]
                    ],
                    ['name' => 'accounting.accounts.fixed_assets.name - ' . $tenantName, 'code' => '1200-' . $tenantId, 'type' => 'asset', 'level' => 1,
                        'children' => [
                            ['name' => 'accounting.accounts.land.name - ' . $tenantName, 'code' => '1210-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.land.description'],
                            ['name' => 'accounting.accounts.buildings.name - ' . $tenantName, 'code' => '1220-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.buildings.description'],
                            ['name' => 'accounting.accounts.accumulated_depreciation_buildings.name - ' . $tenantName, 'code' => '1225-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.accumulated_depreciation_buildings.description'], // Contra-asset
                            ['name' => 'accounting.accounts.equipment.name - ' . $tenantName, 'code' => '1230-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.equipment.description'],
                            ['name' => 'accounting.accounts.accumulated_depreciation_equipment.name - ' . $tenantName, 'code' => '1235-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.accumulated_depreciation_equipment.description'], // Contra-asset
                        ]
                    ],
                ]
            ],
            // Group 2: Liabilities
            [
                'name' => 'accounting.accounts.liabilities.name - ' . $tenantName, 'code' => '2000-' . $tenantId, 'type' => 'liability', 'level' => 0,
                'children' => [
                    ['name' => 'accounting.accounts.current_liabilities.name - ' . $tenantName, 'code' => '2100-' . $tenantId, 'type' => 'liability', 'level' => 1,
                        'children' => [
                            ['name' => 'accounting.accounts.accounts_payable.name - ' . $tenantName, 'code' => '2110-' . $tenantId, 'type' => 'liability', 'level' => 2, 'description' => 'accounting.accounts.accounts_payable.description'],
                            ['name' => 'accounting.accounts.accrued_expenses.name - ' . $tenantName, 'code' => '2120-' . $tenantId, 'type' => 'liability', 'level' => 2, 'description' => 'accounting.accounts.accrued_expenses.description'],
                            ['name' => 'accounting.accounts.output_vat.name - ' . $tenantName, 'code' => '2130-' . $tenantId, 'type' => 'liability', 'level' => 2, 'description' => 'accounting.accounts.output_vat.description'],
                        ]
                    ],
                    ['name' => 'accounting.accounts.long_term_liabilities.name - ' . $tenantName, 'code' => '2200-' . $tenantId, 'type' => 'liability', 'level' => 1,
                        'children' => [
                            ['name' => 'accounting.accounts.bank_loans.name - ' . $tenantName, 'code' => '2210-' . $tenantId, 'type' => 'liability', 'level' => 2, 'description' => 'accounting.accounts.bank_loans.description'],
                        ]
                    ],
                ]
            ],
            // Group 3: Equity
            [
                'name' => 'accounting.accounts.equity.name - ' . $tenantName, 'code' => '3000-' . $tenantId, 'type' => 'equity', 'level' => 0,
                'children' => [
                    ['name' => 'accounting.accounts.owners_equity.name - ' . $tenantName, 'code' => '3100-' . $tenantId, 'type' => 'equity', 'level' => 1, 'description' => 'accounting.accounts.owners_equity.description'],
                    ['name' => 'accounting.accounts.retained_earnings.name - ' . $tenantName, 'code' => '3200-' . $tenantId, 'type' => 'equity', 'level' => 1, 'description' => 'accounting.accounts.retained_earnings.description'],
                ]
            ],
            // Group 4: Revenue
            [
                'name' => 'accounting.accounts.revenue.name - ' . $tenantName, 'code' => '4000-' . $tenantId, 'type' => 'revenue', 'level' => 0,
                'children' => [
                    ['name' => 'accounting.accounts.sales_revenue.name - ' . $tenantName, 'code' => '4100-' . $tenantId, 'type' => 'revenue', 'level' => 1, 'description' => 'accounting.accounts.sales_revenue.description'],
                    ['name' => 'accounting.accounts.sales_returns.name - ' . $tenantName, 'code' => '4110-' . $tenantId, 'type' => 'revenue', 'level' => 1, 'description' => 'accounting.accounts.sales_returns.description'], // Contra-revenue
                    ['name' => 'accounting.accounts.sales_discounts.name - ' . $tenantName, 'code' => '4120-' . $tenantId, 'type' => 'revenue', 'level' => 1, 'description' => 'accounting.accounts.sales_discounts.description'], // Contra-revenue
                ]
            ],
            // Group 5: Cost of Goods Sold (HPP)
            [
                'name' => 'accounting.accounts.cost_of_goods_sold_group.name - ' . $tenantName, 'code' => '5000-' . $tenantId, 'type' => 'expense', 'level' => 0,
                'children' => [
                    ['name' => 'accounting.accounts.purchases.name - ' . $tenantName, 'code' => '5100-' . $tenantId, 'type' => 'expense', 'level' => 1, 'description' => 'accounting.accounts.purchases.description'],
                    ['name' => 'accounting.accounts.freight_in.name - ' . $tenantName, 'code' => '5110-' . $tenantId, 'type' => 'expense', 'level' => 1, 'description' => 'accounting.accounts.freight_in.description'],
                    ['name' => 'accounting.accounts.purchase_returns.name - ' . $tenantName, 'code' => '5120-' . $tenantId, 'type' => 'expense', 'level' => 1, 'description' => 'accounting.accounts.purchase_returns.description'], // Contra-expense
                    ['name' => 'accounting.accounts.purchase_discounts.name - ' . $tenantName, 'code' => '5130-' . $tenantId, 'type' => 'expense', 'level' => 1, 'description' => 'accounting.accounts.purchase_discounts.description'], // Contra-expense
                    ['name' => 'accounting.accounts.cost_of_goods_sold.name - ' . $tenantName, 'code' => '5200-' . $tenantId, 'type' => 'expense', 'level' => 1, 'description' => 'accounting.accounts.cost_of_goods_sold.description'],
                ]
            ],
            // Group 6: Expenses
            [
                'name' => 'accounting.accounts.expenses.name - ' . $tenantName, 'code' => '6000-' . $tenantId, 'type' => 'expense', 'level' => 0,
                'children' => [
                    ['name' => 'accounting.accounts.selling_expenses.name - ' . $tenantName, 'code' => '6100-' . $tenantId, 'type' => 'expense', 'level' => 1,
                        'children' => [
                            ['name' => 'accounting.accounts.salaries_selling.name - ' . $tenantName, 'code' => '6110-' . $tenantId, 'type' => 'expense', 'level' => 2, 'description' => 'accounting.accounts.salaries_selling.description'],
                            ['name' => 'accounting.accounts.advertising_expenses.name - ' . $tenantName, 'code' => '6120-' . $tenantId, 'type' => 'expense', 'level' => 2, 'description' => 'accounting.accounts.advertising_expenses.description'],
                        ]
                    ],
                    ['name' => 'accounting.accounts.administrative_and_general_expenses.name - ' . $tenantName, 'code' => '6200-' . $tenantId, 'type' => 'expense', 'level' => 1,
                        'children' => [
                            ['name' => 'accounting.accounts.salaries_admin.name - ' . $tenantName, 'code' => '6210-' . $tenantId, 'type' => 'expense', 'level' => 2, 'description' => 'accounting.accounts.salaries_admin.description'],
                            ['name' => 'accounting.accounts.rent_expense.name - ' . $tenantName, 'code' => '6220-' . $tenantId, 'type' => 'expense', 'level' => 2, 'description' => 'accounting.accounts.rent_expense.description'],
                            ['name' => 'accounting.accounts.utility_expenses.name - ' . $tenantName, 'code' => '6230-' . $tenantId, 'type' => 'expense', 'level' => 2, 'description' => 'accounting.accounts.utility_expenses.description'],
                            ['name' => 'accounting.accounts.depreciation_expense.name - ' . $tenantName, 'code' => '6240-' . $tenantId, 'type' => 'expense', 'level' => 2, 'description' => 'accounting.accounts.depreciation_expense.description'],
                        ]
                    ],
                ]
            ],
        ];

        $this->seedAccounts($accountsData, $tenantId);
    }

    private function seedAccounts(array $accounts, int $tenantId, ?int $parentId = null): void
    {
        foreach ($accounts as $accountData) {
            $children = $accountData['children'] ?? [];
            unset($accountData['children']);

            $account = Account::updateOrCreate(
                [
                    'code' => $accountData['code'],
                    'tenant_id' => $tenantId,
                ],
                $accountData + ['parent_id' => $parentId]
            );

            if (!empty($children)) {
                $this->seedAccounts($children, $tenantId, $account->id);
            }
        }
    }
}