<?php

namespace Tests\Feature\Migrations;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\POItem;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Purchase;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\StockValuationService;
use Database\Seeders\AccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * The two 2026-11-21 data repairs, run the way the pipeline runs them.
 *
 * The COGS half may only touch a figure it can prove wrong, and must stop
 * finding work the moment the ledger sits on the proven edge. The shelf
 * half must close the gap between the Inventory account and the stock on
 * hand, in either direction, and then find nothing left to do. Both must
 * survive being run twice, because a migration re-run and a second seed
 * are ordinary events on this app.
 */
class StockRepairMigrationsTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private const COGS_MIGRATION = '2026_11_21_000001_reverse_provably_wrong_sale_cogs.php';

    private const SHELF_MIGRATION = '2026_11_21_000002_align_gl_inventory_to_stock_on_hand.php';

    private StockValuationService $valuation;

    private int $cogsAccountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(AccountSeeder::class);

        $this->valuation = app(StockValuationService::class);
        $this->cogsAccountId = Account::where('code', '5200-'.$this->tenant->id)->first()->id;
    }

    #[Test]
    public function a_provably_overstated_sale_has_its_cost_pulled_back_to_the_proven_edge(): void
    {
        // Two units at the catalogue price of 50, with no batch in the
        // way: the goods can only have cost 100, so the books' 150 is a
        // provable 50 too much.
        $product = Product::factory()->create(['price' => 50]);
        $sale = $this->aSaleWithBookedCogs(150.0, $product, 2);

        $this->runMigration(self::COGS_MIGRATION);

        $this->assertEqualsWithDelta(
            100.0,
            $this->valuation->bookedCostOfSale($sale, Account::find($this->cogsAccountId)),
            0.005,
            'The provable 50 must come back out of COGS.'
        );
        $this->assertSame(1, $this->correctionsPosted());

        // A re-run finds the ledger sitting on the proven edge.
        $this->runMigration(self::COGS_MIGRATION);
        $this->assertSame(1, $this->correctionsPosted());
    }

    #[Test]
    public function a_sale_inside_the_range_the_history_still_permits_is_left_alone(): void
    {
        // The batch moved after this sale, so at sale time it may have
        // held anything from none to all of its ten units: the truth sits
        // between 400 and 500 and the books' 450 lands inside it.
        $sale = $this->aSaleWhoseBatchMovedAfterIt(450.0);

        $this->runMigration(self::COGS_MIGRATION);

        $this->assertSame(0, $this->correctionsPosted());
        $this->assertEqualsWithDelta(
            450.0,
            $this->valuation->bookedCostOfSale($sale, Account::find($this->cogsAccountId)),
            0.005,
            'An unproven figure must not be moved.'
        );
    }

    #[Test]
    public function the_shelf_alignment_closes_the_gap_in_one_entry_and_then_stops(): void
    {
        $shelf = $this->stockWorth(500_000.0);
        $gap = round($shelf - $this->valuation->glInventoryBalance(), 2);

        $this->assertGreaterThan(0, $gap, 'This test needs the shelf ahead of the books.');

        $this->runMigration(self::SHELF_MIGRATION);

        $this->assertEqualsWithDelta(0.0, $this->valuation->drift(), 0.005);
        $this->assertSame(1, $this->alignmentsPosted());

        $this->runMigration(self::SHELF_MIGRATION);
        $this->assertSame(1, $this->alignmentsPosted());
        $this->assertEqualsWithDelta(0.0, $this->valuation->drift(), 0.005);
    }

    #[Test]
    public function both_repairs_leave_every_entry_balanced(): void
    {
        $product = Product::factory()->create(['price' => 50]);
        $this->aSaleWithBookedCogs(150.0, $product, 2);
        $this->stockWorth(500_000.0);

        $this->runMigration(self::COGS_MIGRATION);
        $this->runMigration(self::SHELF_MIGRATION);

        $entries = JournalEntry::whereIn('notes', ['2026-11-21 sale COGS repair', '2026-11-21 shelf alignment'])->get();

        $this->assertCount(2, $entries);

        foreach ($entries as $entry) {
            $debits = $entry->transactions->where('type', 'debit')->sum('amount');
            $credits = $entry->transactions->where('type', 'credit')->sum('amount');

            $this->assertEqualsWithDelta((float) $debits, (float) $credits, 0.005, 'Every posted entry must balance.');
        }
    }

    private function runMigration(string $file): void
    {
        $migration = include base_path('database/migrations/'.$file);
        $migration->up();
    }

    private function correctionsPosted(): int
    {
        return JournalEntry::where('notes', '2026-11-21 sale COGS repair')->count();
    }

    private function alignmentsPosted(): int
    {
        return JournalEntry::where('notes', '2026-11-21 shelf alignment')->count();
    }

    /** A sale of $qty units, in the books with $booked of COGS. */
    private function aSaleWithBookedCogs(float $booked, Product $product, int $qty = 2): Sales
    {
        $sale = Sales::factory()->create(['total' => 160]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $product->id, 'quantity' => $qty]);

        $transactions = [
            ['account_name' => $this->accountName('1130'), 'type' => 'debit', 'amount' => 160],
            ['account_name' => $this->accountName('4100'), 'type' => 'credit', 'amount' => 160],
        ];

        if ($booked > 0) {
            $transactions[] = ['account_name' => $this->accountName('5200'), 'type' => 'debit', 'amount' => $booked];
            $transactions[] = ['account_name' => $this->accountName('1140'), 'type' => 'credit', 'amount' => $booked];
        }

        app(AccountingService::class)->createJournalEntry('Sale Invoice #'.$sale->invoice, now(), $transactions, $sale);

        return $sale;
    }

    /**
     * A sale of ten units whose only batch was touched after it, so the
     * sale-time state of that batch stops being knowable.
     */
    private function aSaleWhoseBatchMovedAfterIt(float $booked): Sales
    {
        $product = Product::factory()->create(['price' => 50]);
        $purchase = Purchase::factory()->create([
            'discount_total' => 0,
            'tax_rate' => 0,
            'tax_type' => 'none',
        ]);

        $batch = POItem::factory()->forPurchase($purchase)->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'price' => 40,
            'discount' => 0,
            'discount_type' => 'fixed',
            'total' => 400,
            'remaining_quantity' => 2,
            'expiry_date' => now()->addYear(),
        ]);

        $sale = $this->aSaleWithBookedCogs($booked, $product, 10);
        $batch->forceFill(['updated_at' => $sale->created_at->copy()->addMinute()])->save();

        return $sale;
    }

    /** One product holding units worth exactly $worth at the buying price. */
    private function stockWorth(float $worth): float
    {
        $warehouse = Warehouse::factory()->create(['is_main' => true]);
        $product = Product::factory()->create(['price' => 1_000]);

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $this->tenant->id],
            ['quantity' => (int) ($worth / 1000)]
        );

        return $worth;
    }

    private function accountName(string $code): string
    {
        return Account::where('code', $code.'-'.$this->tenant->id)->first()->name;
    }
}
