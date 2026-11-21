<?php

namespace Tests\Unit\Services;

use App\Models\JournalEntry;
use App\Models\POItem;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Purchase;
use App\Models\Tenant;
use App\Models\Warehouse;
use App\Services\FinancialHealthService;
use App\Services\ProductService;
use App\Services\StockValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class StockValuationServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected StockValuationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->service = app(StockValuationService::class);
    }

    /**
     * Stock on hand is worth what the batches cost, plus what no batch
     * covers at today's buying price. Nothing in the app computed this, so
     * there was no way to say what the warehouse held, let alone compare it
     * with the Inventory account.
     */
    #[Test]
    public function stock_value_counts_batches_then_unbatched_units_at_buying_price(): void
    {
        $warehouse = Warehouse::factory()->create(['is_main' => true]);
        $product = Product::factory()->create(['price' => 1000]);

        // Exactly 10 units on the shelf: the factory's random stock lands
        // on this same main-warehouse row and is overwritten here.
        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $this->tenant->id],
            ['quantity' => 10]
        );

        // 4 of the 10 units are still on their purchase batch at 200 each.
        $purchase = Purchase::factory()->create([
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_type' => 'none',
            'tax_rate' => 0,
            'total_tax' => 0,
            'ppn_enabled' => false,
            'ppn_amount' => 0,
        ]);

        POItem::factory()->forPurchase($purchase)->create([
            'product_id' => $product->id,
            'quantity' => 4,
            'remaining_quantity' => 4,
            'price' => 200,
            'discount' => 0,
            'discount_type' => 'fixed',
            'total' => 800,
            'expiry_date' => null,
        ]);

        // 4 x 200 on the batch + 6 x 1000 unbatched = 6800.
        $this->assertEqualsWithDelta(
            6800,
            $this->service->valueOfStock(),
            0.005,
            'Stock value must cost the batched units at batch cost and the rest at the buying price.'
        );

        $this->assertEqualsWithDelta(6800, $this->service->valueOfProduct($product->id), 0.005);
    }

    /**
     * A raw DB::table() never sees the Eloquent tenant scope, so these
     * costing queries have to reapply it themselves. A batch row whose
     * po_id points at another tenant's purchase must not have that
     * purchase's order discount applied to it: one tenant's stock value
     * would be computed from another tenant's private order terms.
     */
    #[Test]
    public function capitalization_ignores_a_purchase_owned_by_another_tenant(): void
    {
        $foreignPo = $this->aForeignPurchaseWithA50PercentDiscount();

        // A line of 10000 over 10 units: 1000 each before capitalization.
        // The foreign purchase's 50% order discount would halve that to 500
        // if the raw query read it; with the purchase out of reach the
        // factor falls back to 1.0 and the line stays at 1000.
        $line = (object) ['po_id' => $foreignPo->id, 'quantity' => 10, 'total' => 10000, 'price' => 1000];

        $this->assertEqualsWithDelta(
            1000.0,
            $this->service->unitCost($line),
            0.005,
            'A line must not be costed from another tenant\'s order discount.'
        );
    }

    /**
     * Same isolation through the warm path: valueOfStock() hands every
     * batch's po_id to warmFactors(), which looked the purchase up with a
     * raw query. The batch itself belongs to this tenant, so it is valued;
     * the purchase it claims to come from does not, so its discount must
     * not touch the warehouse figure.
     */
    #[Test]
    public function warehouse_value_ignores_a_purchase_owned_by_another_tenant(): void
    {
        // Main warehouse first: every factory-created product parks its
        // random stock on the main row, which is overwritten to exactly 10
        // below. Creating it after the helper would leave two "main"
        // warehouses and split the product's stock across both.
        $warehouse = Warehouse::factory()->create(['is_main' => true]);
        $product = Product::factory()->create(['price' => 1000]);

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $this->tenant->id],
            ['quantity' => 10]
        );

        $foreignPo = $this->aForeignPurchaseWithA50PercentDiscount();

        // This tenant's batch (so the warehouse query includes it) pointing
        // at the other tenant's purchase (the cross-reference under test).
        POItem::factory()->forPurchase($foreignPo)->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'price' => 1000,
            'discount' => 0,
            'discount_type' => 'fixed',
            'total' => 10000,
            'expiry_date' => null,
        ]);

        // 10 units at the raw line cost of 1000 each. The foreign 50%
        // discount would turn that into 5000 if it leaked in.
        $this->assertEqualsWithDelta(
            10000,
            $this->service->valueOfStock(),
            0.005,
            'The warehouse value must not be halved by another tenant\'s order discount.'
        );
    }

    /**
     * A purchase of a second tenant carrying a 50% order discount, with a
     * line under it so the discount has a subtotal to work on (factorFor()
     * returns 1.0 for an empty subtotal, which would mask the leak). The
     * po_id of this tenant's own batch rows is then pointed at it, the
     * cross-reference the raw costing queries must refuse to follow.
     */
    private function aForeignPurchaseWithA50PercentDiscount(): Purchase
    {
        $otherTenant = Tenant::create(['name' => 'Other Tenant', 'domain' => 'other.localhost']);

        $foreignPo = Purchase::factory()->create([
            'discount_total' => 50,
            'discount_total_type' => 'percentage',
            'tax_type' => 'none',
            'tax_rate' => 0,
            'total_tax' => 0,
            'ppn_enabled' => false,
            'ppn_amount' => 0,
        ]);

        // tenant_id is guarded, so mass assignment cannot move it; the raw
        // update does, without tripping any scope on the way.
        DB::table('po')->where('id', $foreignPo->id)->update(['tenant_id' => $otherTenant->id]);

        POItem::factory()->forPurchase($foreignPo)->create([
            // Its own product at zero stock: the factory default would mint
            // a random product holding random units, which valueOfStock()
            // would then rightly include and drown the figure under test.
            'product_id' => Product::factory()->withStock(0)->create()->id,
            'quantity' => 10,
            'remaining_quantity' => 0,
            'price' => 1000,
            'discount' => 0,
            'discount_type' => 'fixed',
            'total' => 10000,
            'expiry_date' => null,
        ]);

        return $foreignPo;
    }

    /**
     * The whole point of valuing stock: the ledger says one number, the
     * shelf says another, and somebody has to be told. Books that carry
     * 500000 of inventory while the warehouse holds none is exactly the
     * shape a manual correction that never reached the accounts leaves.
     */
    #[Test]
    public function drift_reports_the_gap_between_the_ledger_and_the_shelf(): void
    {
        $this->bookInventory(500000);

        $this->assertEqualsWithDelta(0, $this->service->valueOfStock(), 0.005);
        $this->assertEqualsWithDelta(500000, (float) $this->service->glInventoryBalance(), 0.005);
        $this->assertEqualsWithDelta(
            500000,
            (float) $this->service->drift(),
            0.005,
            'Drift must be the Inventory account minus what the stock on the shelf is worth.'
        );
    }

    /**
     * A drift nobody can see fixes nothing: the Financial Health page has
     * to carry the stock value as a figure and call the disagreement out.
     */
    #[Test]
    public function financial_health_carries_the_stock_value_and_flags_the_drift(): void
    {
        $this->bookInventory(500000);

        $overview = app(FinancialHealthService::class)->getOverview();

        $this->assertArrayHasKey('stock_value', $overview, 'The health payload must expose what the shelf holds.');
        $this->assertArrayHasKey('inventory_drift', $overview);
        $this->assertEqualsWithDelta(0, $overview['stock_value'], 0.005);
        $this->assertEqualsWithDelta(500000, $overview['inventory_drift'], 0.005);

        $indicator = collect($overview['indicators'])->firstWhere('key', 'stock_value');
        $this->assertNotNull($indicator, 'The health page must carry a stock value indicator.');
        $this->assertSame(
            'danger',
            $indicator['status'],
            'Books that do not match the shelf must read as danger, not as steady.'
        );

        $flag = collect($overview['attention'])->first(
            fn ($item) => abs((float) $item['amount'] - 500000) < 0.01
        );
        $this->assertNotNull($flag, 'The attention list must name the disagreement and its amount.');
    }

    /**
     * The point of one cost basis: the ledger and the shelf must tell the
     * same story about the same goods.
     *
     * A purchase's Inventory debit is the subtotal less the order discount
     * (and less any recoverable input VAT), so the goods are worth LESS
     * than their line prices add up to. Ten units at 200 with 100 off the
     * order leaves 1900 in the account, which is 190 a unit. Costing at the
     * raw 200 would make the shelf claim 2000 for goods the books only ever
     * paid 1900 for, and every later sale would credit back more Inventory
     * than was debited.
     */
    #[Test]
    public function ledger_and_shelf_agree_on_a_discounted_purchase(): void
    {
        $warehouse = Warehouse::factory()->create(['is_main' => true]);
        $product = Product::factory()->create(['price' => 1000]);

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $this->tenant->id],
            ['quantity' => 10]
        );

        // 10 units at 200 = 2000 on the lines, 100 off the order, no tax.
        $purchase = Purchase::factory()->create([
            'discount_total' => 100,
            'discount_total_type' => 'fixed',
            'tax_type' => 'none',
            'tax_rate' => 0,
            'total_tax' => 0,
            'ppn_enabled' => false,
            'ppn_amount' => 0,
        ]);

        POItem::factory()->forPurchase($purchase)->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'price' => 200,
            'discount' => 0,
            'discount_type' => 'fixed',
            'total' => 2000,
            'expiry_date' => null,
        ]);

        // What the purchase journal actually debited: 2000 less the 100
        // order discount, with no tax to add back.
        $this->bookEntry('inventory_account_id', 'accounts_payable_account_id', 1900);

        $this->assertEqualsWithDelta(
            0,
            (float) $this->service->drift(),
            0.005,
            'Ten units at the capitalized 190 must equal the 1900 the ledger holds.'
        );

        (new ProductService)->adjustProductStock($product, 3, 'decrease', 'Damaged in transit', $this->user->id, $warehouse->id);

        $inventoryId = $this->tenant->accounting_settings['inventory_account_id'];
        $credit = JournalEntry::whereHas('transactions', function ($query) use ($inventoryId) {
            $query->where('account_id', $inventoryId)->where('type', 'credit');
        })->latest('id')->first();

        $this->assertNotNull($credit, 'The manual decrease must reach the Inventory account.');
        $this->assertEqualsWithDelta(
            570,
            (float) $credit->transactions->firstWhere('type', 'credit')->amount,
            0.005,
            'Three units must leave at 190 each, the figure the Inventory debit was built from.'
        );

        $this->assertEqualsWithDelta(
            0,
            (float) $this->service->drift(),
            0.005,
            'After the correction both records must still say 1330.'
        );
    }

    /**
     * Insert a posted Inventory debit the old-fashioned way, the way a
     * correction that skipped the accounts would leave the books.
     */
    private function bookInventory(float $amount): void
    {
        $this->bookEntry('inventory_account_id', 'cash_account_id', $amount);
    }

    /**
     * A balanced posted entry straight into the ledger, for fixtures that
     * need the books to hold a number without running a whole document
     * through its service.
     */
    private function bookEntry(string $debitSetting, string $creditSetting, float $amount): void
    {
        $tenant = app('currentTenant');

        $entry = JournalEntry::create([
            'date' => now()->toDateString(),
            'description' => 'fixture entry',
            'status' => JournalEntry::STATUS_POSTED,
        ]);

        DB::table('transactions')->insert([
            [
                'journal_entry_id' => $entry->id,
                'tenant_id' => $tenant->id,
                'account_id' => $tenant->getAccountingSetting($debitSetting),
                'type' => 'debit',
                'amount' => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_entry_id' => $entry->id,
                'tenant_id' => $tenant->id,
                'account_id' => $tenant->getAccountingSetting($creditSetting),
                'type' => 'credit',
                'amount' => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
