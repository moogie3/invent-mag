<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;
use App\Models\AccountingAuditLog;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AccountingService
{
    protected $auditService;

    public function __construct(AccountingAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Create a new journal entry with the given transactions.
     */
    public function createJournalEntry(
        string $description,
        Carbon $date,
        array $transactions,
        ?Model $sourceDocument = null,
        array $options = []
    ): JournalEntry {
        $this->validateTransactions($transactions);

        $status = $options['status'] ?? JournalEntry::STATUS_POSTED;
        $notes = $options['notes'] ?? null;
        $entryType = $options['entry_type'] ?? JournalEntry::ENTRY_TYPE_STANDARD;
        $skipAudit = $options['skip_audit'] ?? false;

        return DB::transaction(function () use ($description, $date, $transactions, $sourceDocument, $status, $notes, $entryType, $skipAudit) {
            $journalEntry = JournalEntry::create([
                'description' => $description,
                'date' => $date,
                'sourceable_id' => $sourceDocument?->id,
                'sourceable_type' => $sourceDocument ? get_class($sourceDocument) : null,
                'status' => $status,
                'notes' => $notes,
                'entry_type' => $entryType,
            ]);

            foreach ($transactions as $tx) {
                $tenantId = app('currentTenant')->id;
                $account = $this->findAccount($tx, $tenantId);

                if (!$account) {
                    $identifier = $tx['account_code'] ?? $tx['account_name'] ?? 'Unknown';
                    throw new Exception("Account '{$identifier}' not found for the current tenant.");
                }

                if (!$account->allow_manual_entry && !$sourceDocument) {
                    throw new Exception("Account '{$account->name}' does not allow manual entries.");
                }

                $journalEntry->transactions()->create([
                    'account_id' => $account->id,
                    'type' => $tx['type'],
                    'amount' => $tx['amount'],
                ]);
            }

            if (!$skipAudit && Auth::check()) {
                $this->auditService->logCreate($journalEntry, $transactions);
            }

            return $journalEntry;
        });
    }

    /**
     * Create a manual journal entry (draft by default).
     */
    public function createManualJournalEntry(
        string $description,
        Carbon $date,
        array $transactions,
        ?string $notes = null,
        bool $autoPost = false
    ): JournalEntry {
        return $this->createJournalEntry(
            $description,
            $date,
            $transactions,
            null,
            [
                'status' => $autoPost ? JournalEntry::STATUS_POSTED : JournalEntry::STATUS_DRAFT,
                'notes' => $notes,
                'entry_type' => JournalEntry::ENTRY_TYPE_STANDARD,
            ]
        );
    }

    /**
     * Create an opening balance journal entry.
     */
    public function createOpeningBalanceEntry(Carbon $date, array $transactions): JournalEntry
    {
        return $this->createJournalEntry(
            'Opening Balance Entry',
            $date,
            $transactions,
            null,
            [
                'status' => JournalEntry::STATUS_POSTED,
                'entry_type' => JournalEntry::ENTRY_TYPE_OPENING,
                'skip_audit' => false,
            ]
        );
    }

    /**
     * Post a draft journal entry.
     */
    public function postJournalEntry(JournalEntry $journalEntry): JournalEntry
    {
        if ($journalEntry->status !== JournalEntry::STATUS_DRAFT) {
            throw new Exception("Only draft entries can be posted.");
        }

        if (!$journalEntry->isBalanced()) {
            throw new Exception("Journal entry is not balanced and cannot be posted.");
        }

        $oldValues = $journalEntry->toArray();

        $journalEntry->update([
            'status' => JournalEntry::STATUS_POSTED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        if (Auth::check()) {
            $this->auditService->logAction($journalEntry, AccountingAuditLog::ACTION_POST, $oldValues);
        }

        return $journalEntry;
    }

    /**
     * Void a posted journal entry.
     */
    public function voidJournalEntry(JournalEntry $journalEntry, string $reason): JournalEntry
    {
        if ($journalEntry->status !== JournalEntry::STATUS_POSTED) {
            throw new Exception("Only posted entries can be voided.");
        }

        $oldValues = $journalEntry->toArray();

        $journalEntry->update([
            'status' => JournalEntry::STATUS_VOID,
            'notes' => $journalEntry->notes . "\n\nVoided: {$reason}",
        ]);

        if (Auth::check()) {
            $this->auditService->logAction($journalEntry, AccountingAuditLog::ACTION_VOID, $oldValues, ['reason' => $reason]);
        }

        return $journalEntry;
    }

    /**
     * Reverse a journal entry.
     */
    public function reverseJournalEntry(JournalEntry $journalEntry, ?string $notes = null): JournalEntry
    {
        if (!$journalEntry->canBeReversed()) {
            throw new Exception("This journal entry cannot be reversed.");
        }

        $reversalTransactions = [];
        foreach ($journalEntry->transactions as $tx) {
            $reversalTransactions[] = [
                'account_id' => $tx->account_id,
                'type' => $tx->type === 'debit' ? 'credit' : 'debit',
                'amount' => $tx->amount,
            ];
        }

        $description = "REVERSAL - {$journalEntry->description}";

        $reversalEntry = $this->createJournalEntry(
            $description,
            Carbon::now(),
            array_map(function ($tx, $index) use ($journalEntry) {
                return [
                    'account_id' => $tx['account_id'],
                    'type' => $tx['type'],
                    'amount' => $tx['amount'],
                ];
            }, $reversalTransactions, array_keys($reversalTransactions)),
            null,
            [
                'status' => JournalEntry::STATUS_POSTED,
                'entry_type' => JournalEntry::ENTRY_TYPE_REVERSAL,
                'notes' => $notes,
                'skip_audit' => false,
            ]
        );

        $reversalEntry->update([
            'reversed_entry_id' => $journalEntry->id,
        ]);

        $this->auditService->logCreate($reversalEntry, [], "Reversed entry #{$journalEntry->id}");

        return $reversalEntry;
    }

    /**
     * Unpost a journal entry (return to draft).
     */
    public function unpostJournalEntry(JournalEntry $journalEntry): JournalEntry
    {
        if ($journalEntry->status !== JournalEntry::STATUS_POSTED) {
            throw new Exception("Only posted entries can be unposted.");
        }

        if ($journalEntry->hasDependentEntries()) {
            throw new Exception("This entry has dependent entries and cannot be unposted.");
        }

        $oldValues = $journalEntry->toArray();

        $journalEntry->update([
            'status' => JournalEntry::STATUS_DRAFT,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        if (Auth::check()) {
            $this->auditService->logAction($journalEntry, AccountingAuditLog::ACTION_UNPOST, $oldValues);
        }

        return $journalEntry;
    }

    /**
     * Update a draft journal entry.
     */
    public function updateDraftJournalEntry(JournalEntry $journalEntry, array $data, array $transactions): JournalEntry
    {
        if ($journalEntry->status !== JournalEntry::STATUS_DRAFT) {
            throw new Exception("Only draft entries can be updated.");
        }

        $oldValues = $journalEntry->toArray();
        $oldTransactions = $journalEntry->transactions->toArray();

        $this->validateTransactions($transactions);

        DB::transaction(function () use ($journalEntry, $data, $transactions) {
            $journalEntry->update($data);

            $journalEntry->transactions()->delete();

            $tenantId = app('currentTenant')->id;
            foreach ($transactions as $tx) {
                $account = $this->findAccount($tx, $tenantId);

                $journalEntry->transactions()->create([
                    'account_id' => $account->id,
                    'type' => $tx['type'],
                    'amount' => $tx['amount'],
                ]);
            }
        });

        if (Auth::check()) {
            $this->auditService->logUpdate($journalEntry, $oldValues, $transactions);
        }

        return $journalEntry;
    }

    /**
     * Delete a draft journal entry.
     */
    public function deleteDraftJournalEntry(JournalEntry $journalEntry): bool
    {
        if ($journalEntry->status !== JournalEntry::STATUS_DRAFT) {
            throw new Exception("Only draft entries can be deleted.");
        }

        $oldValues = $journalEntry->toArray();

        $deleted = $journalEntry->delete();

        if ($deleted && Auth::check()) {
            $this->auditService->logAction($journalEntry, AccountingAuditLog::ACTION_DELETE, $oldValues);
        }

        return $deleted;
    }

    /**
     * Find an account by code or name.
     */
    protected function findAccount(array $tx, int $tenantId): ?Account
    {
        if (isset($tx['account_code'])) {
            return Account::where('code', $tx['account_code'])->where('tenant_id', $tenantId)->first();
        }
        if (isset($tx['account_name'])) {
            return Account::where('name', $tx['account_name'])->where('tenant_id', $tenantId)->first();
        }
        if (isset($tx['account_id'])) {
            return Account::where('id', $tx['account_id'])->where('tenant_id', $tenantId)->first();
        }
        return null;
    }

    /**
     * Validate that the total debits equal the total credits.
     */
    protected function validateTransactions(array $transactions): void
    {
        $debits = 0;
        $credits = 0;

        foreach ($transactions as $tx) {
            if ((!isset($tx['account_name']) && !isset($tx['account_code']) && !isset($tx['account_id'])) || !isset($tx['type'], $tx['amount'])) {
                throw new Exception('Each transaction must have an account_id, account_name, or account_code, type, and amount.');
            }

            if (!in_array($tx['type'], ['debit', 'credit'])) {
                throw new Exception("Invalid transaction type: {$tx['type']}. Must be 'debit' or 'credit'.");
            }

            if ($tx['type'] === 'debit') {
                $debits += $tx['amount'];
            } else {
                $credits += $tx['amount'];
            }
        }

        if (count($transactions) < 2) {
            throw new Exception("At least two transactions (debit and credit) are required.");
        }

        if (round($debits, 2) !== round($credits, 2)) {
            throw new Exception("Debits ({$debits}) do not equal credits ({$credits}).");
        }
    }

    /**
     * Get trial balance with validation.
     */
    public function getTrialBalance(?Carbon $endDate = null): array
    {
        $endDate = $endDate ?? Carbon::now();

        $accounts = Account::with(['transactions' => function ($query) use ($endDate) {
            $query->whereHas('journalEntry', function ($q) use ($endDate) {
                $q->where('date', '<=', $endDate)
                  ->where('status', JournalEntry::STATUS_POSTED);
            });
        }])->orderBy('code')->get();

        $reportData = [];
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($accounts as $account) {
            $debits = $account->transactions->where('type', 'debit')->sum('amount');
            $credits = $account->transactions->where('type', 'credit')->sum('amount');
            $balance = $debits - $credits;

            if ($balance == 0 && $account->transactions->isEmpty()) {
                continue;
            }

            $debitBalance = 0;
            $creditBalance = 0;

            if (in_array($account->type, ['asset', 'expense']) || $account->isContraAccount()) {
                if ($balance > 0) {
                    $debitBalance = $balance;
                } else {
                    $creditBalance = -$balance;
                }
            } else {
                if ($balance < 0) {
                    $creditBalance = -$balance;
                } else {
                    $debitBalance = $balance;
                }
            }

            $reportData[] = [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'debit' => $debitBalance,
                'credit' => $creditBalance,
            ];

            $totalDebits += $debitBalance;
            $totalCredits += $creditBalance;
        }

        $isBalanced = round($totalDebits, 2) === round($totalCredits, 2);

        return [
            'accounts' => $reportData,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'is_balanced' => $isBalanced,
            'end_date' => $endDate->toDateString(),
        ];
    }
}
