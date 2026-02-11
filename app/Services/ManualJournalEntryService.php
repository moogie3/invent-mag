<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\RecurringJournalEntry;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ManualJournalEntryService
{
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Create a new manual journal entry.
     */
    public function create(array $data): JournalEntry
    {
        $validated = $this->validate($data);

        $transactions = $this->prepareTransactions($validated['transactions']);

        return $this->accountingService->createManualJournalEntry(
            $validated['description'],
            Carbon::parse($validated['date']),
            $transactions,
            $validated['notes'] ?? null,
            $validated['auto_post'] ?? false
        );
    }

    /**
     * Update a draft journal entry.
     */
    public function update(JournalEntry $journalEntry, array $data): JournalEntry
    {
        if ($journalEntry->status !== JournalEntry::STATUS_DRAFT) {
            throw new Exception("Only draft entries can be updated.");
        }

        $validated = $this->validate($data);
        $transactions = $this->prepareTransactions($validated['transactions']);

        return $this->accountingService->updateDraftJournalEntry(
            $journalEntry,
            [
                'description' => $validated['description'],
                'date' => Carbon::parse($validated['date']),
                'notes' => $validated['notes'] ?? null,
            ],
            $transactions
        );
    }

    /**
     * Post a journal entry.
     */
    public function post(JournalEntry $journalEntry): JournalEntry
    {
        return $this->accountingService->postJournalEntry($journalEntry);
    }

    /**
     * Void a journal entry.
     */
    public function void(JournalEntry $journalEntry, string $reason): JournalEntry
    {
        return $this->accountingService->voidJournalEntry($journalEntry, $reason);
    }

    /**
     * Reverse a journal entry.
     */
    public function reverse(JournalEntry $journalEntry, ?string $notes = null): JournalEntry
    {
        return $this->accountingService->reverseJournalEntry($journalEntry, $notes);
    }

    /**
     * Delete a draft journal entry.
     */
    public function delete(JournalEntry $journalEntry): bool
    {
        return $this->accountingService->deleteDraftJournalEntry($journalEntry);
    }

    /**
     * Get all draft entries.
     */
    public function getDraftEntries(array $filters = [])
    {
        $query = JournalEntry::draft()->manual()->with('transactions.account');

        if (isset($filters['start_date'])) {
            $query->whereDate('date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('date', '<=', $filters['end_date']);
        }

        if (isset($filters['search'])) {
            $query->where('description', 'like', "%{$filters['search']}%");
        }

        return $query->orderBy('date', 'desc')->paginate(20);
    }

    /**
     * Get all posted entries.
     */
    public function getPostedEntries(array $filters = [])
    {
        $query = JournalEntry::posted()->manual()->with('transactions.account');

        if (isset($filters['start_date'])) {
            $query->whereDate('date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('date', '<=', $filters['end_date']);
        }

        if (isset($filters['search'])) {
            $query->where('description', 'like', "%{$filters['search']}%");
        }

        return $query->orderBy('date', 'desc')->paginate(20);
    }

    /**
     * Get entry with all details.
     */
    public function getEntryDetails(JournalEntry $journalEntry)
    {
        return JournalEntry::with([
            'transactions.account',
            'approvedBy',
            'reversedEntry',
            'reversingEntry',
        ])->find($journalEntry->id);
    }

    /**
     * Duplicate an existing entry.
     */
    public function duplicate(JournalEntry $original, bool $autoPost = false): JournalEntry
    {
        $transactions = $original->transactions->map(function ($tx) {
            return [
                'account_id' => $tx->account_id,
                'type' => $tx->type,
                'amount' => $tx->amount,
            ];
        })->toArray();

        return $this->accountingService->createManualJournalEntry(
            "COPY - {$original->description}",
            now(),
            $transactions,
            "Duplicated from entry #{$original->id}",
            $autoPost
        );
    }

    /**
     * Validate manual journal entry data.
     */
    protected function validate(array $data): array
    {
        $rules = [
            'description' => 'required|string|max:500',
            'date' => 'required|date',
            'transactions' => 'required|array|min:2',
            'transactions.*.account_code' => 'required|string',
            'transactions.*.type' => 'required|in:debit,credit',
            'transactions.*.amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
            'auto_post' => 'nullable|boolean',
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * Prepare transactions for the accounting service.
     */
    protected function prepareTransactions(array $transactions): array
    {
        return array_map(function ($tx) {
            return [
                'account_code' => $tx['account_code'],
                'type' => $tx['type'],
                'amount' => $tx['amount'],
            ];
        }, $transactions);
    }

    /**
     * Get accounts that allow manual entries.
     */
    public function getAvailableAccounts()
    {
        return Account::active()
            ->where('allow_manual_entry', true)
            ->orderBy('code')
            ->get();
    }

    /**
     * Search accounts for autocomplete.
     */
    public function searchAccounts(string $query)
    {
        return Account::active()
            ->where('allow_manual_entry', true)
            ->where(function ($q) use ($query) {
                $q->where('code', 'like', "%{$query}%")
                  ->orWhere('name', 'like', "%{$query}%");
            })
            ->orderBy('code')
            ->limit(10)
            ->get();
    }

    /**
     * Process recurring journal entries.
     */
    public function processRecurringEntries(): int
    {
        $processed = 0;
        $recurringEntries = RecurringJournalEntry::dueForGeneration()->get();

        foreach ($recurringEntries as $recurring) {
            try {
                DB::transaction(function () use ($recurring) {
                    $recurring->generateJournalEntry();
                });
                $processed++;
            } catch (Exception $e) {
                \Log::error("Failed to generate recurring journal entry #{$recurring->id}: {$e->getMessage()}");
            }
        }

        return $processed;
    }
}