<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\POItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Services\AccountingService;
use App\Services\StockValuationService;
use Database\Seeders\AccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * assessSaleCost() is the gate in front of the one-time COGS repair: it
 * says what the ledger charges a sale, what the current batches say the
 * goods cost, and what the widest range of costs the surviving batch
 * history still permits for the moment the sale was made. The repair may
 * only move a number it can prove, so the verdicts here decide what the
 * migration is allowed to touch.
 *
 * The envelope works from what the data still shows: a batch untouched
 * since the sale can only have held its recorded remaining quantity, a
 * batch moved after the sale may have held anything between none and all
 * of its quantity, a batch created after the sale did not exist, and a
 * product repriced after its sale makes the fallback unknowable.
 */
class SaleCostAssessmentTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private StockValuationService $valuation;

    private int $cogsAccountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(AccountSeeder::class);

        $this->cogsAccountId = Account::where('code', '5200-'.app('currentTenant')->id)->first()->id;
        $this->valuation = app(StockValuationService::class);
    }

    #[Test]
    public function a_sale_the_books_never_held_is_reported_as_missing(): void
    {
        $product = Product::factory()->create(['price' => 50]);
        $sale = Sales::factory()->create();
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $product->id, 'quantity' => 2]);

        $assessment = $this->valuation->assessSaleCost($sale, Account::find($this->cogsAccountId));

        $this->assertFalse($assessment['in_books']);
        $this->assertSame('missing', $assessment['verdict']);
    }

    #[Test]
    public function a_sale_whose_batches_never_moved_collapses_to_one_provable_cost(): void
    {
        // Two units at the catalogue price of 50: no batch touches them,
        // so the envelope has exactly one member and the ledger can be
        // judged against it without any guessing.
        $product = Product::factory()->create(['price' => 50]);
        $sale = $this->aSaleWithBookedCogs(150.0, $product, 2);

        $assessment = $this->valuation->assessSaleCost($sale, Account::find($this->cogsAccountId));

        $this->assertTrue($assessment['in_books']);
        $this->assertEqualsWithDelta(100.0, $assessment['reference'], 0.005);
        $this->assertEqualsWithDelta(100.0, $assessment['min'], 0.005);
        $this->assertEqualsWithDelta(100.0, $assessment['max'], 0.005);
        $this->assertSame('over', $assessment['verdict']);
    }

    #[Test]
    public function a_stable_batch_prices_the_envelope_at_its_recorded_cost(): void
    {
        // The batch says 40 each, factor one, ten units covered. The line
        // takes two, so the reference is 80 and with nothing moved since
        // the sale the envelope is that single number.
        $product = Product::factory()->create(['price' => 999]);
        $this->aBatchFor($product, 40.0, 10, 10);
        $sale = $this->aSaleWithBookedCogs(150.0, $product, 2);

        $assessment = $this->valuation->assessSaleCost($sale, Account::find($this->cogsAccountId));

        $this->assertEqualsWithDelta(80.0, $assessment['reference'], 0.005);
        $this->assertEqualsWithDelta(80.0, $assessment['min'], 0.005);
        $this->assertEqualsWithDelta(80.0, $assessment['max'], 0.005);
        $this->assertSame('over', $assessment['verdict']);
    }

    #[Test]
    public function a_sale_matching_the_reference_is_correct_even_when_batches_moved(): void
    {
        $sale = $this->aSaleWhoseBatchMovedAfterIt(480.0);

        $assessment = $this->valuation->assessSaleCost($sale, Account::find($this->cogsAccountId));

        $this->assertSame('correct', $assessment['verdict']);
    }

    #[Test]
    public function a_cost_inside_the_envelope_of_moved_batches_cannot_be_judged(): void
    {
        // The batch was moved after the sale, so at sale time it may have
        // held anything from none to all of its ten units: the truth for
        // this line sits between 400 (all at the catalogue fallback) and
        // 400 at the batch price is cheaper, 500 at the fallback is dearer.
        // The ledger's 450 lands inside that range, so no proof exists.
        $sale = $this->aSaleWhoseBatchMovedAfterIt(450.0);

        $assessment = $this->valuation->assessSaleCost($sale, Account::find($this->cogsAccountId));

        $this->assertEqualsWithDelta(400.0, $assessment['min'], 0.005);
        $this->assertEqualsWithDelta(500.0, $assessment['max'], 0.005);
        $this->assertEqualsWithDelta(480.0, $assessment['reference'], 0.005);
        $this->assertSame('indeterminate', $assessment['verdict']);
    }

    #[Test]
    public function a_cost_above_everything_the_history_permits_is_provably_overstated(): void
    {
        $sale = $this->aSaleWhoseBatchMovedAfterIt(600.0);

        $assessment = $this->valuation->assessSaleCost($sale, Account::find($this->cogsAccountId));

        $this->assertSame('over', $assessment['verdict']);

        // Only the provable part is moved: down to the dearest the goods
        // can still be shown to have cost, not down to the recompute.
        $this->assertEqualsWithDelta(500.0, $assessment['target'], 0.005);
    }

    #[Test]
    public function a_cost_below_everything_the_history_permits_is_provably_understated(): void
    {
        $sale = $this->aSaleWhoseBatchMovedAfterIt(300.0);

        $assessment = $this->valuation->assessSaleCost($sale, Account::find($this->cogsAccountId));

        $this->assertSame('under', $assessment['verdict']);
        $this->assertEqualsWithDelta(400.0, $assessment['target'], 0.005);
    }

    #[Test]
    public function a_batch_created_after_the_sale_does_not_enter_the_envelope(): void
    {
        // The cheap batch arrived after the sale, so it cannot have been
        // what the sale consumed: the envelope stays at the catalogue
        // fallback of 100 while the current recompute reads the future
        // batch and says 80. The ledger's 150 exceeds every sale-time
        // cost, which is still a proof.
        $product = Product::factory()->create(['price' => 50]);
        $sale = $this->aSaleWithBookedCogs(150.0, $product, 2);
        $batch = $this->aBatchFor($product, 40.0, 10, 10);
        $batch->forceFill([
            'created_at' => $sale->created_at->copy()->addMinute(),
            'updated_at' => $sale->created_at->copy()->addMinute(),
        ])->save();

        $assessment = $this->valuation->assessSaleCost($sale, Account::find($this->cogsAccountId));

        $this->assertEqualsWithDelta(80.0, $assessment['reference'], 0.005);
        $this->assertEqualsWithDelta(100.0, $assessment['min'], 0.005);
        $this->assertEqualsWithDelta(100.0, $assessment['max'], 0.005);
        $this->assertSame('over', $assessment['verdict']);
        $this->assertEqualsWithDelta(100.0, $assessment['target'], 0.005);
    }

    #[Test]
    public function a_product_repriced_after_the_sale_refuses_to_guess(): void
    {
        $product = Product::factory()->create(['price' => 50]);
        $sale = $this->aSaleWithBookedCogs(150.0, $product, 2);
        $product->forceFill(['updated_at' => $sale->created_at->copy()->addMinute()])->save();

        $assessment = $this->valuation->assessSaleCost($sale, Account::find($this->cogsAccountId));

        $this->assertSame('indeterminate', $assessment['verdict']);
    }

    #[Test]
    public function a_dearer_batch_sitting_behind_a_full_earlier_one_is_out_of_reach(): void
    {
        // The first batch holds every unit the line needs at 40 each and
        // was never touched; a dearer batch sits behind it at 90. FIFO can
        // never reach the dear one, so the range collapses onto 80 and the
        // books' 150 is provable. Letting the draw pick the dear batch
        // anyway would have widened the range to 180 and called it
        // unknowable.
        $product = Product::factory()->create(['price' => 999]);
        $this->aBatchFor($product, 40.0, 10, 10, 12);
        $this->aBatchFor($product, 90.0, 10, 10, 24);
        $sale = $this->aSaleWithBookedCogs(150.0, $product, 2);

        $assessment = $this->valuation->assessSaleCost($sale, Account::find($this->cogsAccountId));

        $this->assertEqualsWithDelta(80.0, $assessment['min'], 0.005);
        $this->assertEqualsWithDelta(80.0, $assessment['max'], 0.005);
        $this->assertSame('over', $assessment['verdict']);
        $this->assertEqualsWithDelta(80.0, $assessment['target'], 0.005);
    }

    /** A sale of two (or $qty) units, in the books with $booked of COGS. */
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
     * Ten units on a factor-one batch, of which only $remaining were still
     * on it when the sale happened, then the batch is touched after the
     * sale so its sale-time state stops being knowable.
     */
    private function aSaleWhoseBatchMovedAfterIt(float $booked): Sales
    {
        $product = Product::factory()->create(['price' => 50]);
        $batch = $this->aBatchFor($product, 40.0, 10, 2);
        $sale = $this->aSaleWithBookedCogs($booked, $product, 10);

        $batch->forceFill(['updated_at' => $sale->created_at->copy()->addMinute()])->save();

        return $sale;
    }

    private function aBatchFor(Product $product, float $unitPrice, int $qty, int $remaining, int $expiryMonths = 12): POItem
    {
        $purchase = Purchase::factory()->create([
            'discount_total' => 0,
            'tax_rate' => 0,
            'tax_type' => 'none',
        ]);

        return POItem::factory()->forPurchase($purchase)->create([
            'product_id' => $product->id,
            'quantity' => $qty,
            'price' => $unitPrice,
            'discount' => 0,
            'discount_type' => 'fixed',
            'total' => $unitPrice * $qty,
            'remaining_quantity' => $remaining,
            'expiry_date' => now()->addMonths($expiryMonths),
        ]);
    }

    private function accountName(string $code): string
    {
        return Account::where('code', $code.'-'.app('currentTenant')->id)->first()->name;
    }
}
