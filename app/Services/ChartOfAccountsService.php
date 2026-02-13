<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChartOfAccountsService
{
    /**
     * Get accounting settings for the current user.
     *
     * @return array
     */
    public function getAccountingSettings(): array
    {
        $accounts = Account::all()->groupBy('type');
        $user = Auth::user();
        $settings = $user->accounting_settings ?? [];

        return [
            'accounts' => $accounts,
            'settings' => $settings,
        ];
    }

    /**
     * Update accounting settings for the current user.
     *
     * @param array $data
     * @return array
     */
    public function updateAccountingSettings(array $data): array
    {
        $user = Auth::user();
        $user->accounting_settings = $data;
        $user->save();

        return [
            'success' => true,
            'message' => 'Accounting settings updated successfully.',
        ];
    }

    /**
     * Validate accounting settings data.
     *
     * @param array $data
     * @return array
     */
    public function validateSettingsData(array $data): array
    {
        return [
            'sales_revenue_account_id' => 'required|exists:accounts,id',
            'accounts_receivable_account_id' => 'required|exists:accounts,id',
            'cost_of_goods_sold_account_id' => 'required|exists:accounts,id',
            'inventory_account_id' => 'required|exists:accounts,id',
            'accounts_payable_account_id' => 'required|exists:accounts,id',
            'cash_account_id' => 'required|exists:accounts,id',
        ];
    }

    /**
     * Reset chart of accounts to default.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function resetToDefault(): array
    {
        set_time_limit(300);
        Log::debug('resetToDefault method called.');

        try {
            DB::beginTransaction();
            Log::debug('Explicit transaction started. DB_CONNECTION: ' . config('database.default') . ', Transaction Level: ' . DB::transactionLevel());

            try {
                // Temporarily disable foreign key checks
                if (DB::connection()->getDriverName() === 'sqlite') {
                    DB::statement('PRAGMA foreign_keys = OFF;');
                } else {
                    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                }
            } catch (\Exception $e) {
                Log::error('Failed to disable foreign key checks: ' . $e->getMessage());
                throw $e;
            }

            try {
                // Delete all related transactions and journal entries first
                Transaction::query()->delete();
                JournalEntry::query()->delete();
                Account::query()->delete();
                Log::debug('Accounting data truncated.');
            } catch (\Exception $e) {
                Log::error('Failed to delete/truncate accounting data: ' . $e->getMessage());
                throw $e;
            }

            try {
                // Re-enable foreign key checks
                if (DB::connection()->getDriverName() === 'sqlite') {
                    DB::statement('PRAGMA foreign_keys = ON;');
                } else {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                }
            } catch (\Exception $e) {
                Log::error('Failed to re-enable foreign key checks: ' . $e->getMessage());
                throw $e;
            }

            // Call the seeder to restore defaults
            Artisan::call('db:seed', ['--class' => 'AccountSeeder']);
            Log::debug('AccountSeeder executed.');

            DB::commit();

            return [
                'success' => true,
                'message' => 'Chart of Accounts has been reset to default successfully.',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reset COA: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'An error occurred while resetting the Chart of Accounts: ' . $e->getMessage(),
            ];
        }
    }
}
