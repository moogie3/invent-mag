<?php

namespace App\Services;

use App\Models\AccountingAuditLog;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AccountingAuditService
{
    /**
     * Log the creation of a journal entry.
     */
    public function logCreate(JournalEntry $journalEntry, array $transactions = [], ?string $description = null): AccountingAuditLog
    {
        return $this->createLog(
            AccountingAuditLog::ACTION_CREATE,
            $journalEntry,
            null,
            $this->extractJournalEntryData($journalEntry, $transactions),
            $description ?? "Created journal entry: {$journalEntry->description}"
        );
    }

    /**
     * Log an update to a journal entry.
     */
    public function logUpdate(JournalEntry $journalEntry, array $oldValues, array $newTransactions = []): AccountingAuditLog
    {
        return $this->createLog(
            AccountingAuditLog::ACTION_UPDATE,
            $journalEntry,
            $oldValues,
            $this->extractJournalEntryData($journalEntry, $newTransactions),
            "Updated journal entry: {$journalEntry->description}"
        );
    }

    /**
     * Log an action (post, unpost, void, etc.).
     */
    public function logAction(JournalEntry $journalEntry, string $action, ?array $oldValues = null, array $additionalData = []): AccountingAuditLog
    {
        $actionDescriptions = [
            AccountingAuditLog::ACTION_POST => "Posted journal entry: {$journalEntry->description}",
            AccountingAuditLog::ACTION_UNPOST => "Unposted journal entry: {$journalEntry->description}",
            AccountingAuditLog::ACTION_VOID => "Voided journal entry: {$journalEntry->description}",
            AccountingAuditLog::ACTION_REVERSE => "Reversed journal entry: {$journalEntry->description}",
            AccountingAuditLog::ACTION_DELETE => "Deleted journal entry: {$journalEntry->description}",
            AccountingAuditLog::ACTION_APPROVE => "Approved journal entry: {$journalEntry->description}",
            AccountingAuditLog::ACTION_REJECT => "Rejected journal entry: {$journalEntry->description}",
        ];

        return $this->createLog(
            $action,
            $journalEntry,
            $oldValues,
            array_merge($this->extractJournalEntryData($journalEntry), $additionalData),
            $actionDescriptions[$action] ?? "Action {$action} on journal entry: {$journalEntry->description}"
        );
    }

    /**
     * Create an audit log entry.
     */
    protected function createLog(
        string $action,
        JournalEntry $journalEntry,
        ?array $oldValues,
        array $newValues,
        string $description
    ): AccountingAuditLog {
        $user = Auth::user();

        return AccountingAuditLog::create([
            'tenant_id' => $journalEntry->tenant_id,
            'action' => $action,
            'entity_type' => get_class($journalEntry),
            'entity_id' => $journalEntry->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Extract relevant data from a journal entry for audit purposes.
     */
    protected function extractJournalEntryData(JournalEntry $journalEntry, array $transactions = []): array
    {
        $data = [
            'id' => $journalEntry->id,
            'date' => $journalEntry->date->toDateString(),
            'description' => $journalEntry->description,
            'status' => $journalEntry->status,
            'entry_type' => $journalEntry->entry_type,
            'total_debit' => $journalEntry->total_debit,
            'total_credit' => $journalEntry->total_credit,
        ];

        if (!empty($transactions)) {
            $data['transactions'] = array_map(function ($tx) {
                return [
                    'account_code' => $tx['account_code'] ?? null,
                    'account_name' => $tx['account_name'] ?? null,
                    'type' => $tx['type'],
                    'amount' => $tx['amount'],
                ];
            }, $transactions);
        } else {
            $data['transactions'] = $journalEntry->transactions->map(function ($tx) {
                return [
                    'account_code' => $tx->account->code,
                    'account_name' => $tx->account->name,
                    'type' => $tx->type,
                    'amount' => (float) $tx->amount,
                ];
            })->toArray();
        }

        return $data;
    }

    /**
     * Get audit logs for a journal entry.
     */
    public function getEntryAuditLogs(JournalEntry $journalEntry)
    {
        return AccountingAuditLog::where('entity_type', get_class($journalEntry))
            ->where('entity_id', $journalEntry->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get audit logs for a tenant within a date range.
     */
    public function getTenantAuditLogs(array $filters = [])
    {
        $query = AccountingAuditLog::where('tenant_id', app('currentTenant')->id);

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(50);
    }

    /**
     * Get audit summary for dashboard.
     */
    public function getAuditSummary(int $days = 30)
    {
        $startDate = now()->subDays($days);

        $summary = AccountingAuditLog::where('tenant_id', app('currentTenant')->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->pluck('count', 'action')
            ->toArray();

        $recentLogs = AccountingAuditLog::where('tenant_id', app('currentTenant')->id)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return [
            'action_summary' => $summary,
            'recent_logs' => $recentLogs,
            'period' => $days,
        ];
    }
}