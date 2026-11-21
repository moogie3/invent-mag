<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Purchase;
use App\Models\Sales;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\PosService;
use App\Services\PurchaseService;
use App\Services\SalesService;
use Database\Seeders\AccountSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * Editing a posted document has to move the ledger with it.
 *
 * updateSale() and updatePurchase() used to rewrite the document and post
 * nothing at all, so the books kept the amounts the document was created with
 * for ever: change a Rp 200 invoice to Rp 500 and revenue, receivables and
 * input VAT all still said Rp 200. These tests pin the behaviour from three
 * directions, the net effect on the ledger after an edit, after a second edit,
 * and after a delete, plus the rule that no entry may ever be reversed twice.
 */
class DocumentEditJournalTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected SalesService $salesService;

    protected PurchaseService $purchaseService;

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

        $accountingService = app(AccountingService::class);
        $this->salesService = new SalesService($accountingService);
        $this->purchaseService = new PurchaseService($accountingService);

        $this->customer = Customer::factory()->create();
        $this->supplier = Supplier::factory()->create();
        $this->warehouse = Warehouse::factory()->create(['is_main' => true]);
        // price 50 is what COGS is costed at: there are no purchase batches in
        // these tests, so the fallback "quantity x product price" branch runs.
        $this->product = Product::factory()->create(['price' => 50]);

        ProductWarehouse::updateOrCreate(
            ['product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id, 'tenant_id' => $this->product->tenant_id],
            ['quantity' => 1000]
        );
    }

    #[Test]
    public function editing_a_sale_moves_the_ledger_to_the_new_totals()
    {
        $sale = $this->createSale(2);

        $this->assertLedgerFor($sale, [
            '1130' => 200.0,   // Dr AR
            '4100' => -200.0,  // Cr Revenue
            '5200' => 100.0,   // Dr COGS (2 x 50)
            '1140' => -100.0,  // Cr Inventory
        ], 'after create');

        $this->updateSaleQuantity($sale, 5);

        $this->assertLedgerFor($sale, [
            '1130' => 500.0,
            '4100' => -500.0,
            '5200' => 250.0,   // 5 x 50
            '1140' => -250.0,
        ], 'after edit: the old entry must be gone, not added to');
    }

    #[Test]
    public function a_second_edit_leaves_only_the_latest_entry()
    {
        $sale = $this->createSale(2);
        $this->updateSaleQuantity($sale, 5);
        $this->updateSaleQuantity($sale, 3);

        $this->assertLedgerFor($sale, [
            '1130' => 300.0,
            '4100' => -300.0,
            '5200' => 150.0,
            '1140' => -150.0,
        ], 'after two edits: three versions must not sum to 1000');
    }

    #[Test]
    public function deleting_an_edited_sale_leaves_nothing_behind()
    {
        $sale = $this->createSale(2);
        $this->updateSaleQuantity($sale, 5);

        $this->salesService->deleteSale($sale);

        $this->assertLedgerFor($sale, [
            '1130' => 0.0,
            '4100' => 0.0,
            '5200' => 0.0,
            '1140' => 0.0,
        ], 'after delete of a twice-posted sale');
    }

    #[Test]
    public function editing_a_purchase_moves_the_ledger_to_the_new_totals()
    {
        $purchase = $this->createPurchase(2);

        $this->assertLedgerFor($purchase, [
            '1140' => 200.0,  // Dr Inventory
            '2110' => -200.0, // Cr Accounts Payable
        ], 'after create');

        $this->updatePurchaseQuantity($purchase, 5);

        $this->assertLedgerFor($purchase, [
            '1140' => 500.0,
            '2110' => -500.0,
        ], 'after edit: the old entry must be gone, not added to');
    }

    #[Test]
    public function a_second_purchase_edit_leaves_only_the_latest_entry()
    {
        $purchase = $this->createPurchase(2);
        $this->updatePurchaseQuantity($purchase, 5);
        $this->updatePurchaseQuantity($purchase, 3);

        $this->assertLedgerFor($purchase, [
            '1140' => 300.0,
            '2110' => -300.0,
        ], 'after two edits: three versions must not sum to 1000');
    }

    #[Test]
    public function deleting_an_edited_purchase_leaves_nothing_behind()
    {
        $purchase = $this->createPurchase(2);
        $this->updatePurchaseQuantity($purchase, 5);

        $this->purchaseService->deletePurchase($purchase);

        $this->assertLedgerFor($purchase, [
            '1140' => 0.0,
            '2110' => 0.0,
        ], 'after delete of a twice-posted purchase');
    }

    /**
     * A purchase whose net lands exactly on a half cent must still post.
     *
     * The inventory debit was built from the float the edit had just
     * computed, but the payable on the credit side was read back off the row
     * after a refresh(). PDO binds that float as a string at precision=14, so
     * 143733.19499999998 comes back as 143733.19500000001: the two now sit on
     * opposite sides of the same half cent, round() sends them to different
     * cents, and validateTransactions() refuses an entry that balances to any
     * eye. ProductFactory rolls its price at random, so this only surfaced
     * once in a while; pinning the price makes the boundary land every time.
     */
    #[Test]
    public function editing_a_purchase_whose_total_lands_on_a_half_cent_still_posts(): void
    {
        $product = Product::factory()->create(['price' => 50432.70]);
        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $this->warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 0]
        );

        $data = $this->halfCentPurchaseData($product);
        $purchase = $this->purchaseService->createPurchase($data);

        // 50432.70 x 3 = 151298.10, less the 5% document discount = 143733.195.
        // That is the half-cent boundary. The row may carry it as .195 or as
        // the .20 a decimal(15,2) column rounds it to, so allow half a cent.
        $this->assertEqualsWithDelta(143733.195, (float) $purchase->fresh()->total, 0.005);

        $this->purchaseService->updatePurchase($purchase, $data);

        // Transactions cast amount to decimal:2, so both sides land on the
        // same cent here. What matters is that they land on the SAME one.
        $this->assertLedgerFor($purchase->fresh(), [
            '1140' => 143733.20,
            '2110' => -143733.20,
        ], 'after editing onto a half-cent total');
    }

    /**
     * The net of a 5% document discount on 3 x 50432.70 is exactly
     * 143733.195, which is the half-cent boundary the update path trips on.
     */
    private function halfCentPurchaseData(Product $product): array
    {
        return [
            'invoice' => 'HALF-CENT-001',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'discount_total' => 5,
            'discount_total_type' => 'percentage',
            'products' => json_encode([
                ['product_id' => $product->id, 'quantity' => 3, 'price' => 50432.70],
            ]),
        ];
    }

    /**
     * A POS sale used to refuse edits outright, which left deleting as the
     * only correction left for a mis-rung transaction. Edits now reverse and
     * repost its entries too, so the delete has to stay safe on top of that:
     * its journal is several entries rather than one (invoice, cash payment,
     * and a withholding entry when PPh applies), all sourced to the sale, and
     * each must be reversed exactly once: miss one and the cash receipt
     * survives the sale, reverse one twice and the ledger holds a stray
     * opposite.
     */
    #[Test]
    public function deleting_a_pos_sale_reverses_all_of_its_entries_exactly_once()
    {
        $sale = (new PosService(app(AccountingService::class)))->createSale([
            'transaction_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'products' => json_encode([
                ['id' => $this->product->id, 'quantity' => 2, 'price' => 100, 'total' => 200],
            ]),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'grand_total' => 200,
            'payment_method' => 'Cash',
            'amount_received' => 200,
            'change_amount' => 0,
        ]);

        // Invoice plus cash payment. A withholding entry joins them only
        // when PPh applies, which it does not here.
        $this->assertSame(
            2,
            JournalEntry::where('sourceable_type', Sales::class)->where('sourceable_id', $sale->id)->count()
        );

        $this->salesService->deleteSale($sale);

        $this->assertLedgerFor($sale, [
            '1130' => 0.0,   // AR: invoiced, then paid, then both reversed
            '4100' => 0.0,   // Revenue
            '1110' => 0.0,   // Cash: the receipt must go with the sale
            '5200' => 0.0,   // COGS
            '1140' => 0.0,   // Inventory
        ], 'after delete of a POS sale');
    }

    /**
     * Net effect this document has on the ledger: every entry sourced to it,
     * plus the reversals of those entries, signed debit positive.
     *
     * Reversals are found through reversed_entry_id rather than by sourceable,
     * because reverseJournalEntry() posts them with no source document. They
     * are ADDED, not subtracted: a reversal already carries the opposite
     * debits and credits, so subtracting one would double the original instead
     * of cancelling it. That also makes this the place a double reversal shows
     * up, since two reversals against one entry cancel it twice.
     *
     * @return array<string, float> account code => signed net
     */
    private function ledgerNet(Model $document): array
    {
        $codes = Account::pluck('code', 'id')->all();
        $net = [];

        $entries = JournalEntry::where('sourceable_type', get_class($document))
            ->where('sourceable_id', $document->id)
            ->get();

        $this->assertNotEmpty($entries, 'The document posted no journal entry at all.');

        foreach ($entries as $entry) {
            $reversals = JournalEntry::where('reversed_entry_id', $entry->id)->get();

            $this->assertLessThanOrEqual(
                1,
                $reversals->count(),
                "Entry #{$entry->id} ({$entry->description}) was reversed {$reversals->count()} times. An entry may only ever be reversed once."
            );

            $this->applyTransactions($net, $entry, $codes);
            foreach ($reversals as $reversal) {
                $this->applyTransactions($net, $reversal, $codes);
            }
        }

        return $net;
    }

    /**
     * @param  array<string, float>  $net
     * @param  array<int, string>  $codes
     */
    private function applyTransactions(array &$net, JournalEntry $entry, array $codes): void
    {
        foreach ($entry->transactions as $transaction) {
            $code = $codes[$transaction->account_id] ?? "unknown:{$transaction->account_id}";
            $sign = $transaction->type === 'debit' ? 1 : -1;
            $net[$code] = ($net[$code] ?? 0.0) + $sign * (float) $transaction->amount;
        }
    }

    /**
     * @param  array<string, float>  $expected  account code => signed net, keyed
     *                                          by the bare SAK code; the seeder
     *                                          suffixes codes with the tenant id
     */
    private function assertLedgerFor(Model $document, array $expected, string $stage): void
    {
        $net = $this->ledgerNet($document);

        foreach ($expected as $code => $amount) {
            $resolved = $code.'-'.app('currentTenant')->id;

            $this->assertEqualsWithDelta(
                $amount,
                $net[$resolved] ?? 0.0,
                0.005,
                sprintf(
                    '%s: account %s net was %.2f, expected %.2f (whole ledger: %s)',
                    $stage,
                    $resolved,
                    $net[$resolved] ?? 0.0,
                    $amount,
                    json_encode(array_map(fn ($v) => round($v, 2), $net))
                )
            );
        }

        $this->assertEqualsWithDelta(
            0.0,
            array_sum($net),
            0.005,
            "{$stage}: the document's own entries do not balance to zero: ".json_encode($net)
        );
    }

    private function saleData(int $quantity): array
    {
        return [
            'products' => json_encode([
                ['product_id' => $this->product->id, 'quantity' => $quantity, 'customer_price' => 100, 'discount' => 0, 'discount_type' => 'fixed'],
            ]),
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
        ];
    }

    private function createSale(int $quantity): Sales
    {
        return $this->salesService->createSale($this->saleData($quantity));
    }

    private function updateSaleQuantity(Sales $sale, int $quantity): void
    {
        $this->salesService->updateSale($sale, $this->saleData($quantity));
    }

    private function purchaseData(int $quantity): array
    {
        return [
            'invoice' => 'EDIT-JRNL-001',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'products' => json_encode([
                ['product_id' => $this->product->id, 'quantity' => $quantity, 'price' => 100],
            ]),
        ];
    }

    private function createPurchase(int $quantity): Purchase
    {
        return $this->purchaseService->createPurchase($this->purchaseData($quantity));
    }

    private function updatePurchaseQuantity(Purchase $purchase, int $quantity): void
    {
        $this->purchaseService->updatePurchase($purchase, $this->purchaseData($quantity));
    }
}
