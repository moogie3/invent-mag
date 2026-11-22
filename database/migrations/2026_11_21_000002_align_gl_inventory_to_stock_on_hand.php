<?php

use App\Models\Tenant;
use App\Services\StockValuationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Half of the 2026-11-21 seed-data repair, and the half that carries
     * the weight. Four demo stock sources (StockAdjustmentSeeder's
     * "Initial stock adjustment", ProductSeeder's fixed rows,
     * SalesSeeder's pre-sale top-up and CreditFeatureSeeder's top-ups)
     * moved goods onto the shelf without ever journaling them, so every
     * seeded tenant's Inventory account sits below what its stock is
     * worth. StockTransferSeeder nets to zero per product and needs no
     * entry, and every production path (purchase, POS, adjustment,
     * opname, returns) already journals correctly, so the difference is
     * seed data only.
     *
     * StockValuationService::alignGlToShelf() measures the difference and
     * posts it in one entry: Inventory moves by the gap and Opening
     * Balance Equity takes the other side, which is where a
     * company-setup inventory figure belongs (the same account QuickBooks,
     * Xero and Wave credit when inventory is set up). It runs after the
     * COGS repair above, so it measures what is left once those
     * corrections have landed, and it is idempotent by construction:
     * a second run measures zero.
     *
     * The same call sits at the end of DatabaseSeeder, so a database
     * seeded from scratch reaches the same state without needing this
     * migration to run afterwards.
     */
    public function up(): void
    {
        foreach (Tenant::query()->get() as $tenant) {
            $tenant->execute(function () {
                DB::transaction(function () {
                    app(StockValuationService::class)->alignGlToShelf(
                        'Stock on hand not in the books - 2026-11-21 data correction',
                        '2026-11-21 shelf alignment'
                    );
                });
            });
        }
    }

    /**
     * Delete only what up() created, identified by their notes marker:
     * transactions first, then the entries themselves.
     */
    public function down(): void
    {
        $ids = DB::table('journal_entries')
            ->where('notes', '2026-11-21 shelf alignment')
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
