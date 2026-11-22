<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\StockValuationService;
use Database\Seeders\AccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * alignGlToShelf() closes the one gap the demo seeders leave behind: four
 * of them move stock without ever journaling it, so a seeded database
 * holds goods the Inventory account has never heard of. The tests below
 * pin both directions of that repair, the Opening Balance Equity account
 * it credits (or debits), and the fact that a second run finds nothing
 * left to do.
 */
class GlShelfAlignmentTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private StockValuationService $valuation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(AccountSeeder::class);
        $this->valuation = app(StockValuationService::class);
    }

    #[Test]
    public function stock_the_books_have_never_heard_of_is_booked_against_opening_balance_equity(): void
    {
        $shelf = $this->stockWorth(500_000.0);
        $gap = round($shelf - $this->valuation->glInventoryBalance(), 2);

        $this->assertGreaterThan(0, $gap, 'This test needs the shelf ahead of the books.');

        $posted = $this->valuation->alignGlToShelf('Alignment of stock on hand');

        $this->assertEqualsWithDelta($gap, $posted, 0.005);
        $this->assertEqualsWithDelta($shelf, $this->valuation->glInventoryBalance(), 0.005);
        $this->assertEqualsWithDelta(0.0, $this->valuation->drift(), 0.005);
        $this->assertDebitToInventoryCreditToEquity('Alignment of stock on hand', $gap);

        // A second run measures the same two figures and posts nothing.
        $this->assertSame(0.0, $this->valuation->alignGlToShelf('Alignment of stock on hand'));
        $this->assertSame(1, JournalEntry::where('description', 'Alignment of stock on hand')->count());
    }

    #[Test]
    public function stock_the_books_claim_but_the_shelf_does_not_hold_is_taken_back_out(): void
    {
        $shelf = $this->stockWorth(500_000.0);
        $this->anInventoryDebitOf(1_000_000.0);
        $gap = round($shelf - $this->valuation->glInventoryBalance(), 2);

        $this->assertLessThan(0, $gap, 'This test needs the books ahead of the shelf.');

        $posted = $this->valuation->alignGlToShelf('Alignment of stock on hand');

        $this->assertEqualsWithDelta($gap, $posted, 0.005);
        $this->assertEqualsWithDelta($shelf, $this->valuation->glInventoryBalance(), 0.005);
        $this->assertEqualsWithDelta(0.0, $this->valuation->drift(), 0.005);
        $this->assertDebitToInventoryCreditToEquity('Alignment of stock on hand', $gap);

        $this->assertSame(0.0, $this->valuation->alignGlToShelf('Alignment of stock on hand'));
        $this->assertSame(1, JournalEntry::where('description', 'Alignment of stock on hand')->count());
    }

    #[Test]
    public function a_tenant_whose_accounting_is_not_configured_is_left_alone(): void
    {
        $this->stockWorth(500_000.0);
        $before = JournalEntry::count();

        $this->tenant->accounting_settings = [];
        $this->tenant->save();

        $this->assertNull($this->valuation->alignGlToShelf('Alignment of stock on hand'));
        $this->assertSame($before, JournalEntry::count());
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

    /** Push the Inventory account above any plausible shelf value. */
    private function anInventoryDebitOf(float $amount): void
    {
        app(AccountingService::class)->createJournalEntry('Test opening stock', now(), [
            ['account_name' => $this->account('1140')->name, 'type' => 'debit', 'amount' => $amount],
            ['account_name' => $this->account('3010')->name, 'type' => 'credit', 'amount' => $amount],
        ]);
    }

    /**
     * The entry must move Inventory by the gap and take the other side
     * on Opening Balance Equity, in the direction the gap demands.
     */
    private function assertDebitToInventoryCreditToEquity(string $description, float $gap): void
    {
        $entry = JournalEntry::where('description', $description)->first();

        $this->assertNotNull($entry, 'The alignment must post a journal entry.');

        $lines = $entry->transactions->mapWithKeys(function ($transaction) {
            return [Account::find($transaction->account_id)->code => $transaction];
        });

        $inventory = $lines['1140-'.$this->tenant->id];
        $equity = $lines['3010-'.$this->tenant->id];

        $expectedType = $gap > 0 ? 'debit' : 'credit';
        $otherType = $gap > 0 ? 'credit' : 'debit';

        $this->assertSame($expectedType, $inventory->type);
        $this->assertSame($otherType, $equity->type);
        $this->assertEqualsWithDelta(abs($gap), (float) $inventory->amount, 0.005);
        $this->assertEqualsWithDelta(abs($gap), (float) $equity->amount, 0.005);
    }

    private function account(string $code): Account
    {
        return Account::where('code', $code.'-'.$this->tenant->id)->first();
    }
}
