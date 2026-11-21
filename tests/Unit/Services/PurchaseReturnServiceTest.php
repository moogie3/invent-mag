<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\POItem;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\PurchaseReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class PurchaseReturnServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected User $user;

    protected Purchase $purchase;

    protected Product $product;

    protected PurchaseReturnService $purchaseReturnService;

    protected $accountingServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant(); // Creates $this->tenant and $this->user, and calls actingAs
        $this->user->assignRole('superuser'); // Ensure the user has permissions for services

        $this->product = Product::factory()->withStock(100)->create();
        $this->purchase = Purchase::factory()->create();

        // Sync product warehouse to match purchase warehouse for consistent testing
        $productWarehouse = $this->product->productWarehouses()->first();
        if ($productWarehouse) {
            $productWarehouse->update(['warehouse_id' => $this->purchase->warehouse_id]);
        }

        // Mock AccountingService
        $this->accountingServiceMock = Mockery::mock(AccountingService::class);
        $this->purchaseReturnService = new PurchaseReturnService($this->accountingServiceMock);
    }

    /**
     * A purchase line to return against: returns are capped at what the PO
     * actually ordered, so a test that hands goods back has to order them
     * first. 10 units covers every quantity these tests use.
     */
    private function givePurchaseLine(int $quantity = 10): POItem
    {
        return POItem::firstOrCreate(
            ['po_id' => $this->purchase->id, 'product_id' => $this->product->id],
            [
                'quantity' => $quantity,
                'remaining_quantity' => $quantity,
                'price' => 100,
                'total' => 1000,
                'discount' => 0,
                'discount_type' => 'fixed',
            ]
        );
    }

    #[Test]
    public function test_it_can_get_purchase_return_index_data()
    {
        PurchaseReturn::factory()->count(5)->create();

        $data = $this->purchaseReturnService->getPurchaseReturnIndexData([], 10);

        $this->assertArrayHasKey('returns', $data);
        $this->assertArrayHasKey('total_returns', $data);
        $this->assertArrayHasKey('total_amount', $data);
        $this->assertEquals(5, $data['total_returns']);
    }

    #[Test]
    public function test_it_reverses_ppn_masukan_and_pph_payable_from_original_journal()
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        $ppnMasukanAccount = Account::create([
            'name' => 'PPN Masukan '.$this->tenant->id,
            'code' => '1160-'.$this->tenant->id,
            'type' => 'asset',
            'tenant_id' => $this->tenant->id,
        ]);
        $pphPayableAccount = Account::create([
            'name' => 'PPh Payable '.$this->tenant->id,
            'code' => '2140-'.$this->tenant->id,
            'type' => 'liability',
            'tenant_id' => $this->tenant->id,
        ]);
        $this->tenant->update([
            'accounting_settings' => array_merge(
                $this->tenant->accounting_settings ?? [],
                [
                    'ppn_masukan_account_id' => $ppnMasukanAccount->id,
                    'pph_payable_account_id' => $pphPayableAccount->id,
                ]
            ),
        ]);

        // Create a stock record so the service can decrement
        ProductWarehouse::firstOrCreate(
            [
                'product_id' => $this->product->id,
                'warehouse_id' => $this->purchase->warehouse_id,
                'tenant_id' => $this->purchase->tenant_id,
            ],
            ['quantity' => 50]
        );

        POItem::firstOrCreate(
            ['po_id' => $this->purchase->id, 'product_id' => $this->product->id],
            [
                'quantity' => 10,
                'remaining_quantity' => 10,
                'price' => 100,
                'total' => 1000,
                'discount' => 0,
                'discount_type' => 'fixed',
            ]
        );

        // Original purchase journal as PurchaseService posts it:
        // subtotal 200 + PPN 11% (22) − PPh 2% (4) → AP 218.
        //   Dr Inventory 200 / Dr PPN Masukan 22 / Cr PPh Payable 4 / Cr AP 218
        $settings = $this->tenant->accounting_settings;
        $originalJournal = JournalEntry::create([
            'date' => now(),
            'description' => 'Purchase '.$this->purchase->invoice,
            'sourceable_id' => $this->purchase->id,
            'sourceable_type' => Purchase::class,
            'status' => 'posted',
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $settings['inventory_account_id'],
            'type' => 'debit',
            'amount' => 200.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $ppnMasukanAccount->id,
            'type' => 'debit',
            'amount' => 22.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $pphPayableAccount->id,
            'type' => 'credit',
            'amount' => 4.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $settings['accounts_payable_account_id'],
            'type' => 'credit',
            'amount' => 218.0,
        ]);

        $captured = [];
        $this->accountingServiceMock
            ->shouldReceive('createJournalEntry')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::on(function ($transactions) use (&$captured) {
                    $captured[] = $transactions;

                    return true;
                }),
                Mockery::any()
            );

        // Full return of the 200 net
        $returnData = [
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Full return',
            'status' => 'Completed',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 100, 'return_price' => 100],
            ]),
        ];

        $purchaseReturn = $this->purchaseReturnService->createPurchaseReturn($returnData);

        $this->assertEquals(200, $purchaseReturn->total_amount);
        $this->assertCount(1, $captured);

        $transactions = $captured[0];

        // The input VAT asset and the withholding liability must be
        // reversed proportionally — before the account_name fix these
        // reads always resolved to null, so no reversal lines were
        // ever emitted and both accounts stayed permanently overstated.
        $this->assertEquals(
            22.0,
            $this->sumTransactions($transactions, $ppnMasukanAccount->name, 'credit')
        );
        $this->assertEquals(
            4.0,
            $this->sumTransactions($transactions, $pphPayableAccount->name, 'debit')
        );
        // AP: debit for goods (200) + VAT no longer owed (22), credit for
        // the withholding owed again (4) → net debit 218 = the original AP.
        $apName = Account::find($settings['accounts_payable_account_id'])->name;
        $this->assertEquals(
            218.0,
            $this->sumTransactions($transactions, $apName, 'debit')
                - $this->sumTransactions($transactions, $apName, 'credit')
        );
    }

    /**
     * The figure the return SCREENS show must equal the figure the journal
     * BOOKS. Both read deriveReversal(), so this locks the single source of
     * truth. The case the audit flagged is a PARTIAL return: it needs its
     * own proportional treatment, not a copy of the parent's figures.
     */
    public function test_the_derived_reversal_equals_what_the_journal_books_on_a_partial_return()
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        $ppnMasukanAccount = Account::create([
            'name' => 'PPN Masukan '.$this->tenant->id,
            'code' => '1160-'.$this->tenant->id,
            'type' => 'asset',
            'tenant_id' => $this->tenant->id,
        ]);
        $pphPayableAccount = Account::create([
            'name' => 'PPh Payable '.$this->tenant->id,
            'code' => '2140-'.$this->tenant->id,
            'type' => 'liability',
            'tenant_id' => $this->tenant->id,
        ]);
        $this->tenant->update([
            'accounting_settings' => array_merge(
                $this->tenant->accounting_settings ?? [],
                [
                    'ppn_masukan_account_id' => $ppnMasukanAccount->id,
                    'pph_payable_account_id' => $pphPayableAccount->id,
                ]
            ),
        ]);

        // Create a stock record so the service can decrement
        ProductWarehouse::firstOrCreate(
            [
                'product_id' => $this->product->id,
                'warehouse_id' => $this->purchase->warehouse_id,
                'tenant_id' => $this->purchase->tenant_id,
            ],
            ['quantity' => 50]
        );

        POItem::firstOrCreate(
            ['po_id' => $this->purchase->id, 'product_id' => $this->product->id],
            [
                'quantity' => 10,
                'remaining_quantity' => 10,
                'price' => 100,
                'total' => 1000,
                'discount' => 0,
                'discount_type' => 'fixed',
            ]
        );

        // Original purchase journal as PurchaseService posts it:
        // subtotal 200 + PPN 11% (22) - PPh 2% (4) -> AP 218.
        $settings = $this->tenant->accounting_settings;
        $originalJournal = JournalEntry::create([
            'date' => now(),
            'description' => 'Purchase '.$this->purchase->invoice,
            'sourceable_id' => $this->purchase->id,
            'sourceable_type' => Purchase::class,
            'status' => 'posted',
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $settings['inventory_account_id'],
            'type' => 'debit',
            'amount' => 200.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $ppnMasukanAccount->id,
            'type' => 'debit',
            'amount' => 22.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $pphPayableAccount->id,
            'type' => 'credit',
            'amount' => 4.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $settings['accounts_payable_account_id'],
            'type' => 'credit',
            'amount' => 218.0,
        ]);

        $captured = [];
        $this->accountingServiceMock
            ->shouldReceive('createJournalEntry')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::on(function ($transactions) use (&$captured) {
                    $captured[] = $transactions;

                    return true;
                }),
                Mockery::any()
            );

        // PARTIAL return: 1 of 2 units = 100 of the 200 net, so ratio 0.5.
        $returnData = [
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Partial return',
            'status' => 'Completed',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 100, 'return_price' => 100],
            ]),
        ];

        $purchaseReturn = $this->purchaseReturnService->createPurchaseReturn($returnData);

        $this->assertEquals(100, $purchaseReturn->total_amount);
        $this->assertCount(1, $captured);

        // What the return SCREENS display...
        $derived = $this->purchaseReturnService->deriveReversal($this->purchase, (float) $purchaseReturn->total_amount);

        $this->assertEquals(0.5, $derived['ratio']);
        $this->assertEquals(11.0, $derived['ppn']);
        $this->assertEquals(2.0, $derived['pph']);
        $this->assertEquals(100.0, $derived['purchase']);

        // ...must equal what the journal actually BOOKED.
        $transactions = $captured[0];
        $bookedPpn = $this->sumTransactions($transactions, $ppnMasukanAccount->name, 'credit');
        $bookedPph = $this->sumTransactions($transactions, $pphPayableAccount->name, 'debit');

        $this->assertEquals($bookedPpn, $derived['ppn'], 'Screen PPN must equal booked PPN');
        $this->assertEquals($bookedPph, $derived['pph'], 'Screen PPh must equal booked PPh');

        // And both are genuinely proportional, not the parent's full figures.
        $this->assertEquals(11.0, $bookedPpn);
        $this->assertEquals(2.0, $bookedPph);
    }

    private function sumTransactions(array $transactions, string $accountName, string $type): float
    {
        $sum = 0.0;
        foreach ($transactions as $transaction) {
            if ($transaction['account_name'] === $accountName && $transaction['type'] === $type) {
                $sum += (float) $transaction['amount'];
            }
        }

        return $sum;
    }

    #[Test]
    public function test_it_copies_tax_from_parent_purchase_on_create()
    {
        // Give the parent purchase explicit tax fields
        $this->purchase->update([
            'tax_rate' => 11,
            'tax_type' => 'exclusive',
        ]);

        // Create a stock record so the service can decrement
        ProductWarehouse::firstOrCreate(
            [
                'product_id' => $this->product->id,
                'warehouse_id' => $this->purchase->warehouse_id,
                'tenant_id' => $this->purchase->tenant_id,
            ],
            ['quantity' => 50]
        );

        // Create a POItem so decrementPOItemRemaining has something to work with
        POItem::firstOrCreate(
            ['po_id' => $this->purchase->id, 'product_id' => $this->product->id],
            [
                'quantity' => 10,
                'remaining_quantity' => 10,
                'price' => 100,
                'total' => 1000,
                'discount' => 0,
                'discount_type' => 'fixed',
            ]
        );

        $this->accountingServiceMock->shouldReceive('createJournalEntry')->once();

        $returnData = [
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Damaged on arrival',
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 100, 'return_price' => 100],
            ]),
        ];

        $purchaseReturn = $this->purchaseReturnService->createPurchaseReturn($returnData);

        $this->assertNotNull($purchaseReturn);
        // Tax fields must be copied from the parent purchase
        $this->assertEquals($this->purchase->tax_id, $purchaseReturn->tax_id);
        $this->assertEquals(11.0, $purchaseReturn->tax_rate);
        $this->assertEquals('exclusive', $purchaseReturn->tax_type);
        // tax_amount = 11% of 200 = 22
        $this->assertEquals(22.0, $purchaseReturn->tax_amount);
    }

    #[Test]
    public function test_it_can_filter_index_data_by_month_and_year()
    {
        PurchaseReturn::factory()->create(['return_date' => '2023-01-15']);
        PurchaseReturn::factory()->create(['return_date' => '2023-02-20']);

        $filters = ['month' => 1, 'year' => 2023];
        $data = $this->purchaseReturnService->getPurchaseReturnIndexData($filters, 10);

        $this->assertEquals(1, $data['total_returns']);
    }

    #[Test]
    public function test_it_can_update_a_purchase_return()
    {
        $this->givePurchaseLine();

        // Pinned to a live status: the test below hand-simulates a return
        // that is holding stock, and a Canceled row holds none (the F2 rule
        // skips its stock halves on edit). The factory's random status made
        // this test fail on roughly one run in three.
        $purchaseReturn = PurchaseReturn::factory()->create([
            'purchase_id' => $this->purchase->id,
            'total_amount' => 100,
            'status' => 'Pending',
        ]);
        PurchaseReturnItem::factory()->create([
            'purchase_return_id' => $purchaseReturn->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        // Decrement stock from ProductWarehouse instead of Product directly
        $stockRecord = $this->product->productWarehouses()->first();
        $stockRecord->decrement('quantity', 1);
        $this->product->refresh();
        $this->assertEquals(99, $this->product->stock_quantity);

        $updateData = [
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Updated reason',
            'status' => 'Completed',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 3, 'price' => 50],
            ]),
        ];

        // Re-journaling runs on update: nothing to reverse (factory return
        // never posted), then one fresh entry is handed to the accounting
        // service.
        $this->accountingServiceMock->shouldReceive('createJournalEntry')->once();

        $updatedReturn = $this->purchaseReturnService->updatePurchaseReturn($purchaseReturn, $updateData);

        $this->assertEquals('Completed', $updatedReturn->status);
        $this->assertEquals(150, $updatedReturn->total_amount);
        $this->assertCount(1, $updatedReturn->items);

        $this->product->refresh();
        // Initial stock 100. Factory creates item, stock becomes 99.
        // Update reverts old item (+1), new item is 3, so stock is 100 + 1 - 3 = 97.
        // But the service logic is incrementing from the old value, so it's 99 + 1 - 3 = 97
        $this->assertEquals(97, $this->product->stock_quantity);
    }

    #[Test]
    public function test_update_throws_exception_for_invalid_items_data()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid items data');

        $purchaseReturn = PurchaseReturn::factory()->create();
        $updateData = [
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Test',
            'status' => 'Pending',
            'items' => 'not-a-json-string',
        ];

        $this->purchaseReturnService->updatePurchaseReturn($purchaseReturn, $updateData);
    }

    #[Test]
    public function test_update_re_journals_the_gl_with_the_new_total()
    {
        $this->givePurchaseLine();
        $service = app(PurchaseReturnService::class);

        // Create: 2 × 50 = 100 → one posted entry, net AP debit 100.
        $purchaseReturn = $service->createPurchaseReturn([
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Damaged in transit',
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);

        $originalEntries = JournalEntry::where('sourceable_type', PurchaseReturn::class)
            ->where('sourceable_id', $purchaseReturn->id)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->get();
        $this->assertCount(1, $originalEntries);
        $this->assertEqualsWithDelta(100.0, $this->netAccountsPayableEffect($purchaseReturn), 0.001);

        // Edit: 3 × 50 = 150 → old entry reversed, new entry posted for 150.
        $service->updatePurchaseReturn($purchaseReturn, [
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Damaged in transit (recount)',
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 3, 'price' => 50],
            ]),
        ]);

        $entries = JournalEntry::where('sourceable_type', PurchaseReturn::class)
            ->where('sourceable_id', $purchaseReturn->id)
            ->get();
        $this->assertCount(2, $entries);

        $original = $entries->firstWhere('id', $originalEntries->first()->id);
        $this->assertTrue($original->hasDependentEntries(), 'The pre-edit entry must be reversed.');

        $this->assertEqualsWithDelta(150.0, $this->netAccountsPayableEffect($purchaseReturn), 0.001);
    }

    /**
     * Zeroing a live return on edit is a designed path, not an accident:
     * the return hands back the units it held, its journal nets to zero
     * instead of posting an empty entry, and no item rows remain. This
     * pins what the empty `if (empty($returnedItems))` block in
     * updatePurchaseReturn() only ever commented about, so deleting that
     * dead block cannot quietly change any of it.
     */
    #[Test]
    public function test_editing_a_return_down_to_zero_items_releases_stock_and_nets_the_journal_to_zero()
    {
        $this->givePurchaseLine();
        $service = app(PurchaseReturnService::class);
        $stockBefore = $this->product->fresh()->stock_quantity;

        $purchaseReturn = $service->createPurchaseReturn([
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);

        $this->assertEquals($stockBefore - 2, $this->product->fresh()->stock_quantity);
        $this->assertEqualsWithDelta(100.0, $this->netAccountsPayableEffect($purchaseReturn), 0.001);

        $service->updatePurchaseReturn($purchaseReturn, [
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Counted twice, nothing left to return',
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 0, 'price' => 50],
            ]),
        ]);

        $fresh = $purchaseReturn->fresh();

        $this->assertEquals($stockBefore, $this->product->fresh()->stock_quantity, 'Zeroing a live return must hand its units back.');
        $this->assertCount(0, $fresh->items, 'A zeroed return holds no item rows.');
        $this->assertEqualsWithDelta(0.0, (float) $fresh->total_amount, 0.001);
        $this->assertEqualsWithDelta(0.0, $this->netAccountsPayableEffect($purchaseReturn), 0.001, 'The journal must net to zero rather than post an empty entry.');
    }

    #[Test]
    public function test_a_second_edit_does_not_reverse_the_same_entry_twice()
    {
        $this->givePurchaseLine();
        $service = app(PurchaseReturnService::class);

        $purchaseReturn = $service->createPurchaseReturn([
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);
        $firstEntryId = JournalEntry::where('sourceable_type', PurchaseReturn::class)
            ->where('sourceable_id', $purchaseReturn->id)
            ->value('id');

        // Two successive edits. reverseJournalEntry() leaves the original
        // 'posted', so without the hasDependentEntries() guard the second
        // edit would reverse entry #1 a second time.
        $purchaseReturn = $service->updatePurchaseReturn($purchaseReturn, [
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 3, 'price' => 50],
            ]),
        ]);
        $purchaseReturn = $service->updatePurchaseReturn($purchaseReturn, [
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 4, 'price' => 50],
            ]),
        ]);

        $this->assertEquals(
            1,
            JournalEntry::where('reversed_entry_id', $firstEntryId)->count(),
            'The first entry must be reversed exactly once.'
        );
        $this->assertCount(
            3,
            JournalEntry::where('sourceable_type', PurchaseReturn::class)
                ->where('sourceable_id', $purchaseReturn->id)
                ->get()
        );
        $this->assertEqualsWithDelta(200.0, $this->netAccountsPayableEffect($purchaseReturn), 0.001);
    }

    #[Test]
    public function test_editing_status_to_canceled_reverses_without_reposting_then_reopens()
    {
        $this->givePurchaseLine();
        $service = app(PurchaseReturnService::class);
        $stockBefore = $this->product->fresh()->stock_quantity;

        $purchaseReturn = $service->createPurchaseReturn([
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);
        $firstEntryId = JournalEntry::where('sourceable_type', PurchaseReturn::class)
            ->where('sourceable_id', $purchaseReturn->id)
            ->value('id');
        $this->assertEquals($stockBefore - 2, $this->product->fresh()->stock_quantity);

        // Canceled via the edit form: reversal only, no fresh entry. A
        // canceled return must not leave a live GL impact behind.
        $purchaseReturn = $service->updatePurchaseReturn($purchaseReturn, [
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Canceled',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 50],
            ]),
        ]);

        $this->assertTrue(JournalEntry::findOrFail($firstEntryId)->hasDependentEntries());
        $this->assertEqualsWithDelta(0.0, $this->netAccountsPayableEffect($purchaseReturn), 0.001);
        // The GL was not the only half: the old 2 units go back to the
        // warehouse and the new 1 is NOT taken out, so a Canceled return
        // holds no stock. The stock half used to decrement unconditionally
        // and left the canceled row holding 1.
        $this->assertEquals($stockBefore, $this->product->fresh()->stock_quantity);

        // Re-open it: the already-reversed entry stays reversed exactly once
        // and a new entry carries the reopened total.
        $purchaseReturn = $service->updatePurchaseReturn($purchaseReturn, [
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 70],
            ]),
        ]);

        $this->assertEquals(1, JournalEntry::where('reversed_entry_id', $firstEntryId)->count());
        $this->assertEqualsWithDelta(70.0, $this->netAccountsPayableEffect($purchaseReturn), 0.001);
        // The reopened return takes exactly its new 1 unit out: nothing is
        // handed back for the canceled state it came from, because a canceled
        // return held nothing to hand back.
        $this->assertEquals($stockBefore - 1, $this->product->fresh()->stock_quantity);
    }

    #[Test]
    public function test_delete_after_edit_does_not_reverse_the_original_entry_twice()
    {
        $this->givePurchaseLine();
        $service = app(PurchaseReturnService::class);

        $purchaseReturn = $service->createPurchaseReturn([
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);
        $firstEntryId = JournalEntry::where('sourceable_type', PurchaseReturn::class)
            ->where('sourceable_id', $purchaseReturn->id)
            ->value('id');

        $purchaseReturn = $service->updatePurchaseReturn($purchaseReturn, [
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 3, 'price' => 50],
            ]),
        ]);

        $service->deletePurchaseReturn($purchaseReturn);

        // Delete may only reverse the live post-edit entry; reversing the
        // already-reversed original again would swing the GL negative.
        $this->assertEquals(
            1,
            JournalEntry::where('reversed_entry_id', $firstEntryId)->count()
        );
        $this->assertEqualsWithDelta(0.0, $this->netAccountsPayableEffect($purchaseReturn), 0.001);
    }

    #[Test]
    public function test_canceling_a_pending_return_reverts_the_stock_and_the_journal()
    {
        $this->givePurchaseLine();
        $service = app(PurchaseReturnService::class);
        $stockBefore = $this->product->fresh()->stock_quantity;

        // Created Pending: create already took the goods out of stock and
        // posted the journal, so cancel from Pending owes both of them back.
        $purchaseReturn = $service->createPurchaseReturn([
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);
        $entryId = JournalEntry::where('sourceable_type', PurchaseReturn::class)
            ->where('sourceable_id', $purchaseReturn->id)
            ->value('id');

        $this->assertEquals($stockBefore - 2, $this->product->fresh()->stock_quantity);
        $this->assertEqualsWithDelta(100.0, $this->netAccountsPayableEffect($purchaseReturn), 0.001);

        $service->cancelPurchaseReturn($purchaseReturn);

        // Same contract as cancelSalesReturn() and as deletePurchaseReturn(),
        // which already reverted unconditionally: the Completed-only gate left
        // the stock moved and a live GL entry behind for a canceled return.
        $this->assertEquals('Canceled', $purchaseReturn->fresh()->status);
        $this->assertEquals($stockBefore, $this->product->fresh()->stock_quantity);
        $this->assertEqualsWithDelta(0.0, $this->netAccountsPayableEffect($purchaseReturn), 0.001);
        $this->assertTrue(JournalEntry::findOrFail($entryId)->hasDependentEntries());
        $this->assertEquals(
            1,
            JournalEntry::where('reversed_entry_id', $entryId)->count(),
            'The entry must be reversed exactly once.'
        );

        // A canceled return must not keep counting toward the parent
        // purchase's returned quantity either.
        $this->purchase->refresh();
        $this->assertNotEquals('Returned', $this->purchase->status);

        // Canceling twice is a no-op, not a second revert.
        $service->cancelPurchaseReturn($purchaseReturn);
        $this->assertEquals($stockBefore, $this->product->fresh()->stock_quantity);
        $this->assertEquals(1, JournalEntry::where('reversed_entry_id', $entryId)->count());
        $this->assertEqualsWithDelta(0.0, $this->netAccountsPayableEffect($purchaseReturn), 0.001);
    }

    public function test_completing_a_canceled_return_is_refused()
    {
        $this->givePurchaseLine();
        $service = app(PurchaseReturnService::class);
        $stockBefore = $this->product->fresh()->stock_quantity;

        $purchaseReturn = $service->createPurchaseReturn([
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);
        $service->cancelPurchaseReturn($purchaseReturn);
        $purchaseReturn = $purchaseReturn->fresh();

        // The bulk action skips canceled rows, so this throw only ever fires
        // for a direct caller. Without it a Canceled row could be flipped to
        // Completed and read as a live return while holding no stock and no
        // journal entry.
        $refused = false;
        try {
            $service->completePurchaseReturn($purchaseReturn);
        } catch (\Exception $e) {
            $refused = true;
            $this->assertStringContainsString('canceled', $e->getMessage());
        }

        $this->assertTrue($refused, 'completePurchaseReturn() must refuse a canceled return.');
        $this->assertEquals('Canceled', $purchaseReturn->fresh()->status);
        $this->assertEquals($stockBefore, $this->product->fresh()->stock_quantity);
        $this->assertEqualsWithDelta(0.0, $this->netAccountsPayableEffect($purchaseReturn), 0.001);
    }

    public function test_bulk_complete_skips_canceled_rows_and_counts_them()
    {
        $this->givePurchaseLine();
        $service = app(PurchaseReturnService::class);

        $live = $service->createPurchaseReturn([
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 50],
            ]),
        ]);
        $canceled = $service->createPurchaseReturn([
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 50],
            ]),
        ]);
        $service->cancelPurchaseReturn($canceled);

        $counts = $service->bulkCompletePurchaseReturns([$live->id, $canceled->id]);

        $this->assertEquals(1, $counts['completed']);
        $this->assertEquals(1, $counts['skipped']);
        $this->assertEquals('Completed', $live->fresh()->status);
        $this->assertEquals('Canceled', $canceled->fresh()->status);
    }

    public function test_deleting_a_canceled_return_does_not_move_the_stock_a_second_time()
    {
        $this->givePurchaseLine();
        $service = app(PurchaseReturnService::class);
        $stockBefore = $this->product->fresh()->stock_quantity;

        $purchaseReturn = $service->createPurchaseReturn([
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);
        $service->cancelPurchaseReturn($purchaseReturn);
        $this->assertEquals($stockBefore, $this->product->fresh()->stock_quantity);

        $service->deletePurchaseReturn($purchaseReturn->fresh());

        // cancel already handed the 2 units back, so delete of a Canceled row
        // is a pure row removal: the unconditional revert it used to do put
        // the same goods back into the warehouse a second time.
        $this->assertDatabaseMissing('purchase_returns', ['id' => $purchaseReturn->id]);
        $this->assertEquals($stockBefore, $this->product->fresh()->stock_quantity);
    }

    public function test_creating_a_return_born_canceled_moves_no_stock_and_posts_no_journal()
    {
        $service = app(PurchaseReturnService::class);
        $stockBefore = $this->product->fresh()->stock_quantity;

        $purchaseReturn = $service->createPurchaseReturn([
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Canceled',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);

        // Mirror of the sales-side backstop: a Canceled birth moves no stock
        // and books nothing, so cancel never owes a revert for it.
        $this->assertEquals('Canceled', $purchaseReturn->status);
        $this->assertEquals($stockBefore, $this->product->fresh()->stock_quantity);
        $this->assertEquals(
            0,
            JournalEntry::where('sourceable_type', PurchaseReturn::class)
                ->where('sourceable_id', $purchaseReturn->id)
                ->count()
        );
    }

    public function test_delete_recalculates_the_purchase_from_live_returns_only()
    {
        // The purchase sold exactly these 2 units, so the live return of 2
        // covers the whole order and the purchase reads Returned.
        $this->givePurchaseLine(2);
        $service = app(PurchaseReturnService::class);

        $live = $service->createPurchaseReturn([
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);
        // Born Canceled: it holds no stock and no journal, and it is never
        // counted as returned - exactly the state this test needs beside the
        // live one (a Pending sibling would push the pair past the 2 ordered).
        $canceled = $service->createPurchaseReturn([
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Canceled',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 50],
            ]),
        ]);

        $this->assertEquals('Returned', $this->purchase->fresh()->status);

        $service->deletePurchaseReturn($live->fresh());

        // The canceled sibling still holds its rows for history but holds no
        // stock and no journal entry, so it cannot keep the purchase Returned:
        // the delete-path recount is live-only, like create, edit and cancel.
        $this->purchase->refresh();
        $this->assertEquals('Unpaid', $this->purchase->status);
    }

    /**
     * Nothing on the server refused a return larger than the purchase ever
     * sold: the stock record went negative, the purchase flipped to
     * Returned and the journal reversed more than was booked. The cap
     * counts every live (non-Canceled) return against the ordered quantity.
     */
    #[Test]
    public function test_create_refuses_a_return_larger_than_the_purchase_still_owes()
    {
        $this->givePurchaseLine(10);

        // Count postings in the test body rather than with a Mockery count
        // expectation: an unmet count throws during teardown, before
        // RefreshDatabase rolls its transaction back, and poisons every
        // test that follows. The mock method is typed, so it must return
        // a JournalEntry.
        $journalPostings = 0;
        $this->accountingServiceMock->shouldReceive('createJournalEntry')
            ->andReturnUsing(function () use (&$journalPostings) {
                $journalPostings++;

                return Mockery::mock(JournalEntry::class);
            });

        $refused = null;
        try {
            $this->purchaseReturnService->createPurchaseReturn([
                'purchase_id' => $this->purchase->id,
                'return_date' => now()->format('Y-m-d'),
                'status' => 'Pending',
                'items' => json_encode([
                    ['product_id' => $this->product->id, 'returned_quantity' => 11, 'price' => 50],
                ]),
            ]);
        } catch (\Exception $e) {
            $refused = $e->getMessage();
        }

        $this->assertNotNull($refused, 'A return larger than the purchase ordered must be refused.');
        $this->assertStringContainsString('only 10 of the 10 purchased units can still be returned', $refused);
        $this->assertDatabaseMissing('purchase_returns', ['purchase_id' => $this->purchase->id]);
        $this->assertEquals(100, $this->product->fresh()->stock_quantity);

        // Fill 6 of the 10 (the one legitimate journal entry)...
        $this->purchaseReturnService->createPurchaseReturn([
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 6, 'price' => 50],
            ]),
        ]);
        $this->assertEquals(94, $this->product->fresh()->stock_quantity);

        // ...then a second live return of 5: it fits on its own, but 6 live
        // + 5 exceeds the 10 ordered, so it has to be refused too.
        $refused = null;
        try {
            $this->purchaseReturnService->createPurchaseReturn([
                'purchase_id' => $this->purchase->id,
                'return_date' => now()->format('Y-m-d'),
                'status' => 'Pending',
                'items' => json_encode([
                    ['product_id' => $this->product->id, 'returned_quantity' => 5, 'price' => 50],
                ]),
            ]);
        } catch (\Exception $e) {
            $refused = $e->getMessage();
        }

        $this->assertNotNull($refused, 'Live returns together must not exceed the purchase ordered.');
        $this->assertStringContainsString('only 4 of the 10 purchased units can still be returned', $refused);
        $this->assertEquals(1, PurchaseReturn::where('purchase_id', $this->purchase->id)->count());
        $this->assertEquals(94, $this->product->fresh()->stock_quantity);
        // Neither refusal may have posted a journal entry.
        $this->assertEquals(1, $journalPostings);
    }

    /**
     * An edit is checked against the OTHER live returns: filling the
     * purchase's remaining quantity is legitimate (the return's own old
     * units must not count against it), while an edit that pushes the live
     * total past what was ordered must be refused before stock and the
     * journal move.
     */
    #[Test]
    public function test_update_refuses_when_other_live_returns_leave_less_than_requested()
    {
        $this->givePurchaseLine(10);

        // Same teardown-safe counting as the create-cap proof: three
        // legitimate postings (create A, edit A, create B), and the refused
        // edit must add no fourth.
        $journalPostings = 0;
        $this->accountingServiceMock->shouldReceive('createJournalEntry')
            ->andReturnUsing(function () use (&$journalPostings) {
                $journalPostings++;

                return Mockery::mock(JournalEntry::class);
            });

        $a = $this->purchaseReturnService->createPurchaseReturn([
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 6, 'price' => 50],
            ]),
        ]);

        // A edits itself up to 9: its own 6 live units are excluded from
        // its own check, and 9 of 10 ordered is inside the cap.
        $this->purchaseReturnService->updatePurchaseReturn($a, [
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 9, 'price' => 50],
            ]),
        ]);
        $this->assertEquals(9, $a->fresh()->items->sum('quantity'));
        $this->assertEquals(91, $this->product->fresh()->stock_quantity);

        // B takes the last unit: 9 + 1 = 10 of 10.
        $this->purchaseReturnService->createPurchaseReturn([
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 50],
            ]),
        ]);

        // With B live, A may no longer be edited up to 10: A's 10 plus B's
        // 1 exceeds the 10 ordered.
        $refused = null;
        try {
            $this->purchaseReturnService->updatePurchaseReturn($a, [
                'return_date' => now()->format('Y-m-d'),
                'status' => 'Pending',
                'items' => json_encode([
                    ['product_id' => $this->product->id, 'returned_quantity' => 10, 'price' => 50],
                ]),
            ]);
        } catch (\Exception $e) {
            $refused = $e->getMessage();
        }

        $this->assertNotNull($refused, 'An edit that pushes live returns past the ordered quantity must be refused.');
        // What A may still set itself to: 10 ordered - B's 1 live unit = 9.
        $this->assertStringContainsString('only 9 of the 10 purchased units can still be returned', $refused);
        $this->assertEquals(9, $a->fresh()->items->sum('quantity'));
        $this->assertEquals(90, $this->product->fresh()->stock_quantity);
        // The refused edit must not have posted a journal entry either.
        $this->assertEquals(3, $journalPostings);
    }

    /**
     * Audit F6: the purchase walk had no ORDER BY at all, so which PO line a
     * return drained depended on whichever index the query planner picked.
     * Both directions now walk FEFO (earliest expiry, oldest line, id).
     *
     * This locks the contract rather than reproducing the old bug: on SQLite
     * the (po_id, expiry_date) index already returns expiry order, so the
     * unordered code passes here too. Production runs MariaDB, where the
     * planner may choose the product_id or primary index instead and hand the
     * lines back in a different order. The far-expiring line is still created
     * first, so insertion order cannot quietly match FEFO either.
     */
    #[Test]
    public function test_po_lines_are_drawn_in_expiry_order_and_restored_on_cancel()
    {
        $service = app(PurchaseReturnService::class);

        $farExpiry = POItem::factory()->create([
            'po_id' => $this->purchase->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'expiry_date' => now()->addDays(40),
        ]);
        $nearExpiry = POItem::factory()->create([
            'po_id' => $this->purchase->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'expiry_date' => now()->addDays(10),
        ]);

        $purchaseReturn = $service->createPurchaseReturn([
            'purchase_id' => $this->purchase->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 12, 'price' => 50],
            ]),
        ]);

        // 12 units to the supplier: 10 off the near line, 2 off the far line.
        $this->assertEquals(
            0,
            (int) $nearExpiry->fresh()->remaining_quantity,
            'The nearest expiry must be drained first.'
        );
        $this->assertEquals(8, (int) $farExpiry->fresh()->remaining_quantity);

        $service->cancelPurchaseReturn($purchaseReturn);

        // Cancel walks the same order, so every unit goes back to the line it
        // left and the pair ends exactly where it started.
        $this->assertEquals(
            10,
            (int) $nearExpiry->fresh()->remaining_quantity,
            'Cancel must refill the line create drained.'
        );
        $this->assertEquals(10, (int) $farExpiry->fresh()->remaining_quantity);
        $this->assertEquals(
            20,
            (int) POItem::whereIn('id', [$nearExpiry->id, $farExpiry->id])->sum('remaining_quantity'),
            'A canceled return must leave the PO lines exactly as it found them.'
        );
    }

    /**
     * Net debit/credit effect on Accounts Payable from everything journaled
     * against this return: the sourceable entries plus their reversals
     * (reversal entries carry no sourceable themselves).
     */
    private function netAccountsPayableEffect(PurchaseReturn $purchaseReturn): float
    {
        $entryIds = JournalEntry::where('sourceable_type', PurchaseReturn::class)
            ->where('sourceable_id', $purchaseReturn->id)
            ->pluck('id');

        $reversalIds = JournalEntry::whereIn('reversed_entry_id', $entryIds)->pluck('id');

        $accountsPayableId = $this->tenant->accounting_settings['accounts_payable_account_id'];

        return (float) Transaction::whereIn('journal_entry_id', $entryIds->merge($reversalIds))
            ->where('account_id', $accountsPayableId)
            ->selectRaw("SUM(CASE WHEN type = 'debit' THEN amount ELSE -amount END) AS net")
            ->value('net');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
