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
        $accounts = [
            // Assets (1000-1999)
            ['name' => 'Cash', 'code' => '1010', 'type' => 'asset', 'description' => 'Cash on hand and in bank accounts.'],
            ['name' => 'Accounts Receivable', 'code' => '1200', 'type' => 'asset', 'description' => 'Money owed to the company by its customers.'],
            ['name' => 'Inventory', 'code' => '1300', 'type' => 'asset', 'description' => 'Value of goods available for sale.'],

            // Liabilities (2000-2999)
            ['name' => 'Accounts Payable', 'code' => '2000', 'type' => 'liability', 'description' => 'Money owed by the company to its suppliers.'],

            // Equity (3000-3999)
            ['name' => 'Owner\'s Equity', 'code' => '3000', 'type' => 'equity', 'description' => 'The owner\'s stake in the company.'],

            // Revenue (4000-4999)
            ['name' => 'Sales Revenue', 'code' => '4000', 'type' => 'revenue', 'description' => 'Revenue generated from sales of goods or services.'],

            // Expenses (5000-5999)
            ['name' => 'Cost of Goods Sold', 'code' => '5000', 'type' => 'expense', 'description' => 'The direct costs attributable to the production of the goods sold by a company.'],
            ['name' => 'General & Administrative Expenses', 'code' => '5400', 'type' => 'expense', 'description' => 'Operating expenses not directly related to production or sales.'],
        ];

        foreach ($accounts as $account) {
            Account::updateOrCreate(
                ['name' => $account['name']],
                $account
            );
        }
    }
}