<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\POItem;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\ProductService;
use App\Services\PurchaseService;
use App\Services\SalesService;
use App\Services\StockOpnameService;
use App\Services\StockValuationService;
use Database\Seeders\AccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * Properties that must hold no matter which screen writes the stock.
 *
 * Every one of these has already broken once, and each break was invisible
 * until somebody compared the balance sheet with the warehouse. They are
 * pinned here so the next writer (a new document type, a new report, a
 * "quick" adjustment button) has to keep them rather than rediscover them.
 *
 * The suite runs one realistic lifecycle, four different writers deep, and
 * then checks the properties against the result: a discounted purchase, a
 * sale that consumes it, a manual correction from the product screen and a
 * stock opname. Four code paths, one cost basis.
 */
class InventoryInvariantsTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected AccountingService $accountingService;

    protected PurchaseService $purchaseService;

    protected SalesService $salesService;

    protected Customer $customer;

    protected Supplier $supplier;

    protected Warehouse $warehouse;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->user->assignRole('superuser');

        // Supplies every account the builders look up, plus the ppn_/pph_
        // accounts, and writes them into the tenant's accounting_settings.
        $this->seed(AccountSeeder::class);

        $this->accountingService = app(AccountingService::class);
        $this->purchaseService = new PurchaseService($this->accountingService);
        $this->salesService = new SalesService($this->accountingService);

        $this->customer = Customer::factory()->create();
        $this->supplier = Supplier::factory()->create();
        $this->warehouse = Warehouse::factory()->create(['is_main' => true]);

        // AccountSeeder opens the books with 100000 sitting in Inventory,
        // which is the goods the business already owned before this
        // lifecycle. The shelf has to open holding the same thing or the
        // first assertion would be measuring the fixture rather than the
        // writers: 100 units at the 1000 buying price below is that same
        // 100000, standing on the shelf instead of in the ledger.
        //
        // The factory seeds a random stock quantity into the main warehouse,
        // so it is pinned here: every number below is derived from this
        // starting point, not from a coin flip.
        $this->product = Product::factory()->create(['price' => 1000]);

        ProductWarehouse::updateOrCreate(
            ['product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id, 'tenant_id' => $this->product->tenant_id],
            ['quantity' => 100]
        );
    }

    /**
     * The invariant the whole cost-basis work exists for.
     *
     * The purchase journal capitalizes the supplier invoice (order discount
     * off, recoverable input VAT back out), so the goods are worth LESS
     * than their line prices add up to. Every later writer has to hand back
     * that same figure, or the account and the warehouse drift apart and
     * nothing in the app is left that can say so.
     */
    #[Test]
    public function the_ledger_and_the_shelf_agree_after_a_mixed_lifecycle(): void
    {
        $this->runLifecycle();

        $valuation = app(StockValuationService::class);

        $this->assertEqualsWithDelta(
            0,
            (float) $valuation->drift(),
            0.005,
            'After a discounted purchase, a sale, a manual correction and an opname, the Inventory '
            .'account must still hold exactly what the warehouse is worth.'
        );

        // Not vacuously: both sides have to be a real, non-zero number, or
        // a bug that posted nothing at all would satisfy the check above.
        $this->assertGreaterThan(0, $valuation->glInventoryBalance(), 'The books must actually hold inventory value.');
        $this->assertGreaterThan(0, $valuation->valueOfStock(), 'The warehouse must actually hold stock.');
    }

    /**
     * Double-entry means double: every posted entry has to balance, on its
     * own, whatever combination of lines it was built from.
     */
    #[Test]
    public function every_posted_entry_balances(): void
    {
        $this->runLifecycle();

        $entries = JournalEntry::where('status', JournalEntry::STATUS_POSTED)->get();

        $this->assertGreaterThan(0, $entries->count(), 'The lifecycle must have posted entries to check.');

        foreach ($entries as $entry) {
            $debits = (float) $entry->transactions()->where('type', 'debit')->sum('amount');
            $credits = (float) $entry->transactions()->where('type', 'credit')->sum('amount');

            $this->assertEqualsWithDelta(
                $debits,
                $credits,
                0.005,
                "Entry #{$entry->id} ({$entry->description}) debits {$debits} but credits {$credits}."
            );
        }
    }

    /**
     * A batch is a promise about physical goods: it may not claim more
     * units than were received, and it may not go negative when several
     * writers consume it in turn.
     */
    #[Test]
    public function batches_never_claim_more_units_than_they_received(): void
    {
        $this->runLifecycle();

        $batches = POItem::all();
        $this->assertNotEmpty($batches, 'The lifecycle must have left batches behind to check.');

        foreach ($batches as $batch) {
            $remaining = (int) $batch->remaining_quantity;

            $this->assertGreaterThanOrEqual(
                0,
                $remaining,
                "Batch #{$batch->id} claims {$remaining} units left; it cannot be negative."
            );
            $this->assertLessThanOrEqual(
                (int) $batch->quantity,
                $remaining,
                "Batch #{$batch->id} claims {$remaining} left of the {$batch->quantity} it received."
            );
        }
    }

    /**
     * Stock is a physical quantity. Nothing in the app may drive a
     * warehouse row below zero, however many documents touch it.
     */
    #[Test]
    public function warehouse_stock_never_goes_negative(): void
    {
        $this->runLifecycle();

        $negative = DB::table('product_warehouse')->where('quantity', '<', 0)->count();

        $this->assertSame(0, $negative, 'No warehouse row may end up holding negative stock.');
    }

    /**
     * One product, four writers, in the order a real business meets them.
     *
     * The purchase carries an order discount on purpose: it is what makes
     * the capitalized cost (190 a unit) differ from the line price (200),
     * which is the whole reason a single shared cost basis matters.
     */
    private function runLifecycle(): void
    {
        // 1) Ten units at 200 with 100 off the order: the ledger is charged
        //    1900, so each unit is worth 190 rather than the 200 on the line.
        $this->purchaseService->createPurchase([
            'invoice' => 'INV-INVARIANT-001',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'discount_total' => 100,
            'discount_total_type' => 'fixed',
            'products' => json_encode([
                ['product_id' => $this->product->id, 'quantity' => 10, 'price' => 200],
            ]),
        ]);

        // 2) Sell three: 570 leaves Inventory, not the 600 the lines say.
        $this->salesService->createSale([
            'products' => json_encode([
                ['product_id' => $this->product->id, 'quantity' => 3, 'customer_price' => 100, 'discount' => 0, 'discount_type' => 'fixed'],
            ]),
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
        ]);

        // 3) Two damaged in transit, corrected from the product screen.
        (new ProductService)->adjustProductStock(
            $this->product,
            2,
            'decrease',
            'Damaged in transit',
            $this->user->id,
            $this->warehouse->id
        );

        // 4) The shelf is counted short by two: 105 expected (100 opening
        //    plus the ten bought, less the three sold and two damaged),
        //    103 found.
        $opnameService = app(StockOpnameService::class);
        $opname = $opnameService->createOpname($this->warehouse->id, now()->toDateString(), null);
        $opnameService->updateCounts($opname, [$this->product->id => 103]);
        $opnameService->completeOpname($opname);
    }
}
