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

        $this->command->info("Seeding Chart of Accounts for tenant: {$tenantName} (ID: {$tenantId})");
        
        $accountsData = [
            // Group 1: Assets
            [
                'name' => 'accounting.accounts.assets.name', 'code' => '1000-' . $tenantId, 'type' => 'asset', 'level' => 0,
                'children' => [
                    ['name' => 'accounting.accounts.current_assets.name', 'code' => '1100-' . $tenantId, 'type' => 'asset', 'level' => 1,
                        'children' => [
                            ['name' => 'accounting.accounts.cash.name', 'code' => '1110-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.cash.description'],
                            ['name' => 'accounting.accounts.bank.name', 'code' => '1120-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.bank.description'],
                            ['name' => 'accounting.accounts.accounts_receivable.name', 'code' => '1130-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.accounts_receivable.description'],
                            ['name' => 'accounting.accounts.allowance_for_doubtful_accounts.name', 'code' => '1135-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.allowance_for_doubtful_accounts.description'], // Contra-asset
                            ['name' => 'accounting.accounts.inventory.name', 'code' => '1140-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.inventory.description'],
                            ['name' => 'accounting.accounts.prepaid_expenses.name', 'code' => '1150-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.prepaid_expenses.description'],
                            ['name' => 'accounting.accounts.input_vat.name', 'code' => '1160-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.input_vat.description'],
                        ]
                    ],
                    ['name' => 'accounting.accounts.fixed_assets.name', 'code' => '1200-' . $tenantId, 'type' => 'asset', 'level' => 1,
                        'children' => [
                            ['name' => 'accounting.accounts.land.name', 'code' => '1210-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.land.description'],
                            ['name' => 'accounting.accounts.buildings.name', 'code' => '1220-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.buildings.description'],
                            ['name' => 'accounting.accounts.accumulated_depreciation_buildings.name', 'code' => '1225-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.accumulated_depreciation_buildings.description'], // Contra-asset
                            ['name' => 'accounting.accounts.equipment.name', 'code' => '1230-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.equipment.description'],
                            ['name' => 'accounting.accounts.accumulated_depreciation_equipment.name', 'code' => '1235-' . $tenantId, 'type' => 'asset', 'level' => 2, 'description' => 'accounting.accounts.accumulated_depreciation_equipment.description'], // Contra-asset
                        ]
                    ],
                ]
            ],
            // Group 2: Liabilities
            [
                'name' => 'accounting.accounts.liabilities.name', 'code' => '2000-' . $tenantId, 'type' => 'liability', 'level' => 0,
                'children' => [
                    ['name' => 'accounting.accounts.current_liabilities.name', 'code' => '2100-' . $tenantId, 'type' => 'liability', 'level' => 1,
                        'children' => [
                            ['name' => 'accounting.accounts.accounts_payable.name', 'code' => '2110-' . $tenantId, 'type' => 'liability', 'level' => 2, 'description' => 'accounting.accounts.accounts_payable.description'],
                            ['name' => 'accounting.accounts.accrued_expenses.name', 'code' => '2120-' . $tenantId, 'type' => 'liability', 'level' => 2, 'description' => 'accounting.accounts.accrued_expenses.description'],
                            ['name' => 'accounting.accounts.output_vat.name', 'code' => '2130-' . $tenantId, 'type' => 'liability', 'level' => 2, 'description' => 'accounting.accounts.output_vat.description'],
                        ]
                    ],
                    ['name' => 'accounting.accounts.long_term_liabilities.name', 'code' => '2200-' . $tenantId, 'type' => 'liability', 'level' => 1,
                        'children' => [
                            ['name' => 'accounting.accounts.bank_loans.name', 'code' => '2210-' . $tenantId, 'type' => 'liability', 'level' => 2, 'description' => 'accounting.accounts.bank_loans.description'],
                        ]
                    ],
                ]
            ],
            // Group 3: Equity
            [
                'name' => 'accounting.accounts.equity.name', 'code' => '3000-' . $tenantId, 'type' => 'equity', 'level' => 0,
                'children' => [
                    ['name' => 'accounting.accounts.owners_equity.name', 'code' => '3100-' . $tenantId, 'type' => 'equity', 'level' => 1, 'description' => 'accounting.accounts.owners_equity.description'],
                    ['name' => 'accounting.accounts.retained_earnings.name', 'code' => '3200-' . $tenantId, 'type' => 'equity', 'level' => 1, 'description' => 'accounting.accounts.retained_earnings.description'],
                ]
            ],
            // Group 4: Revenue
            [
                'name' => 'accounting.accounts.revenue.name', 'code' => '4000-' . $tenantId, 'type' => 'revenue', 'level' => 0,
                'children' => [
                    ['name' => 'accounting.accounts.sales_revenue.name', 'code' => '4100-' . $tenantId, 'type' => 'revenue', 'level' => 1, 'description' => 'accounting.accounts.sales_revenue.description'],
                    ['name' => 'accounting.accounts.sales_returns.name', 'code' => '4110-' . $tenantId, 'type' => 'revenue', 'level' => 1, 'description' => 'accounting.accounts.sales_returns.description'], // Contra-revenue
                    ['name' => 'accounting.accounts.sales_discounts.name', 'code' => '4120-' . $tenantId, 'type' => 'revenue', 'level' => 1, 'description' => 'accounting.accounts.sales_discounts.description'], // Contra-revenue
                ]
            ],
            // Group 5: Cost of Goods Sold (HPP)
            [
                'name' => 'accounting.accounts.cost_of_goods_sold_group.name', 'code' => '5000-' . $tenantId, 'type' => 'expense', 'level' => 0,
                'children' => [
                    ['name' => 'accounting.accounts.purchases.name', 'code' => '5100-' . $tenantId, 'type' => 'expense', 'level' => 1, 'description' => 'accounting.accounts.purchases.description'],
                    ['name' => 'accounting.accounts.freight_in.name', 'code' => '5110-' . $tenantId, 'type' => 'expense', 'level' => 1, 'description' => 'accounting.accounts.freight_in.description'],
                    ['name' => 'accounting.accounts.purchase_returns.name', 'code' => '5120-' . $tenantId, 'type' => 'expense', 'level' => 1, 'description' => 'accounting.accounts.purchase_returns.description'], // Contra-expense
                    ['name' => 'accounting.accounts.purchase_discounts.name', 'code' => '5130-' . $tenantId, 'type' => 'expense', 'level' => 1, 'description' => 'accounting.accounts.purchase_discounts.description'], // Contra-expense
                    ['name' => 'accounting.accounts.cost_of_goods_sold.name', 'code' => '5200-' . $tenantId, 'type' => 'expense', 'level' => 1, 'description' => 'accounting.accounts.cost_of_goods_sold.description'],
                ]
            ],
            // Group 6: Expenses
            [
                'name' => 'accounting.accounts.expenses.name', 'code' => '6000-' . $tenantId, 'type' => 'expense', 'level' => 0,
                'children' => [
                    ['name' => 'accounting.accounts.selling_expenses.name', 'code' => '6100-' . $tenantId, 'type' => 'expense', 'level' => 1,
                        'children' => [
                            ['name' => 'accounting.accounts.salaries_selling.name', 'code' => '6110-' . $tenantId, 'type' => 'expense', 'level' => 2, 'description' => 'accounting.accounts.salaries_selling.description'],
                            ['name' => 'accounting.accounts.advertising_expenses.name', 'code' => '6120-' . $tenantId, 'type' => 'expense', 'level' => 2, 'description' => 'accounting.accounts.advertising_expenses.description'],
                        ]
                    ],
                    ['name' => 'accounting.accounts.administrative_and_general_expenses.name', 'code' => '6200-' . $tenantId, 'type' => 'expense', 'level' => 1,
                        'children' => [
                            ['name' => 'accounting.accounts.salaries_admin.name', 'code' => '6210-' . $tenantId, 'type' => 'expense', 'level' => 2, 'description' => 'accounting.accounts.salaries_admin.description'],
                            ['name' => 'accounting.accounts.rent_expense.name', 'code' => '6220-' . $tenantId, 'type' => 'expense', 'level' => 2, 'description' => 'accounting.accounts.rent_expense.description'],
                            ['name' => 'accounting.accounts.utility_expenses.name', 'code' => '6230-' . $tenantId, 'type' => 'expense', 'level' => 2, 'description' => 'accounting.accounts.utility_expenses.description'],
                            ['name' => 'accounting.accounts.depreciation_expense.name', 'code' => '6240-' . $tenantId, 'type' => 'expense', 'level' => 2, 'description' => 'accounting.accounts.depreciation_expense.description'],
                        ]
                    ],
                ]
            ],
        ];

        $createdCount = $this->seedAccounts($accountsData, $tenantId);
        
        $this->command->info("Chart of Accounts seeded successfully!");
        $this->command->info("Total accounts created/updated: {$createdCount}");
        
        // Show summary by type
        $accountsByType = Account::where('tenant_id', $tenantId)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');
        
        $this->command->newLine();
        $this->command->info("Account Summary by Type:");
        foreach ($accountsByType as $type => $count) {
            $this->command->info("  - " . ucfirst($type) . ": {$count} accounts");
        }
    }

    private function seedAccounts(array $accounts, int $tenantId, ?int $parentId = null): int
    {
        $count = 0;
        
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
            
            $count++;

            if (!empty($children)) {
                $count += $this->seedAccounts($children, $tenantId, $account->id);
            }
        }
        
        return $count;
    }
}