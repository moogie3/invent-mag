<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\POItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Tenant;
use App\Services\AccountingService;
use Database\Seeders\AccountSeeder;
use Illuminate\Foundation\testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * A repair for sales whose journals booked no cost for the goods they sold
 * (the point-of-sale path read a product field that does not exist, so COGS
 * and Inventory never moved even though stock left the shelf).
 *
 * The command is dry-run by default and books only what a sale is short of:
 * it recomputes each sale's cost from the same FEFO batches a fresh sale
 * costs from, compares that with what the ledger already holds for the sale,
 * and posts the difference. Sales the books never held, sales already
 * correct, and sales carrying more cost than the recomputation are all left
 * alone.
 */
class BackfillCogsCommandTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private AccountingService $accounting;

    private int $tenantId;

    private int $cogsAccountId;

    private int $inventoryAccountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(AccountSeeder::class);

        $this->tenantId = app('currentTenant')->id;
        $this->cogsAccountId = Account::where('code', '5200-'.$this->tenantId)->first()->id;
        $this->inventoryAccountId = Account::where('code', '1140-'.$this->tenantId)->first()->id;

        $this->tenant->accounting_settings = [
            'cash_account_id' => Account::where('code', '1110-'.$this->tenantId)->first()->id,
            'accounts_receivable_account_id' => Account::where('code', '1130-'.$this->tenantId)->first()->id,
            'sales_revenue_account_id' => Account::where('code', '4100-'.$this->tenantId)->first()->id,
            'cost_of_goods_sold_account_id' => $this->cogsAccountId,
            'inventory_account_id' => $this->inventoryAccountId,
        ];
        $this->tenant->save();

        $this->accounting = app(AccountingService::class);
    }

    #[Test]
    public function it_reports_the_shortfall_without_posting_by_default()
    {
        $sale = $this->saleInTheBooksWithBookedCogs(0.0);

        $this->artisan('sales:backfill-cogs')
            ->expectsOutputToContain('DRY RUN')
            ->assertExitCode(0);

        $this->assertSame(
            1,
            JournalEntry::where('sourceable_type', Sales::class)->where('sourceable_id', $sale->id)->count(),
            'A dry run must not post anything: only the original invoice entry may exist.'
        );
        $this->assertSame(0, JournalEntry::where('description', 'COGS correction for Sale #'.$sale->invoice)->count());
    }

    #[Test]
    public function it_posts_the_missing_cost_when_asked()
    {
        $sale = $this->saleInTheBooksWithBookedCogs(0.0); // 2 units at 50 buying price

        $this->artisan('sales:backfill-cogs', ['--apply' => true])
            ->expectsOutputToContain('1 correction posted')
            ->assertExitCode(0);

        $correction = JournalEntry::where('description', 'COGS correction for Sale #'.$sale->invoice)->sole();
        $this->assertSame($sale->id, $correction->sourceable_id, 'The correction belongs to the sale it repairs.');
        $this->assertSame($this->tenantId, $correction->tenant_id);

        $this->assertEqualsWithDelta(
            100.0,
            (float) $correction->transactions()->where('account_id', $this->cogsAccountId)->where('type', 'debit')->sum('amount'),
            0.005,
            'The correction must debit COGS with what the two units cost.'
        );
        $this->assertEqualsWithDelta(
            100.0,
            (float) $correction->transactions()->where('account_id', $this->inventoryAccountId)->where('type', 'credit')->sum('amount'),
            0.005,
            'The correction must credit Inventory with the same amount.'
        );
        $this->assertSame(
            2,
            $correction->transactions()->count(),
            'The correction must hold exactly one debit and one credit.'
        );
    }

    #[Test]
    public function a_second_run_posts_nothing_more()
    {
        $sale = $this->saleInTheBooksWithBookedCogs(0.0);

        $this->artisan('sales:backfill-cogs', ['--apply' => true])->assertExitCode(0);
        $this->artisan('sales:backfill-cogs', ['--apply' => true])->assertExitCode(0);

        $this->assertSame(
            1,
            JournalEntry::where('description', 'COGS correction for Sale #'.$sale->invoice)->count(),
            'Running the backfill twice must not double the correction.'
        );
    }

    #[Test]
    public function it_leaves_a_sale_alone_that_already_carries_its_cost()
    {
        $sale = $this->saleInTheBooksWithBookedCogs(100.0); // exactly what the units cost

        $this->artisan('sales:backfill-cogs', ['--apply' => true])
            ->expectsOutputToContain('already correct')
            ->assertExitCode(0);

        $this->assertSame(
            1,
            JournalEntry::where('sourceable_type', Sales::class)->where('sourceable_id', $sale->id)->count(),
            'A sale whose books already match the recomputation must not gain a second entry.'
        );
    }

    #[Test]
    public function it_skips_a_sale_the_books_never_held()
    {
        $product = Product::factory()->create(['price' => 50]);
        $sale = Sales::factory()->create();
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $product->id, 'quantity' => 2]);

        $this->artisan('sales:backfill-cogs', ['--apply' => true])->assertExitCode(0);

        $this->assertSame(
            0,
            JournalEntry::where('sourceable_type', Sales::class)->where('sourceable_id', $sale->id)->count(),
            'A sale with no entry in the books has no revenue either; booking cost alone would invent an expense.'
        );
    }

    #[Test]
    public function it_never_corrects_a_sale_whose_entries_are_all_reversed()
    {
        $sale = $this->saleInTheBooksWithBookedCogs(0.0);
        $entry = JournalEntry::where('sourceable_type', Sales::class)->where('sourceable_id', $sale->id)->sole();
        $this->accounting->reverseJournalEntry($entry, 'Netted out by a prior correction');

        $this->artisan('sales:backfill-cogs', ['--apply' => true])->assertExitCode(0);

        $this->assertSame(
            0,
            JournalEntry::where('description', 'COGS correction for Sale #'.$sale->invoice)->count(),
            'A sale fully reversed out of the books must not be written back in.'
        );
    }

    #[Test]
    public function it_prices_the_correction_from_the_batches_not_the_shelf_price()
    {
        // The batch says 400 for 10 units (40 each); the catalogue price of
        // 999 is only what goods cost when no batch covers them. The purchase
        // is pinned discount- and tax-free so the capitalization factor is 1.
        $product = Product::factory()->create(['price' => 999]);
        $purchase = Purchase::factory()->create([
            'discount_total' => 0,
            'tax_rate' => 0,
            'tax_type' => 'none',
        ]);
        POItem::factory()->forPurchase($purchase)->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'price' => 40,
            'discount' => 0,
            'discount_type' => 'fixed',
            'total' => 400,
            'remaining_quantity' => 10,
            'expiry_date' => now()->addYear(),
        ]);

        $sale = Sales::factory()->create();
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $product->id, 'quantity' => 2]);
        $this->accounting->createJournalEntry('Sale Invoice #'.$sale->invoice, now(), [
            ['account_name' => $this->accountName('1130'), 'type' => 'debit', 'amount' => 160],
            ['account_name' => $this->accountName('4100'), 'type' => 'credit', 'amount' => 160],
        ], $sale);

        $this->artisan('sales:backfill-cogs', ['--apply' => true])->assertExitCode(0);

        $correction = JournalEntry::where('description', 'COGS correction for Sale #'.$sale->invoice)->sole();
        $this->assertEqualsWithDelta(
            80.0,
            (float) $correction->transactions()->where('account_id', $this->cogsAccountId)->where('type', 'debit')->sum('amount'),
            0.005,
            'Two units from a 40-per-unit batch cost 80, not 1998 at the catalogue price.'
        );
    }

    #[Test]
    public function it_reports_an_overstated_sale_without_touching_it()
    {
        $sale = $this->saleInTheBooksWithBookedCogs(150.0); // units recompute to 100

        $this->artisan('sales:backfill-cogs', ['--apply' => true])
            ->expectsOutputToContain('overstated')
            ->assertExitCode(0);

        $this->assertSame(
            1,
            JournalEntry::where('sourceable_type', Sales::class)->where('sourceable_id', $sale->id)->count(),
            'A sale carrying more cost than the recomputation is reported, never written down silently.'
        );
        $this->assertSame(0, JournalEntry::where('description', 'COGS correction for Sale #'.$sale->invoice)->count());
    }

    #[Test]
    public function it_can_run_for_a_single_tenant_only()
    {
        $mine = $this->saleInTheBooksWithBookedCogs(0.0);

        $otherTenant = Tenant::create(['name' => 'Other Tenant', 'domain' => 'other.localhost']);
        $theirs = $otherTenant->execute(function () {
            $this->seed(AccountSeeder::class);
            $tid = app('currentTenant')->id;
            $tenant = Tenant::find($tid);
            $tenant->accounting_settings = [
                'cost_of_goods_sold_account_id' => Account::where('code', '5200-'.$tid)->first()->id,
                'inventory_account_id' => Account::where('code', '1140-'.$tid)->first()->id,
            ];
            $tenant->save();

            $product = Product::factory()->create(['price' => 50]);
            $sale = Sales::factory()->create();
            SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $product->id, 'quantity' => 2]);
            app(AccountingService::class)->createJournalEntry('Sale Invoice #'.$sale->invoice, now(), [
                ['account_name' => $this->accountName('1130', $tid), 'type' => 'debit', 'amount' => 160],
                ['account_name' => $this->accountName('4100', $tid), 'type' => 'credit', 'amount' => 160],
            ], $sale);

            return $sale;
        });

        $this->artisan('sales:backfill-cogs', ['--tenant' => $this->tenantId, '--apply' => true])
            ->assertExitCode(0);

        $this->assertSame(
            1,
            JournalEntry::where('description', 'COGS correction for Sale #'.$mine->invoice)->count(),
            'The scoped run must correct its own tenant\'s sale.'
        );

        $theirsCount = $otherTenant->execute(fn () => JournalEntry::where('description', 'COGS correction for Sale #'.$theirs->invoice)
            ->count());
        $this->assertSame(0, $theirsCount, 'A run scoped to one tenant must not post into another tenant\'s books.');
    }

    /**
     * A sale with one line of two units, in the books with whatever COGS the
     * ledger already holds for it.
     */
    private function saleInTheBooksWithBookedCogs(float $bookedCogs): Sales
    {
        $product = Product::factory()->create(['price' => 50, 'selling_price' => 80]);
        $sale = Sales::factory()->create(['total' => 160]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $product->id, 'quantity' => 2]);

        $transactions = [
            ['account_name' => $this->accountName('1130'), 'type' => 'debit', 'amount' => 160],
            ['account_name' => $this->accountName('4100'), 'type' => 'credit', 'amount' => 160],
        ];

        if ($bookedCogs > 0) {
            $transactions[] = ['account_name' => $this->accountName('5200'), 'type' => 'debit', 'amount' => $bookedCogs];
            $transactions[] = ['account_name' => $this->accountName('1140'), 'type' => 'credit', 'amount' => $bookedCogs];
        }

        $this->accounting->createJournalEntry('Sale Invoice #'.$sale->invoice, now(), $transactions, $sale);

        return $sale;
    }

    private function accountName(string $code, ?int $tenantId = null): string
    {
        return Account::where('code', $code.'-'.($tenantId ?? $this->tenantId))->first()->name;
    }
}
