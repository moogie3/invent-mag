<?php

namespace Tests\Unit\Seeders;

use App\Models\JournalEntry;
use App\Models\Purchase;
use App\Models\Sales;
use App\Models\Tenant;
use Database\Seeders\AccountSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PurchaseReturnSeeder;
use Database\Seeders\PurchaseSeeder;
use Database\Seeders\SalesReturnSeeder;
use Database\Seeders\SalesSeeder;
use Database\Seeders\StockOpnameSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AssertsSchemaColumns;

/**
 * The full DatabaseSeeder chain had never been executed by a test. It runs
 * 36 seeders across six tenants and books real journal lines, so a seeder
 * that drives stock negative, over-returns past what a sale ever shipped or
 * posts an unbalanced entry would only ever surface on a fresh install.
 *
 * Two things are held to account here: the accounting invariants of a fresh
 * seed, and the claim that running the whole chain a second time changes
 * nothing. The second claim used to be false in both directions. It crashed
 * on attendance_records, because AttendanceRecord casts date to 'date' and
 * updateOrCreate() looked the row up with a bare 'Y-m-d' that never matched
 * the stored 'Y-m-d H:i:s'. And where it did not crash it duplicated:
 * ManualJournalEntrySeeder and CreditFeatureSeeder each booked a fresh set
 * of journal entries and transactions on top of the originals, every entry
 * balancing on its own, so the general ledger doubled without anything
 * looking wrong. Every seeder now carries a re-run guard, and
 * a_second_full_seed_is_a_no_op() holds them all to it.
 */
#[Large]
class DatabaseSeederTest extends TestCase
{
    use AssertsSchemaColumns;
    use RefreshDatabase;

    /** Posted entries must balance to the cent, per entry, not in aggregate. */
    private function unbalancedPostedJournalEntryIds()
    {
        // transactions.type and transactions.amount are read inside a raw
        // fragment, where they carry no table name for the parser to follow.
        $this->assertColumnsExist('transactions', ['type', 'amount']);

        $query = DB::table('transactions')
            ->select('journal_entries.id')
            ->join('journal_entries', 'journal_entries.id', '=', 'transactions.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->groupBy('transactions.journal_entry_id')
            ->havingRaw("ABS(SUM(CASE WHEN type = 'debit' THEN amount ELSE -amount END)) > 0.005");

        $this->assertQueryReadsExistingColumns($query);

        return $query->pluck('journal_entries.id')->all();
    }

    /**
     * Named rather than counted, so a failure points straight at the row
     * instead of at "1 is identical to 0".
     *
     * @return array<int, string>
     */
    private function negativeStockRows(): array
    {
        $rows = [];

        $warehouse = DB::table('product_warehouse')
            ->select('product_id', 'warehouse_id', 'tenant_id', 'quantity')
            ->where('quantity', '<', 0);

        $this->assertQueryReadsExistingColumns($warehouse);

        foreach ($warehouse->get() as $row) {
            $rows[] = "product_warehouse product {$row->product_id} warehouse {$row->warehouse_id} "
                ."tenant {$row->tenant_id} = {$row->quantity}";
        }

        // products carries no stock column of its own: stock_quantity was
        // removed for multi-warehouse support, so the per-warehouse figure
        // above is the only stock figure there is.

        $remaining = DB::table('po_items')
            ->select('id', 'product_id', 'remaining_quantity')
            ->where('remaining_quantity', '<', 0);

        $this->assertQueryReadsExistingColumns($remaining);

        foreach ($remaining->get() as $row) {
            $rows[] = "po_items #{$row->id} product {$row->product_id} remaining_quantity = {$row->remaining_quantity}";
        }

        return $rows;
    }

    /**
     * @return array<string, int> "saleId:productId" => units returned
     */
    private function returnedUnitsPerSaleAndProduct(): array
    {
        $query = DB::table('sales_return_items')
            ->join('sales_returns', 'sales_returns.id', '=', 'sales_return_items.sales_return_id')
            ->where('sales_returns.status', '!=', 'Canceled')
            ->groupBy('sales_returns.sales_id', 'sales_return_items.product_id')
            ->selectRaw('sales_returns.sales_id, sales_return_items.product_id, SUM(sales_return_items.quantity) as qty');

        $this->assertQueryReadsExistingColumns($query);

        return $query->get()
            ->keyBy(fn ($row) => $row->sales_id.':'.$row->product_id)
            ->map(fn ($row) => (int) $row->qty)
            ->all();
    }

    /** @return array<string, int>  "saleId:productId" => units sold */
    private function soldUnitsPerSaleAndProduct(): array
    {
        // The selectRaw names its columns without a table to qualify them, so
        // the parser can only see them because they are declared here.
        $this->assertColumnsExist('sales_items', ['sales_id', 'product_id', 'quantity']);

        $query = DB::table('sales_items')
            ->groupBy('sales_id', 'product_id')
            ->selectRaw('sales_id, product_id, SUM(quantity) as qty');

        $this->assertQueryReadsExistingColumns($query);

        return $query->get()
            ->keyBy(fn ($row) => $row->sales_id.':'.$row->product_id)
            ->map(fn ($row) => (int) $row->qty)
            ->all();
    }

    /** @return array<string, int>  "poId:productId" => units returned */
    private function returnedUnitsPerPurchaseAndProduct(): array
    {
        $query = DB::table('purchase_return_items')
            ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
            ->where('purchase_returns.status', '!=', 'Canceled')
            ->groupBy('purchase_returns.purchase_id', 'purchase_return_items.product_id')
            ->selectRaw('purchase_returns.purchase_id, purchase_return_items.product_id, SUM(purchase_return_items.quantity) as qty');

        $this->assertQueryReadsExistingColumns($query);

        return $query->get()
            ->keyBy(fn ($row) => $row->purchase_id.':'.$row->product_id)
            ->map(fn ($row) => (int) $row->qty)
            ->all();
    }

    /** @return array<string, int>  "poId:productId" => units ordered */
    private function orderedUnitsPerPurchaseAndProduct(): array
    {
        // The selectRaw names its columns without a table to qualify them, so
        // the parser can only see them because they are declared here.
        $this->assertColumnsExist('po_items', ['po_id', 'product_id', 'quantity']);

        $query = DB::table('po_items')
            ->groupBy('po_id', 'product_id')
            ->selectRaw('po_id, product_id, SUM(quantity) as qty');

        $this->assertQueryReadsExistingColumns($query);

        return $query->get()
            ->keyBy(fn ($row) => $row->po_id.':'.$row->product_id)
            ->map(fn ($row) => (int) $row->qty)
            ->all();
    }

    /**
     * A Canceled return holds no stock, no live journal entry and no credit.
     * The seeders now build returns through the services, so an entry left
     * alive here would be a service bug rather than a seeder shortcut.
     *
     * @return array<int, string>
     */
    private function canceledReturnsWithALiveJournal(string $model, string $table): array
    {
        $canceledIdsQuery = DB::table($table)->where('status', 'Canceled');
        $this->assertQueryReadsExistingColumns($canceledIdsQuery);
        $canceledIds = $canceledIdsQuery->pluck('id');

        if ($canceledIds->isEmpty()) {
            return [];
        }

        $nettedOutQuery = DB::table('journal_entries')->whereNotNull('reversed_entry_id');
        $this->assertQueryReadsExistingColumns($nettedOutQuery);
        $nettedOut = $nettedOutQuery->pluck('reversed_entry_id')->all();

        // JournalEntry::where() goes through the model, whose tenant scope is
        // not part of the base query, so these are declared rather than parsed.
        $this->assertColumnsExist('journal_entries', [
            'id', 'reversed_entry_id', 'sourceable_type', 'sourceable_id',
        ]);

        return JournalEntry::where('sourceable_type', $model)
            ->whereIn('sourceable_id', $canceledIds)
            ->whereNotIn('id', $nettedOut)
            ->pluck('id')
            ->all();
    }

    /** @return array<string, int> */
    private function accountingRowCounts(): array
    {
        // DB::table rather than the models: Sales, Purchase and JournalEntry
        // all carry a tenant global scope, so model counts would silently
        // narrow to whichever tenant happened to be current.
        return [
            'opening_balances' => DB::table('opening_balances')->count(),
            'sales' => DB::table('sales')->count(),
            'purchase_orders' => DB::table('po')->count(),
            'sales_returns' => DB::table('sales_returns')->count(),
            'purchase_returns' => DB::table('purchase_returns')->count(),
            'stock_opnames' => DB::table('stock_opnames')->count(),
            'journal_entries' => DB::table('journal_entries')->count(),
            'transactions' => DB::table('transactions')->count(),
            'product_warehouse' => DB::table('product_warehouse')->count(),
        ];
    }

    /**
     * The seeders that book the GL or move stock all carry a re-run guard, so
     * running them a second time has to change nothing at all. Before these
     * guards existed, AccountSeeder aborted db:seed on a duplicate
     * opening_balances row, StockOpnameSeeder collided on its hardcoded
     * SO-{today}-0001 reference and PurchaseSeeder appended another fifty
     * purchase orders with their journal entries while its sibling
     * SalesSeeder skipped.
     */
    private function reRunTheAccountingSeeders(): void
    {
        // DatabaseSeeder calls Tenant::forgetCurrent() when it finishes, and
        // every one of these seeders resolves app('currentTenant') on their
        // first line, so a tenant has to be bound again before they can run.
        $tenant = Tenant::query()->firstOrFail();
        $tenant->makeCurrent();

        try {
            foreach ([
                AccountSeeder::class,
                PurchaseSeeder::class,
                SalesSeeder::class,
                PurchaseReturnSeeder::class,
                SalesReturnSeeder::class,
                StockOpnameSeeder::class,
            ] as $seeder) {
                $this->seed($seeder);
            }
        } finally {
            Tenant::forgetCurrent();
        }
    }

    #[Test]
    public function the_full_seed_chain_keeps_the_accounting_consistent(): void
    {
        $this->seed(DatabaseSeeder::class);

        // The chain has to have actually done something, or every invariant
        // below would pass vacuously against an empty database.
        $this->assertGreaterThan(0, Tenant::count(), 'No tenants were seeded.');
        $this->assertGreaterThan(0, Sales::count(), 'No sales were seeded.');
        $this->assertGreaterThan(0, Purchase::count(), 'No purchase orders were seeded.');
        $this->assertGreaterThan(0, JournalEntry::count(), 'No journal entries were seeded.');

        // 1. Every posted entry balances on its own, not just in aggregate.
        $unbalanced = $this->unbalancedPostedJournalEntryIds();
        $this->assertSame(
            [],
            $unbalanced,
            'Posted journal entries out of balance after a fresh seed: #'.implode(', #', $unbalanced)
        );

        // 2. No seeded stock figure goes below zero.
        $this->assertSame(
            [],
            $this->negativeStockRows(),
            'Fresh seed drove stock negative: '.implode(' | ', $this->negativeStockRows())
        );

        // 3. No Canceled return is left holding a live journal entry.
        $canceledSales = $this->canceledReturnsWithALiveJournal(
            'App\\Models\\SalesReturn',
            'sales_returns'
        );
        $this->assertSame(
            [],
            $canceledSales,
            'Canceled sales returns still carrying a live journal entry: #'.implode(', #', $canceledSales)
        );

        $canceledPurchases = $this->canceledReturnsWithALiveJournal(
            'App\\Models\\PurchaseReturn',
            'purchase_returns'
        );
        $this->assertSame(
            [],
            $canceledPurchases,
            'Canceled purchase returns still carrying a live journal entry: #'.implode(', #', $canceledPurchases)
        );

        // 4. A return can never hand back more than its parent ever shipped.
        // The seeders draw a random document up to fifteen times, so without a
        // cumulative cap one invoice could be returned past what it sold.
        $sold = $this->soldUnitsPerSaleAndProduct();
        $overReturnedSales = [];
        foreach ($this->returnedUnitsPerSaleAndProduct() as $key => $returned) {
            if ($returned > ($sold[$key] ?? 0)) {
                $overReturnedSales[] = $key." returned {$returned} of ".($sold[$key] ?? 0).' sold';
            }
        }
        $this->assertSame([], $overReturnedSales, 'Sales returns exceed the sale: '.implode('; ', $overReturnedSales));

        $ordered = $this->orderedUnitsPerPurchaseAndProduct();
        $overReturnedPurchases = [];
        foreach ($this->returnedUnitsPerPurchaseAndProduct() as $key => $returned) {
            if ($returned > ($ordered[$key] ?? 0)) {
                $overReturnedPurchases[] = $key." returned {$returned} of ".($ordered[$key] ?? 0).' ordered';
            }
        }
        $this->assertSame(
            [],
            $overReturnedPurchases,
            'Purchase returns exceed the PO: '.implode('; ', $overReturnedPurchases)
        );

        // 5. TaxSeeder has to run before PurchaseSeeder and SalesSeeder, or
        // every seeded document books at rate 0 with no tax_id at all.
        $untaxedSalesQuery = DB::table('sales')->whereNull('tax_id');
        $untaxedPurchasesQuery = DB::table('po')->whereNull('tax_id');
        $this->assertQueryReadsExistingColumns($untaxedSalesQuery);
        $this->assertQueryReadsExistingColumns($untaxedPurchasesQuery);
        $untaxedSales = $untaxedSalesQuery->count();
        $untaxedPurchases = $untaxedPurchasesQuery->count();
        $this->assertSame(0, $untaxedSales, "{$untaxedSales} seeded sales carry no tax.");
        $this->assertSame(0, $untaxedPurchases, "{$untaxedPurchases} seeded purchase orders carry no tax.");

        // 6. Re-running the accounting seeders is a no-op.
        $before = $this->accountingRowCounts();
        $this->reRunTheAccountingSeeders();
        $this->assertSame(
            $before,
            $this->accountingRowCounts(),
            'Re-running the accounting seeders changed row counts, so one of their re-run guards is gone.'
        );

        // ...and the invariants still hold afterwards, not just the counts.
        $unbalancedAfter = $this->unbalancedPostedJournalEntryIds();
        $this->assertSame(
            [],
            $unbalancedAfter,
            'Re-seeding left unbalanced posted entries: #'.implode(', #', $unbalancedAfter)
        );
        $this->assertSame(
            [],
            $this->negativeStockRows(),
            'Re-seed drove stock negative: '.implode(' | ', $this->negativeStockRows())
        );

        // 7. A seeded tax's flags have to match what the tax IS. A PPN row
        // left unflagged escapes the jurisdiction filter, shows up in a
        // tenant's dropdown that should never see it, and can never split
        // VAT on the way through the journal.
        $taxesQuery = DB::table('taxes');
        $this->assertQueryReadsExistingColumns($taxesQuery);
        $misflagged = [];
        foreach ($taxesQuery->get() as $tax) {
            $lower = strtolower($tax->name);
            $expectsPpn = str_starts_with($lower, 'ppn');
            $expectsPph = str_contains($lower, 'pph');

            if ((bool) $tax->is_ppn !== $expectsPpn || (bool) $tax->is_pph !== $expectsPph) {
                $misflagged[] = sprintf(
                    '%s is_ppn=%s is_pph=%s (expected is_ppn=%s is_pph=%s)',
                    $tax->name,
                    var_export((bool) $tax->is_ppn, true),
                    var_export((bool) $tax->is_pph, true),
                    var_export($expectsPpn, true),
                    var_export($expectsPph, true)
                );
            }
        }
        $this->assertSame(
            [],
            $misflagged,
            'Seeded taxes whose flags disagree with their name: '.implode('; ', $misflagged)
        );

        // 8. No payment may exceed what its invoice says it is owed. The
        // credit seeder used to size payments from its OWN copy of the total,
        // which the service had already recomputed, so the seeded invoices
        // were settled against a figure no document ever carried.
        $overpaidQuery = DB::table('payments')
            ->join('sales', function ($join) {
                $join->on('payments.paymentable_id', '=', 'sales.id')
                    ->where('payments.paymentable_type', '=', 'App\Models\Sales');
            })
            ->select('sales.id', 'sales.invoice', 'sales.total', DB::raw('SUM(payments.amount) as paid'))
            ->groupBy('sales.id', 'sales.invoice', 'sales.total')
            ->havingRaw('SUM(payments.amount) > sales.total + 0.005');
        $this->assertQueryReadsExistingColumns($overpaidQuery);

        $overpaid = [];
        foreach ($overpaidQuery->get() as $row) {
            $overpaid[] = sprintf(
                '%s paid %s of %s',
                $row->invoice,
                number_format((float) $row->paid, 2, '.', ''),
                number_format((float) $row->total, 2, '.', '')
            );
        }
        $this->assertSame(
            [],
            $overpaid,
            'Invoices paid more than they total: '.implode('; ', $overpaid)
        );
    }

    /**
     * Every table, so the second run cannot hide a duplicate behind a table
     * this test forgot to look at. Migrations are excluded because they are
     * not seeded data.
     *
     * @return array<string, int>
     */
    private function allTableCounts(): array
    {
        $counts = [];

        foreach (DB::connection()->getSchemaBuilder()->getTables() as $table) {
            $name = $table['name'];
            if (str_starts_with($name, '_') || $name === 'migrations') {
                continue;
            }
            $counts[$name] = DB::table($name)->count();
        }

        return $counts;
    }

    /**
     * Running php artisan db:seed twice has to be a no-op, not a crash and
     * not a second copy of the demo data. Measured rather than assumed: the
     * probe this was written from found one seeder that crashed and six that
     * quietly duplicated, two of them in the general ledger.
     */
    #[Test]
    public function a_second_full_seed_is_a_no_op(): void
    {
        $this->seed(DatabaseSeeder::class);
        $before = $this->allTableCounts();

        $error = null;
        try {
            $this->seed(DatabaseSeeder::class);
        } catch (\Throwable $e) {
            $error = get_class($e).': '.preg_replace('/\s+/', ' ', substr($e->getMessage(), 0, 300));
        }

        $this->assertNull(
            $error,
            'A second db:seed died instead of skipping: '.$error
        );

        $after = $this->allTableCounts();

        $drift = [];
        foreach ($after as $table => $count) {
            $delta = $count - ($before[$table] ?? 0);
            if ($delta !== 0) {
                $drift[] = "{$table} {$delta}";
            }
        }
        foreach ($before as $table => $count) {
            if (! array_key_exists($table, $after)) {
                $drift[] = "{$table} table disappeared";
            }
        }

        $this->assertSame(
            [],
            $drift,
            'A second db:seed changed row counts, so at least one seeder lost its re-run guard: '.implode(' | ', $drift)
        );

        // The counts matching is necessary but not sufficient: a seeder could
        // delete a row and add another. Hold the accounting invariants again
        // so the second run has to leave the ledger exactly as it found it.
        $this->assertSame(
            [],
            $this->unbalancedPostedJournalEntryIds(),
            'A second db:seed left unbalanced posted entries: #'.implode(', #', $this->unbalancedPostedJournalEntryIds())
        );
        $this->assertSame(
            [],
            $this->negativeStockRows(),
            'A second db:seed drove stock negative: '.implode(' | ', $this->negativeStockRows())
        );
    }
}
