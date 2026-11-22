<?php

use App\Models\Account;
use App\Models\Sales;
use App\Models\Tenant;
use App\Services\AccountingService;
use App\Services\StockValuationService;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Half of the 2026-11-21 seed-data repair: the demo seeders booked
     * every sale through the real sale path, but four of their stock
     * sources moved goods without ever journaling them, so some of those
     * bookings charge the ledger more (or less) than the goods can be
     * shown to have cost.
     *
     * StockValuationService::assessSaleCost() proves a figure wrong only
     * when it sits outside every cost the surviving batch history still
     * permits for the moment the sale was made: a batch untouched since
     * the sale held exactly its recorded remaining, one moved since may
     * have held anything between none and all of it, a batch received
     * later did not exist. Anything inside that range is reported and
     * left alone, because moving profit on an estimate is the same
     * refusal the sales:backfill-cogs command was built on.
     *
     * Each correction is sourced to its sale, so a later edit or delete
     * reverses it together with every other entry the sale owns, and it
     * is dated as the sale was, the way sales:backfill-cogs dates its
     * corrections, so the cost stays in the period the goods left. A
     * re-run posts nothing: once corrected the ledger sits on the proven
     * edge and the assessment reads "correct".
     *
     * Runs before the shelf alignment below, which measures what is left
     * once these corrections have landed.
     */
    public function up(): void
    {
        foreach (Tenant::query()->get() as $tenant) {
            $tenant->execute(function () use ($tenant) {
                DB::transaction(function () use ($tenant) {
                    $this->repairTenant($tenant);
                });
            });
        }
    }

    private function repairTenant(Tenant $tenant): void
    {
        $settings = $tenant->accounting_settings ?? [];
        $cogsAccount = Account::find($settings['cost_of_goods_sold_account_id'] ?? null);
        $inventoryAccount = Account::find($settings['inventory_account_id'] ?? null);

        if (! $cogsAccount || ! $inventoryAccount) {
            return;
        }

        $valuation = app(StockValuationService::class);
        $accountingService = app(AccountingService::class);

        Sales::query()
            ->with(['salesItems.product'])
            ->orderBy('id')
            ->chunkById(100, function ($sales) use ($valuation, $accountingService, $cogsAccount, $inventoryAccount) {
                foreach ($sales as $sale) {
                    $assessment = $valuation->assessSaleCost($sale, $cogsAccount);

                    if ($assessment['verdict'] !== 'over' && $assessment['verdict'] !== 'under') {
                        continue;
                    }

                    $amount = round(abs($assessment['booked'] - $assessment['target']), 2);

                    if ($amount < 0.01) {
                        continue;
                    }

                    // Overstated: the goods cost the books more than they
                    // can have cost, so Inventory takes the excess back
                    // and COGS gives it up. Understated: the other way.
                    $transactions = $assessment['verdict'] === 'over'
                        ? [
                            ['account_name' => $inventoryAccount->name, 'type' => 'debit', 'amount' => $amount],
                            ['account_name' => $cogsAccount->name, 'type' => 'credit', 'amount' => $amount],
                        ]
                        : [
                            ['account_name' => $cogsAccount->name, 'type' => 'debit', 'amount' => $amount],
                            ['account_name' => $inventoryAccount->name, 'type' => 'credit', 'amount' => $amount],
                        ];

                    $accountingService->createJournalEntry(
                        "COGS proof correction for Sale #{$sale->invoice}",
                        Carbon::parse($sale->order_date),
                        $transactions,
                        $sale,
                        ['notes' => '2026-11-21 sale COGS repair']
                    );
                }
            });
    }

    /**
     * Delete only what up() created, identified by their notes marker:
     * transactions first, then the entries themselves (which detaches
     * them from their sales).
     */
    public function down(): void
    {
        $ids = DB::table('journal_entries')
            ->where('notes', '2026-11-21 sale COGS repair')
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
