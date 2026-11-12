<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Data half of audit finding #4. cancelSalesReturn()/cancelPurchaseReturn()
     * only reverted stock and reversed the GL when the return was Completed,
     * so every return canceled from Pending kept the entry its creation had
     * already posted: 41 Canceled returns (20 sales, 21 purchase) hold a live
     * posted journal entry, over-reversing AR/AP by Rp 35,860 and Rp 68,930
     * against returns that no longer exist. The service fix protects cancels
     * from here on; this migration corrects the rows the bug already wrote.
     *
     * Each reversal mirrors AccountingService::reverseJournalEntry() line for
     * line: swapped transactions, entry_type 'reversal', reversed_entry_id
     * pointing at the original, sourceable NULL, description "REVERSAL - ...".
     * Dated TODAY - a correction belongs to the open period; rewriting the
     * original date would move history in a closed one. No
     * accounting_audit_logs row: reverseJournalEntry() itself logs only under
     * Auth::check(), and a migration has no user. Selection is the same
     * live-entry test the cancel paths use (no reversal points at the
     * entry), so a re-run finds nothing to do: the pointer lives only on
     * reversals (AccountingService::reverseJournalEntry writes it there),
     * never on the original, so testing the original's own
     * reversed_entry_id column would match every row forever. None of the
     * 41 is bank-reconciled, which is reverseJournalEntry()'s other guard
     * (assertNotReconciled) - verified live before writing.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $returnsBySourceable = [
                'App\Models\SalesReturn' => ['table' => 'sales_returns', 'label' => 'Sales Return'],
                'App\Models\PurchaseReturn' => ['table' => 'purchase_returns', 'label' => 'Purchase Return'],
            ];

            foreach ($returnsBySourceable as $sourceableType => $meta) {
                $canceledIds = DB::table($meta['table'])
                    ->where('status', 'Canceled')
                    ->pluck('id');

                if ($canceledIds->isEmpty()) {
                    continue;
                }

                $entries = DB::table('journal_entries')
                    ->where('sourceable_type', $sourceableType)
                    ->whereIn('sourceable_id', $canceledIds)
                    ->where('status', 'posted')
                    ->whereNotExists(function ($query) {
                        $query->selectRaw('1')
                            ->from('journal_entries as reversals')
                            ->whereColumn('reversals.reversed_entry_id', 'journal_entries.id');
                    })
                    ->get();

                foreach ($entries as $entry) {
                    $this->reverse($entry, $meta['label']);
                }
            }
        });
    }

    /**
     * Create the reversal entry plus its swapped transactions, exactly as
     * AccountingService::reverseJournalEntry() would for a user-driven cancel.
     */
    private function reverse(object $entry, string $label): void
    {
        $now = now();

        $reversalId = DB::table('journal_entries')->insertGetId([
            'tenant_id' => $entry->tenant_id,
            'date' => $now->toDateString(),
            'description' => "REVERSAL - {$entry->description}",
            'status' => 'posted',
            'notes' => "Reversal due to canceled {$label} #{$entry->sourceable_id} - 2026-11-08 data correction: cancel predated the fix",
            'reversed_entry_id' => $entry->id,
            'entry_type' => 'reversal',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::statement(
            'INSERT INTO transactions (tenant_id, journal_entry_id, account_id, type, amount, created_at, updated_at)
             SELECT tenant_id, ?, account_id, IF(type = \'debit\', \'credit\', \'debit\'), amount, ?, ?
             FROM transactions WHERE journal_entry_id = ?',
            [$reversalId, $now, $now, $entry->id]
        );
    }

    /**
     * Delete only what up() created, identified by the correction marker in
     * notes: their transactions first, then the entries themselves (which
     * clears reversed_entry_id on the originals as a side effect).
     */
    public function down(): void
    {
        $ids = DB::table('journal_entries')
            ->where('entry_type', 'reversal')
            ->where('notes', 'like', '%2026-11-08 data correction%')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($ids) {
            DB::table('transactions')->whereIn('journal_entry_id', $ids)->delete();
            DB::table('journal_entries')->whereIn('id', $ids)->delete();
        });
    }
};
