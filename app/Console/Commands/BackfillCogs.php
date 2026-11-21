<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Sales;
use App\Models\Tenant;
use App\Services\AccountingService;
use App\Services\StockValuationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * One-off repair for sales whose journals never charged the goods they sold:
 * the point-of-sale path cost from a product field that does not exist, so
 * COGS and Inventory sat at zero while stock left the shelf.
 *
 * Dry-run by default; --apply posts. For every sale the books actually hold
 * it recomputes the sold units from the same FEFO batches a fresh sale costs
 * from, nets that against the cost already booked for the sale (its own
 * entries plus the reversals of them), and posts Dr COGS / Cr Inventory for
 * the difference only. Sales already correct, sales reversed out of the
 * books, sales the books never held, and sales carrying more cost than the
 * recomputation are reported and left alone.
 *
 * The correction is sourced to the sale, so a later edit or delete reverses
 * it together with every other entry the sale owns, and a re-run posts
 * nothing: once corrected, the ledger and the recomputation agree.
 */
class BackfillCogs extends Command
{
    protected $signature = 'sales:backfill-cogs
        {--tenant= : Only run for this tenant ID}
        {--apply : Post the corrections (default reports only)}';

    protected $description = 'Post COGS corrections for sales whose journals booked no cost for the goods they sold';

    public function handle(): int
    {
        $tenants = Tenant::query();
        if ($tenantId = $this->option('tenant')) {
            $tenants->where('id', $tenantId);
        }

        $apply = (bool) $this->option('apply');
        $grandCount = 0;

        foreach ($tenants->get() as $tenant) {
            $summary = $tenant->execute(fn () => $this->backfillTenant($tenant, $apply));
            $grandCount += $summary['count'];

            $this->info(sprintf(
                'Tenant %d (%s): %s%d correction%s %s totalling %s (%d already correct, %d overstated, %d not in the books).',
                $tenant->id,
                $tenant->name,
                $apply ? '' : 'DRY RUN, ',
                $summary['count'],
                $summary['count'] === 1 ? '' : 's',
                $apply ? 'posted' : 'would post',
                number_format($summary['amount'], 2),
                $summary['correct'],
                $summary['overstated'],
                $summary['missing']
            ));
        }

        if ($grandCount === 0) {
            $this->info(
                $apply
                    ? 'Nothing posted: every sale in the books already carries its cost.'
                    : 'Nothing to post: every sale in the books already carries its cost.'
            );
        } elseif (! $apply) {
            $this->comment('Run again with --apply to post these corrections.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{count: int, amount: float, correct: int, overstated: int, missing: int}
     */
    private function backfillTenant(Tenant $tenant, bool $apply): array
    {
        $summary = ['count' => 0, 'amount' => 0.0, 'correct' => 0, 'overstated' => 0, 'missing' => 0];

        $settings = $tenant->accounting_settings ?? [];
        $cogsAccount = Account::find($settings['cost_of_goods_sold_account_id'] ?? null);
        $inventoryAccount = Account::find($settings['inventory_account_id'] ?? null);

        if (! $cogsAccount || ! $inventoryAccount) {
            $this->line('  No COGS/Inventory accounts configured; tenant skipped.');

            return $summary;
        }

        $valuation = app(StockValuationService::class);
        $accountingService = app(AccountingService::class);
        $rows = [];

        Sales::query()
            ->with(['salesItems.product'])
            ->orderBy('id')
            ->chunkById(100, function ($sales) use (&$summary, &$rows, $cogsAccount, $inventoryAccount, $valuation, $accountingService, $apply) {
                foreach ($sales as $sale) {
                    $entries = JournalEntry::with('transactions')
                        ->where('sourceable_type', Sales::class)
                        ->where('sourceable_id', $sale->id)
                        ->get();

                    if ($entries->isEmpty()) {
                        $summary['missing']++;

                        continue;
                    }

                    $reversals = JournalEntry::with('transactions')
                        ->whereIn('reversed_entry_id', $entries->pluck('id'))
                        ->get();

                    $reversedIds = $reversals->pluck('reversed_entry_id');

                    if (! $entries->contains(fn ($entry) => ! $reversedIds->contains($entry->id))) {
                        // Every entry this sale owns has been netted out by a
                        // reversal; the sale is no longer in the books and
                        // must not be written back in.
                        $summary['missing']++;

                        continue;
                    }

                    // What the ledger already holds as cost for this sale:
                    // its own COGS lines, with any reversal subtracted.
                    $booked = 0.0;
                    foreach ($entries->concat($reversals) as $entry) {
                        foreach ($entry->transactions as $transaction) {
                            if ((int) $transaction->account_id !== (int) $cogsAccount->id) {
                                continue;
                            }

                            $booked += $transaction->type === 'debit'
                                ? (float) $transaction->amount
                                : -(float) $transaction->amount;
                        }
                    }

                    $recomputed = 0.0;
                    foreach ($sale->salesItems as $item) {
                        if (! $item->product) {
                            continue;
                        }

                        $recomputed += $valuation->costOfUnits(
                            (int) $item->product->id,
                            $item->quantity,
                            (float) $item->product->price
                        );
                    }

                    $delta = $recomputed - $booked;

                    if ($delta >= -0.005 && $delta <= 0.005) {
                        $summary['correct']++;

                        continue;
                    }

                    if ($delta < -0.005) {
                        // The books claim more cost than the goods can have
                        // cost. Writing that down silently would move profit
                        // on our own estimate, so it is reported instead.
                        $summary['overstated']++;
                        $rows[] = [
                            $sale->invoice,
                            number_format($booked, 2),
                            number_format($recomputed, 2),
                            number_format($delta, 2),
                        ];

                        continue;
                    }

                    $shortfall = round($delta, 2);

                    if ($apply) {
                        $accountingService->createJournalEntry(
                            "COGS correction for Sale #{$sale->invoice}",
                            Carbon::parse($sale->order_date),
                            [
                                ['account_name' => $cogsAccount->name, 'type' => 'debit', 'amount' => $shortfall],
                                ['account_name' => $inventoryAccount->name, 'type' => 'credit', 'amount' => $shortfall],
                            ],
                            $sale
                        );
                    }

                    $summary['count']++;
                    $summary['amount'] += $shortfall;
                    $rows[] = [
                        $sale->invoice,
                        number_format($booked, 2),
                        number_format($recomputed, 2),
                        number_format($shortfall, 2),
                    ];
                }
            });

        if ($rows !== []) {
            $this->table(['Invoice', 'Booked COGS', 'Recomputed', 'Shortfall'], $rows);
        }

        return $summary;
    }
}
