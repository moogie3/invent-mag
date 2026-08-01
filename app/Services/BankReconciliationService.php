<?php

namespace App\Services;

use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\JournalEntry;
use App\Services\BankStatement\BankStatementParserFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BankReconciliationService
{
    /**
     * Days before/after a statement line's date to look for a matching journal entry.
     */
    public const MATCH_DATE_WINDOW_DAYS = 5;

    public function getIndexData(array $filters, int $entries)
    {
        $query = BankReconciliation::with(['account', 'createdBy']);

        if (isset($filters['month']) && $filters['month']) {
            $query->whereMonth('period_end', $filters['month']);

            if (! isset($filters['year']) || ! $filters['year']) {
                $query->whereYear('period_end', now()->year);
            }
        }

        if (isset($filters['year']) && $filters['year']) {
            $query->whereYear('period_end', $filters['year']);
        }

        $reconciliations = $query->latest('period_end')->paginate($entries);

        $totalCompleted = BankReconciliation::completed()->count();
        $totalOpen = BankReconciliation::open()->count();

        return compact('reconciliations', 'totalCompleted', 'totalOpen');
    }

    public function getCreateData(): array
    {
        $accounts = Account::where('type', 'asset')
            ->where('is_contra', false)
            ->where('is_active', true)
            ->where('level', '>=', 2)
            ->orderBy('code')
            ->get();

        $accountOpeningBalances = $accounts->mapWithKeys(fn (Account $account) => [
            $account->id => [
                'amount' => (float) $account->opening_balance,
                'date' => $account->opening_balance_date ? Carbon::parse($account->opening_balance_date)->toDateString() : null,
            ],
        ])->all();

        $tenant = app('currentTenant');
        $defaultAccountId = $tenant?->getAccountingSetting('cash_account_id');
        $defaultAccount = $defaultAccountId ? $accounts->firstWhere('id', $defaultAccountId) : null;

        return [
            'accounts' => $accounts,
            'banks' => BankStatementParserFactory::supportedBanks(),
            'defaultAccountId' => $defaultAccountId,
            'accountOpeningBalances' => $accountOpeningBalances,
            'defaultOpeningBalance' => $defaultAccount ? (float) $defaultAccount->opening_balance : 0,
            'defaultOpeningBalanceDate' => $defaultAccount?->opening_balance_date
                ? Carbon::parse($defaultAccount->opening_balance_date)->toDateString()
                : null,
        ];
    }

    public function importStatement(
        int $accountId,
        string $bank,
        string $content,
        float $openingBalance,
        float $closingBalance,
        ?string $periodStart = null,
        ?string $periodEnd = null
    ): BankReconciliation {
        $parser = BankStatementParserFactory::make($bank);
        $lines = $parser->parse($content);

        if (empty($lines)) {
            throw new \InvalidArgumentException('The statement file contains no recognized transaction lines.');
        }

        return DB::transaction(function () use (
            $accountId,
            $lines,
            $openingBalance,
            $closingBalance,
            $periodStart,
            $periodEnd
        ) {
            $dates = array_column($lines, 'date');

            $reconciliation = BankReconciliation::create([
                'account_id' => $accountId,
                'period_start' => $periodStart ?: min($dates),
                'period_end' => $periodEnd ?: max($dates),
                'opening_balance' => $openingBalance,
                'closing_balance' => $closingBalance,
                'status' => BankReconciliation::STATUS_OPEN,
                'created_by' => auth()->id(),
            ]);

            foreach ($lines as $line) {
                BankReconciliationItem::create([
                    'bank_reconciliation_id' => $reconciliation->id,
                    'line_date' => $line['date'],
                    'description' => $line['description'],
                    'reference' => $line['reference'] ?? null,
                    'type' => $line['type'],
                    'amount' => $line['amount'],
                    'status' => BankReconciliationItem::STATUS_UNMATCHED,
                ]);
            }

            $this->recalculateTotals($reconciliation);
            $this->autoMatch($reconciliation);

            return $reconciliation;
        });
    }

    public function autoMatch(BankReconciliation $reconciliation): int
    {
        $matched = 0;

        foreach ($reconciliation->items()->unmatched()->get() as $item) {
            $candidate = $this->findMatchingJournalEntry($reconciliation, $item);

            if ($candidate === null) {
                continue;
            }

            $item->update([
                'status' => BankReconciliationItem::STATUS_MATCHED,
                'journal_entry_id' => $candidate->id,
                'matched_at' => now(),
            ]);

            $matched++;
        }

        $this->recalculateTotals($reconciliation);

        return $matched;
    }

    public function manualMatch(
        BankReconciliation $reconciliation,
        BankReconciliationItem $item,
        int $journalEntryId
    ): void {
        if ($reconciliation->isCompleted()) {
            throw new \DomainException('Cannot modify a completed reconciliation.');
        }

        $entry = JournalEntry::where('status', JournalEntry::STATUS_POSTED)
            ->where('entry_type', '!=', JournalEntry::ENTRY_TYPE_REVERSAL)
            ->whereHas('transactions', function ($query) use ($reconciliation, $item) {
                $amount = round((float) $item->amount, 2);

                $query->where('account_id', $reconciliation->account_id)
                    ->where('amount', '>=', $amount - 0.005)
                    ->where('amount', '<=', $amount + 0.005);
            })
            ->whereDoesntHave('bankReconciliationItems')
            ->findOrFail($journalEntryId);

        $item->update([
            'status' => BankReconciliationItem::STATUS_MANUALLY_MATCHED,
            'journal_entry_id' => $entry->id,
            'matched_at' => now(),
        ]);

        $this->recalculateTotals($reconciliation);
    }

    public function unmatch(BankReconciliationItem $item): void
    {
        if ($item->reconciliation->isCompleted()) {
            throw new \DomainException('Cannot modify a completed reconciliation.');
        }

        $item->update([
            'status' => BankReconciliationItem::STATUS_UNMATCHED,
            'journal_entry_id' => null,
            'matched_at' => null,
        ]);

        $this->recalculateTotals($item->reconciliation);
    }

    public function complete(BankReconciliation $reconciliation): void
    {
        $this->recalculateTotals($reconciliation);

        if (abs($reconciliation->variance) > 0.005) {
            throw new \DomainException(
                'Cannot complete the reconciliation. The variance is '.number_format($reconciliation->variance, 2).'. '
                .'Resolve all unmatched lines or correct the closing balance before completing.'
            );
        }

        $reconciliation->update([
            'status' => BankReconciliation::STATUS_COMPLETED,
        ]);
    }

    public function delete(BankReconciliation $reconciliation): void
    {
        $reconciliation->delete();
    }

    public function getShowData(BankReconciliation $reconciliation): array
    {
        $reconciliation->load(['account', 'items.journalEntry']);

        return [
            'reconciliation' => $reconciliation,
            'candidates' => $this->getJournalEntryCandidates($reconciliation),
        ];
    }

    public function getJournalEntryCandidates(BankReconciliation $reconciliation)
    {
        return JournalEntry::where('status', JournalEntry::STATUS_POSTED)
            ->where('entry_type', '!=', JournalEntry::ENTRY_TYPE_REVERSAL)
            ->where('date', '>=', $reconciliation->period_start->copy()->subDays(30)->toDateString())
            ->where('date', '<=', $reconciliation->period_end->copy()->addDays(30)->toDateString())
            ->whereHas('transactions', function ($query) use ($reconciliation) {
                $query->where('account_id', $reconciliation->account_id);
            })
            ->whereDoesntHave('bankReconciliationItems')
            ->with(['transactions' => function ($query) use ($reconciliation) {
                $query->where('account_id', $reconciliation->account_id);
            }])
            ->orderBy('date')
            ->limit(200)
            ->get();
    }

    public function isJournalEntryReconciled(JournalEntry $journalEntry): bool
    {
        return BankReconciliationItem::where('journal_entry_id', $journalEntry->id)
            ->whereHas('reconciliation', function ($query) {
                $query->where('status', BankReconciliation::STATUS_COMPLETED);
            })
            ->exists();
    }

    protected function findMatchingJournalEntry(BankReconciliation $reconciliation, BankReconciliationItem $item): ?JournalEntry
    {
        $amount = round((float) $item->amount, 2);

        $candidates = JournalEntry::where('status', JournalEntry::STATUS_POSTED)
            ->where('entry_type', '!=', JournalEntry::ENTRY_TYPE_REVERSAL)
            ->where('date', '>=', $item->line_date->copy()->subDays(self::MATCH_DATE_WINDOW_DAYS)->toDateString())
            ->where('date', '<=', $item->line_date->copy()->addDays(self::MATCH_DATE_WINDOW_DAYS)->toDateString())
            ->whereHas('transactions', function ($query) use ($reconciliation, $amount) {
                $query->where('account_id', $reconciliation->account_id)
                    ->where('amount', '>=', $amount - 0.005)
                    ->where('amount', '<=', $amount + 0.005);
            })
            ->whereDoesntHave('bankReconciliationItems')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        return $candidates->sortBy(
            fn (JournalEntry $entry) => abs($entry->date->diffInDays($item->line_date))
        )->first();
    }

    protected function recalculateTotals(BankReconciliation $reconciliation): void
    {
        $items = $reconciliation->items()->get();

        $reconciliation->update([
            'total_debit' => round((float) $items->where('type', 'debit')->sum('amount'), 2),
            'total_credit' => round((float) $items->where('type', 'credit')->sum('amount'), 2),
            'matched_amount' => round((float) $items->filter(fn ($i) => $i->isMatched())->sum('amount'), 2),
            'unmatched_amount' => round((float) $items->filter(fn ($i) => ! $i->isMatched())->sum('amount'), 2),
            'statement_lines_count' => $items->count(),
        ]);
    }
}
