<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Create a new journal entry with the given transactions.
     *
     * @param string $description
     * @param \Carbon\Carbon $date
     * @param array $transactions Array of transactions, each with 'account_name', 'type' ('debit' or 'credit'), and 'amount'.
     * @param Model $sourceDocument The source of the transaction (e.g., Sales or Purchase model).
     * @return JournalEntry
     * @throws Exception
     */
    public function createJournalEntry(string $description, \Carbon\Carbon $date, array $transactions, Model $sourceDocument): JournalEntry
    {
        $this->validateTransactions($transactions);

        return DB::transaction(function () use ($description, $date, $transactions, $sourceDocument) {
            $journalEntry = JournalEntry::create([
                'description' => $description,
                'date' => $date,
                'sourceable_id' => $sourceDocument->id,
                'sourceable_type' => get_class($sourceDocument),
            ]);

            foreach ($transactions as $tx) {
                $tenantId = app('currentTenant')->id;
                $account = Account::where('name', $tx['account_name'])->where('tenant_id', $tenantId)->first();
                if (!$account) {
                    throw new Exception("Account '{$tx['account_name']}' not found for the current tenant.");
                }

                $journalEntry->transactions()->create([
                    'account_id' => $account->id,
                    'type' => $tx['type'],
                    'amount' => $tx['amount'],
                ]);
            }

            return $journalEntry;
        });
    }

    /**
     * Validate that the total debits equal the total credits.
     *
     * @param array $transactions
     * @throws Exception
     */
    private function validateTransactions(array $transactions): void
    {
        $debits = 0;
        $credits = 0;

        foreach ($transactions as $tx) {
            if (!isset($tx['account_name'], $tx['type'], $tx['amount'])) {
                throw new Exception('Each transaction must have an account_name, type, and amount.');
            }

            if ($tx['type'] === 'debit') {
                $debits += $tx['amount'];
            } elseif ($tx['type'] === 'credit') {
                $credits += $tx['amount'];
            }
        }

        if (round($debits, 2) !== round($credits, 2)) {
            throw new Exception("Debits ({$debits}) do not equal credits ({$credits}).");
        }
    }
}
