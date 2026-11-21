<?php

namespace Tests\Unit\Seeders;

use App\Models\JournalEntry;
use App\Models\POItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\SalesReturn;
use Database\Seeders\AccountSeeder;
use Database\Seeders\PurchaseReturnSeeder;
use Database\Seeders\SalesReturnSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * The return seeders bypass the services entirely: raw creates, hand-rolled
 * stock moves, hand-rolled journal lines. Nothing else in the suite runs
 * them, so a seeder that books a Canceled row or drives stock negative
 * would ship unnoticed. These tests hold them to the same invariant the
 * services enforce: a Canceled return holds no stock and no live journal.
 */
class ReturnSeedersTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected Product $product;

    protected int $openingStock = 1000;

    protected int $warehouseId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        // Both seeders book real account codes (4100/1130, 2110/1140), so the
        // chart of accounts has to exist or every journal line fails.
        $this->seed(AccountSeeder::class);

        $this->product = Product::factory()->withStock($this->openingStock)->create();
        $this->warehouseId = $this->product->productWarehouses()->first()->warehouse_id;
    }

    #[Test]
    public function sales_return_seeder_never_moves_stock_or_books_for_a_canceled_row()
    {
        $sale = Sales::factory()->create(['warehouse_id' => $this->warehouseId]);
        // Enough lines that the seeder's cumulative cap still leaves it room
        // to build a full sample. The cap is what stops a return exceeding
        // what the sale shipped, so a fixture of three five-unit lines runs
        // dry after two or three returns and a run could easily draw no
        // Canceled row at all, which is the case this test exists to cover.
        SalesItem::factory()->count(15)->create([
            'sales_id' => $sale->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'customer_price' => $this->product->selling_price,
            'total' => 5 * (float) $this->product->selling_price,
        ]);

        $stockBefore = $this->product->fresh()->stock_quantity;

        $this->seed(SalesReturnSeeder::class);

        $returns = SalesReturn::query()->get();
        $this->assertNotEmpty(
            $returns,
            'The seeder must actually create rows instead of bailing on missing prerequisites.'
        );

        foreach ($returns as $return) {
            $this->assertGreaterThan(0, $return->items()->count(), 'Every seeded return must carry items.');

            $entries = JournalEntry::where('sourceable_type', SalesReturn::class)
                ->where('sourceable_id', $return->id)
                ->count();

            if ($return->status === 'Canceled') {
                $this->assertSame(
                    0,
                    $entries,
                    "Seeded Canceled return #{$return->id} booked a live entry it can never reverse."
                );
            } else {
                $this->assertSame(
                    1,
                    $entries,
                    "Seeded {$return->status} return #{$return->id} must book exactly one entry."
                );
            }
        }

        // The sales seeder only ever hands stock back, so the total cannot
        // drop below what the tenant started with.
        $this->assertGreaterThanOrEqual(
            $stockBefore,
            $this->product->fresh()->stock_quantity
        );
        $this->assertGreaterThanOrEqual(0, $this->product->fresh()->stock_quantity);

        // The "already exist" guard at the top of the seeder: a second run
        // must be a no-op, not a second pile of returns.
        $count = $returns->count();
        $this->seed(SalesReturnSeeder::class);
        $this->assertSame($count, SalesReturn::query()->count());
    }

    #[Test]
    public function purchase_return_seeder_never_moves_stock_or_books_for_a_canceled_row()
    {
        $purchase = Purchase::factory()->create(['warehouse_id' => $this->warehouseId]);
        // Same reasoning as the sales fixture: fifteen five-unit lines give
        // the seeder enough returnable budget to produce a full sample, so a
        // run is very unlikely to draw no Canceled row.
        POItem::factory()->count(15)->create([
            'po_id' => $purchase->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'price' => 50,
            'total' => 250,
            'remaining_quantity' => 5,
        ]);

        $stockBefore = $this->product->fresh()->stock_quantity;

        $this->seed(PurchaseReturnSeeder::class);

        $returns = PurchaseReturn::query()->get();
        $this->assertNotEmpty(
            $returns,
            'The seeder must actually create rows instead of bailing on missing prerequisites.'
        );

        foreach ($returns as $return) {
            $this->assertGreaterThan(0, $return->items()->count(), 'Every seeded return must carry items.');

            $entries = JournalEntry::where('sourceable_type', PurchaseReturn::class)
                ->where('sourceable_id', $return->id)
                ->count();

            if ($return->status === 'Canceled') {
                $this->assertSame(
                    0,
                    $entries,
                    "Seeded Canceled return #{$return->id} booked a live entry it can never reverse."
                );
            } else {
                $this->assertSame(
                    1,
                    $entries,
                    "Seeded {$return->status} return #{$return->id} must book exactly one entry."
                );
            }
        }

        // The purchase seeder only ever takes stock out: it cannot exceed the
        // starting figure, and it must never push the record below zero.
        $stockAfter = $this->product->fresh()->stock_quantity;
        $this->assertLessThanOrEqual($stockBefore, $stockAfter);
        $this->assertGreaterThanOrEqual(0, $stockAfter);

        $count = $returns->count();
        $this->seed(PurchaseReturnSeeder::class);
        $this->assertSame($count, PurchaseReturn::query()->count());
    }
}
