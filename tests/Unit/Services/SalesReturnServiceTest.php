<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\CustomerCredit;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Tax;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\SalesReturnService;
use App\Services\SalesService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class SalesReturnServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected User $user;

    protected Sales $sale;

    protected Product $product;

    protected SalesReturnService $salesReturnService;

    protected $accountingServiceMock;

    protected $salesServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant(); // Creates $this->tenant and $this->user, and calls actingAs
        $this->user->assignRole('superuser'); // Ensure the user has permissions for services

        $this->product = Product::factory()->withStock(100)->create();
        $this->sale = Sales::factory()->create();

        // Mock AccountingService
        $this->accountingServiceMock = Mockery::mock(AccountingService::class);
        // Mock SalesService - allow updateSaleStatus to be called without errors
        $this->salesServiceMock = Mockery::mock(SalesService::class);
        $this->salesServiceMock->shouldReceive('updateSaleStatus')->andReturn(null)->byDefault();
        $this->salesReturnService = new SalesReturnService($this->accountingServiceMock, $this->salesServiceMock);
    }

    public function test_it_can_get_sales_return_index_data()
    {
        SalesReturn::factory()->count(5)->create();

        $data = $this->salesReturnService->getSalesReturnIndexData([], 10);

        $this->assertArrayHasKey('returns', $data);
        $this->assertArrayHasKey('total_returns', $data);
        $this->assertArrayHasKey('total_amount', $data);
        $this->assertEquals(5, $data['total_returns']);
    }

    public function test_it_copies_tax_from_parent_sale_on_create()
    {
        // Create a tax record and link it to the parent sale
        $tax = Tax::create([
            'name' => 'PPN 11%',
            'rate' => 11,
            'type' => 'exclusive',
            'is_active' => true,
            'is_default' => true,
        ]);

        // Give the parent sale explicit tax fields
        $this->sale->update([
            'tax_id' => $tax->id,
            'tax_rate' => 11,
            'tax_type' => 'exclusive',
        ]);

        $this->sale->salesItems()->create([
            'product_id' => $this->product->id,
            'quantity' => 5,
            'price' => 100,
            'customer_price' => 100,
            'discount' => 0,
            'total' => 500,
        ]);

        $this->accountingServiceMock->shouldReceive('createJournalEntry')->once();

        $returnData = [
            'sales_id' => $this->sale->id,
            'return_date' => Carbon::now()->format('Y-m-d'),
            'reason' => 'Defective product',
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 100, 'return_price' => 100],
            ]),
            'total_amount' => 200,
        ];

        $salesReturn = $this->salesReturnService->createSalesReturn($returnData);

        $this->assertNotNull($salesReturn);
        // Tax fields must be copied from the parent sale
        $this->assertEquals($tax->id, $salesReturn->tax_id);
        $this->assertEquals(11.0, $salesReturn->tax_rate);
        $this->assertEquals('exclusive', $salesReturn->tax_type);
        // tax_amount = 11% of 200 = 22
        $this->assertEquals(22.0, $salesReturn->tax_amount);
    }

    public function test_it_can_filter_index_data_by_month_and_year()
    {
        SalesReturn::factory()->create(['return_date' => '2023-01-15']);
        SalesReturn::factory()->create(['return_date' => '2023-02-20']);

        $filters = ['month' => 1, 'year' => 2023];
        $data = $this->salesReturnService->getSalesReturnIndexData($filters, 10);

        $this->assertEquals(1, $data['total_returns']);
    }

    public function test_it_can_create_a_sales_return()
    {
        $this->sale->salesItems()->create([
            'product_id' => $this->product->id,
            'quantity' => 5,
            'price' => 100,
            'customer_price' => 100,
            'discount' => 0,
            'total' => 500,
        ]);

        $this->product->refresh();
        $initialStock = $this->product->stock_quantity;

        // Create a sale that is unpaid first - the factory creates Paid status by default
        // so let's create a fresh sale for testing
        $unpaidSale = Sales::factory()->create(['status' => 'Unpaid']);

        $returnData = [
            'sales_id' => $unpaidSale->id,
            'return_date' => Carbon::now()->format('Y-m-d'),
            'reason' => 'Customer changed mind',
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 100, 'return_price' => 100],
            ]),
            'total_amount' => 200,
        ];

        // Mock accounting service for journal entry creation
        $this->accountingServiceMock->shouldReceive('createJournalEntry')->once();

        $salesReturn = $this->salesReturnService->createSalesReturn($returnData);

        $this->assertNotNull($salesReturn);
        $this->assertEquals('Pending', $salesReturn->status);
        $this->assertEquals(200, $salesReturn->total_amount);
        $this->assertDatabaseHas('sales_returns', ['id' => $salesReturn->id]);

        $this->product->refresh();
        $this->assertEquals($initialStock + 2, $this->product->stock_quantity); // Stock should increase by returned quantity
        $unpaidSale->refresh();
        // The sale status becomes 'Returned' when a return is processed against it
        $this->assertEquals('Returned', $unpaidSale->status);
    }

    public function test_create_sales_return_throws_exception_for_no_items()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No items selected for return.');

        $returnData = [
            'sales_id' => $this->sale->id,
            'return_date' => Carbon::now()->format('Y-m-d'),
            'reason' => 'No items',
            'status' => 'Pending',
            'items' => json_encode([]),
            'total_amount' => 0,
        ];

        $this->salesReturnService->createSalesReturn($returnData);
    }

    public function test_it_can_update_a_sales_return()
    {
        $this->sale->salesItems()->create([
            'product_id' => $this->product->id,
            'quantity' => 5,
            'price' => 100,
            'customer_price' => 100,
            'discount' => 0,
            'total' => 500,
        ]);

        $salesReturn = SalesReturn::factory()->create([
            'sales_id' => $this->sale->id,
            'total_amount' => 100,
            'status' => 'Pending',
        ]);
        SalesReturnItem::factory()->create([
            'sales_return_id' => $salesReturn->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);

        $this->product->refresh();
        $this->assertEquals(100, $this->product->stock_quantity); // Initial stock

        $updateData = [
            'sales_id' => $this->sale->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Updated reason',
            'status' => 'Completed',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 3, 'price' => 50],
            ]),
        ];

        // An edit re-journals: nothing was ever posted against a factory-made
        // return, so only the fresh entry is booked (no reversal to make).
        $this->accountingServiceMock->shouldReceive('createJournalEntry')->once();

        $updatedReturn = $this->salesReturnService->updateSalesReturn($salesReturn, $updateData);

        $this->assertEquals('Completed', $updatedReturn->status);
        $this->assertEquals(150, $updatedReturn->total_amount); // 3 items * 50 price
        $this->assertCount(1, $updatedReturn->items);

        $this->product->refresh();
        // Initial stock (100) + first return (1) - old return (1) + new return (3) = 100 + 1 - 1 + 3 = 103
        $this->assertEquals(100 + 1 - 1 + 3, $this->product->stock_quantity);
        $this->sale->refresh();
        $this->assertEquals('Partial', $this->sale->status); // Still partial as 3/5 returned
    }

    public function test_update_sales_return_throws_exception_for_invalid_items_data()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid items data');

        $salesReturn = SalesReturn::factory()->create();
        $updateData = [
            'sales_id' => $this->sale->id,
            'return_date' => Carbon::now()->format('Y-m-d'),
            'reason' => 'Test',
            'status' => 'Pending',
            'items' => 'not-a-json-string',
        ];

        $this->salesReturnService->updateSalesReturn($salesReturn, $updateData);
    }

    public function test_it_reverses_ppn_from_original_journal_net_base()
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        $ppnKeluaranAccount = Account::create([
            'name' => 'PPN Keluaran '.$this->tenant->id,
            'code' => '2130-'.$this->tenant->id,
            'type' => 'liability',
            'tenant_id' => $this->tenant->id,
        ]);
        $this->tenant->update([
            'accounting_settings' => array_merge(
                $this->tenant->accounting_settings ?? [],
                ['ppn_keluaran_account_id' => $ppnKeluaranAccount->id]
            ),
        ]);

        // Parent sale: subtotal 200 + PPN 11% = 222 gross, VAT already
        // recognised at sale creation (Dr AR 222 / Cr Revenue 200 /
        // Cr PPN Keluaran 22) — exactly what SalesService now posts.
        $this->sale->update([
            'ppn_enabled' => true,
            'ppn_rate' => 11.0,
            'ppn_amount' => 22.0,
            'tax_rate' => 11,
            'tax_type' => 'exclusive',
            'total' => 222.0,
        ]);

        $this->sale->salesItems()->create([
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 100,
            'customer_price' => 100,
            'discount' => 0,
            'total' => 200,
        ]);

        $settings = $this->tenant->accounting_settings;
        $originalJournal = JournalEntry::create([
            'date' => now(),
            'description' => 'Sale Invoice #'.$this->sale->invoice,
            'sourceable_id' => $this->sale->id,
            'sourceable_type' => Sales::class,
            'status' => 'posted',
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $settings['accounts_receivable_account_id'],
            'type' => 'debit',
            'amount' => 222.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $settings['sales_revenue_account_id'],
            'type' => 'credit',
            'amount' => 200.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $ppnKeluaranAccount->id,
            'type' => 'credit',
            'amount' => 22.0,
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

        // Full return of the 200 net — ratio must be measured against the
        // ORIGINAL journal's revenue credit (200), not sale->total (222).
        $returnData = [
            'sales_id' => $this->sale->id,
            'return_date' => Carbon::now()->format('Y-m-d'),
            'reason' => 'Full return',
            'status' => 'Completed',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 100],
            ]),
        ];

        $salesReturn = $this->salesReturnService->createSalesReturn($returnData);

        $this->assertEquals(200, $salesReturn->total_amount);
        $this->assertCount(1, $captured);

        $transactions = $captured[0];

        // Full VAT reversal (22), not 200/222 × 22 = 19.82, and not the
        // silent zero the broken account_name read produced.
        $this->assertEquals(
            22.0,
            $this->sumTransactions($transactions, $ppnKeluaranAccount->name, 'debit')
        );
        // Revenue fully reversed for the returned goods
        $this->assertEquals(
            200.0,
            $this->sumTransactions($transactions, Account::find($settings['sales_revenue_account_id'])->name, 'debit')
        );
        // AR credited for goods (200) + the reversed VAT (22)
        $this->assertEquals(
            222.0,
            $this->sumTransactions($transactions, Account::find($settings['accounts_receivable_account_id'])->name, 'credit')
        );
    }

    /**
     * The figure the return SCREENS show must equal the figure the journal
     * BOOKS. Both read deriveReversal(), so this locks the single source of
     * truth. The case the audit flagged is a PARTIAL return: it needs its
     * own proportional tax treatment, not a copy of the parent's figures.
     */
    public function test_the_derived_reversal_equals_what_the_journal_books_on_a_partial_return()
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        $ppnKeluaranAccount = Account::create([
            'name' => 'PPN Keluaran '.$this->tenant->id,
            'code' => '2130-'.$this->tenant->id,
            'type' => 'liability',
            'tenant_id' => $this->tenant->id,
        ]);
        $pphReceivableAccount = Account::create([
            'name' => 'PPh Receivable '.$this->tenant->id,
            'code' => '1170-'.$this->tenant->id,
            'type' => 'asset',
            'tenant_id' => $this->tenant->id,
        ]);
        $this->tenant->update([
            'accounting_settings' => array_merge(
                $this->tenant->accounting_settings ?? [],
                [
                    'ppn_keluaran_account_id' => $ppnKeluaranAccount->id,
                    'pph_receivable_account_id' => $pphReceivableAccount->id,
                ]
            ),
        ]);

        // Parent sale: 200 net + PPN 11% (22) + PPh 2% (4).
        $this->sale->update([
            'ppn_enabled' => true,
            'ppn_rate' => 11.0,
            'ppn_amount' => 22.0,
            'pph_enabled' => true,
            'pph_rate' => 2.0,
            'pph_amount' => 4.0,
            'tax_rate' => 11,
            'tax_type' => 'exclusive',
            'total' => 222.0,
        ]);

        $this->sale->salesItems()->create([
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 100,
            'customer_price' => 100,
            'discount' => 0,
            'total' => 200,
        ]);

        $settings = $this->tenant->accounting_settings;

        // VAT split as SalesService posts it: Dr AR gross / Cr Revenue net /
        // Cr PPN Keluaran, plus the COGS pair.
        $originalJournal = JournalEntry::create([
            'date' => now(),
            'description' => 'Sale Invoice #'.$this->sale->invoice,
            'sourceable_id' => $this->sale->id,
            'sourceable_type' => Sales::class,
            'status' => 'posted',
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $settings['accounts_receivable_account_id'],
            'type' => 'debit',
            'amount' => 222.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $settings['sales_revenue_account_id'],
            'type' => 'credit',
            'amount' => 200.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $ppnKeluaranAccount->id,
            'type' => 'credit',
            'amount' => 22.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $settings['cost_of_goods_sold_account_id'],
            'type' => 'debit',
            'amount' => 120.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $settings['inventory_account_id'],
            'type' => 'credit',
            'amount' => 120.0,
        ]);

        // PPh is booked as its own entry: Dr PPh Receivable / Cr AR.
        $pphJournal = JournalEntry::create([
            'date' => now(),
            'description' => 'PPh Withholding on Sale #'.$this->sale->invoice,
            'sourceable_id' => $this->sale->id,
            'sourceable_type' => Sales::class,
            'status' => 'posted',
        ]);
        $pphJournal->transactions()->create([
            'account_id' => $pphReceivableAccount->id,
            'type' => 'debit',
            'amount' => 4.0,
        ]);
        $pphJournal->transactions()->create([
            'account_id' => $settings['accounts_receivable_account_id'],
            'type' => 'credit',
            'amount' => 4.0,
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
        $salesReturn = $this->salesReturnService->createSalesReturn([
            'sales_id' => $this->sale->id,
            'return_date' => Carbon::now()->format('Y-m-d'),
            'reason' => 'Partial return',
            'status' => 'Completed',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 100],
            ]),
        ]);

        $this->assertEquals(100, $salesReturn->total_amount);
        $this->assertCount(1, $captured);

        // What the return SCREENS display...
        $derived = $this->salesReturnService->deriveReversal($this->sale, (float) $salesReturn->total_amount);

        $this->assertEquals(0.5, $derived['ratio']);
        $this->assertEquals(11.0, $derived['ppn']);
        $this->assertEquals(2.0, $derived['pph']);
        $this->assertEquals(60.0, $derived['cogs']);

        // ...must equal what the journal actually BOOKED.
        $bookedPpn = $this->sumTransactions($captured[0], $ppnKeluaranAccount->name, 'debit');
        $bookedPph = $this->sumTransactions($captured[0], $pphReceivableAccount->name, 'credit');
        $bookedCogs = $this->sumTransactions($captured[0], Account::find($settings['cost_of_goods_sold_account_id'])->name, 'credit');

        $this->assertEquals($bookedPpn, $derived['ppn'], 'Screen PPN must equal booked PPN');
        $this->assertEquals($bookedPph, $derived['pph'], 'Screen PPh must equal booked PPh');
        $this->assertEquals($bookedCogs, $derived['cogs'], 'Screen COGS must equal booked COGS');

        // And both are genuinely proportional, not the parent's full figures.
        $this->assertEquals(11.0, $bookedPpn);
        $this->assertEquals(2.0, $bookedPph);
        $this->assertEquals(60.0, $bookedCogs);
    }

    /** Sum transaction amounts matching an account name and direction. */
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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_reverses_pph_on_return()
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        $pphReceivableAccount = Account::create([
            'name' => 'PPh Receivable '.$this->tenant->id,
            'code' => '1170-'.$this->tenant->id,
            'type' => 'asset',
            'tenant_id' => $this->tenant->id,
        ]);
        $this->tenant->update([
            'accounting_settings' => array_merge(
                $this->tenant->accounting_settings ?? [],
                ['pph_receivable_account_id' => $pphReceivableAccount->id]
            ),
        ]);

        // Create sale with PPh
        $this->sale->update([
            'pph_enabled' => true,
            'pph_rate' => 2.0,
            'pph_amount' => 4.0,
        ]);

        $this->sale->salesItems()->create([
            'product_id' => $this->product->id,
            'quantity' => 5,
            'price' => 100,
            'customer_price' => 100,
            'discount' => 0,
            'total' => 500,
        ]);

        $this->accountingServiceMock->shouldReceive('createJournalEntry')->once();

        $returnData = [
            'sales_id' => $this->sale->id,
            'return_date' => Carbon::now()->format('Y-m-d'),
            'reason' => 'PPh reversal test',
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 100],
            ]),
        ];

        $salesReturn = $this->salesReturnService->createSalesReturn($returnData);

        $this->assertNotNull($salesReturn);
        $this->assertEquals(200, $salesReturn->total_amount);
    }

    /*
     * Re-journaling cases. These resolve the service from the container so
     * the REAL AccountingService runs - the double above mocks it, which
     * would record nothing to reverse. Mirrors PurchaseReturnServiceTest's
     * re-journal block: sales and purchase returns must describe an edit the
     * same way, or the GL disagrees with itself depending on which side of
     * the invoice the return landed on.
     */

    public function test_update_re_journals_the_gl_with_the_new_total()
    {
        $service = app(SalesReturnService::class);

        // Create: 2 × 50 = 100 → one posted entry, AR credited 100.
        $salesReturn = $service->createSalesReturn([
            'sales_id' => $this->sale->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Damaged in transit',
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);

        $originalEntries = JournalEntry::where('sourceable_type', SalesReturn::class)
            ->where('sourceable_id', $salesReturn->id)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->get();
        $this->assertCount(1, $originalEntries);
        $this->assertEqualsWithDelta(-100.0, $this->netAccountsReceivableEffect($salesReturn), 0.001);

        // Edit: 3 × 50 = 150 → old entry reversed, new entry posted for 150.
        $service->updateSalesReturn($salesReturn, [
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Damaged in transit (recount)',
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 3, 'price' => 50],
            ]),
        ]);

        $entries = JournalEntry::where('sourceable_type', SalesReturn::class)
            ->where('sourceable_id', $salesReturn->id)
            ->get();
        $this->assertCount(2, $entries);

        $original = $entries->firstWhere('id', $originalEntries->first()->id);
        $this->assertTrue($original->hasDependentEntries(), 'The pre-edit entry must be reversed.');

        $this->assertEqualsWithDelta(-150.0, $this->netAccountsReceivableEffect($salesReturn), 0.001);
    }

    public function test_a_second_edit_does_not_reverse_the_same_entry_twice()
    {
        $service = app(SalesReturnService::class);

        $salesReturn = $service->createSalesReturn([
            'sales_id' => $this->sale->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);
        $firstEntryId = JournalEntry::where('sourceable_type', SalesReturn::class)
            ->where('sourceable_id', $salesReturn->id)
            ->value('id');

        // Two successive edits. reverseJournalEntry() leaves the original
        // 'posted', so without the hasDependentEntries() guard the second
        // edit would reverse entry #1 a second time.
        $salesReturn = $service->updateSalesReturn($salesReturn, [
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 3, 'price' => 50],
            ]),
        ]);
        $salesReturn = $service->updateSalesReturn($salesReturn, [
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
            JournalEntry::where('sourceable_type', SalesReturn::class)
                ->where('sourceable_id', $salesReturn->id)
                ->get()
        );
        $this->assertEqualsWithDelta(-200.0, $this->netAccountsReceivableEffect($salesReturn), 0.001);
    }

    public function test_editing_status_to_canceled_reverses_without_reposting_then_reopens()
    {
        $service = app(SalesReturnService::class);
        $stockBefore = $this->product->fresh()->stock_quantity;

        $salesReturn = $service->createSalesReturn([
            'sales_id' => $this->sale->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);
        $firstEntryId = JournalEntry::where('sourceable_type', SalesReturn::class)
            ->where('sourceable_id', $salesReturn->id)
            ->value('id');
        $this->assertEquals($stockBefore + 2, $this->product->fresh()->stock_quantity);

        // Canceled via the edit form: reversal only, no fresh entry. A
        // canceled return must not leave a live GL impact behind.
        $salesReturn = $service->updateSalesReturn($salesReturn, [
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Canceled',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 50],
            ]),
        ]);

        $this->assertTrue(JournalEntry::findOrFail($firstEntryId)->hasDependentEntries());
        $this->assertEqualsWithDelta(0.0, $this->netAccountsReceivableEffect($salesReturn), 0.001);
        // The GL was not the only half: the old 2 units are handed back and
        // the new 1 is NOT taken in, so a Canceled return holds no stock. The
        // stock half used to re-increment unconditionally and left the
        // canceled row holding 1.
        $this->assertEquals($stockBefore, $this->product->fresh()->stock_quantity);

        // Re-open it: the already-reversed entry stays reversed exactly once
        // and a new entry carries the reopened total.
        $salesReturn = $service->updateSalesReturn($salesReturn, [
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 70],
            ]),
        ]);

        $this->assertEquals(1, JournalEntry::where('reversed_entry_id', $firstEntryId)->count());
        $this->assertEqualsWithDelta(-70.0, $this->netAccountsReceivableEffect($salesReturn), 0.001);
        // The reopened return holds exactly its new 1 unit: nothing was
        // handed back for the canceled state it came from, because a canceled
        // return held nothing to hand back.
        $this->assertEquals($stockBefore + 1, $this->product->fresh()->stock_quantity);
    }

    public function test_editing_a_completed_return_to_canceled_cancels_its_credit()
    {
        $service = app(SalesReturnService::class);

        // A paid sale: only a paid invoice's return issues a credit.
        $this->sale->update(['total' => 200]);
        $this->sale->payments()->create([
            'amount' => 200,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Cash',
        ]);

        $salesReturn = $service->createSalesReturn([
            'sales_id' => $this->sale->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Completed',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);

        $creditId = $salesReturn->fresh()->customer_credit_id;
        $this->assertNotNull($creditId, 'A completed return against a paid sale must issue its credit.');
        $this->assertDatabaseHas('customer_credits', ['id' => $creditId, 'status' => 'active']);

        $service->updateSalesReturn($salesReturn->fresh(), [
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Canceled',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);

        // The credit dies with the return: left active it was spendable
        // money against a row with no stock and no live journal entry. The
        // cancel path already did this; the edit path never did.
        $this->assertDatabaseHas('customer_credits', [
            'id' => $creditId,
            'status' => 'canceled',
            'available_amount' => 0,
        ]);

        // Reopen to Completed: the same credit is reactivated (not a second
        // one minted) and its amount is re-synced to the return's current
        // total. A reopened return holding a dead credit would show a
        // balance of zero against money the customer is owed.
        $service->updateSalesReturn($salesReturn->fresh(), [
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Completed',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 60],
            ]),
        ]);

        $this->assertDatabaseHas('customer_credits', [
            'id' => $creditId,
            'status' => 'active',
            'amount' => 120,
            'available_amount' => 120,
        ]);
        $this->assertEquals(
            1,
            CustomerCredit::where('sales_return_id', $salesReturn->id)->count(),
            'Reopening must reactivate the existing credit, never mint a second.'
        );
    }

    public function test_delete_after_edit_does_not_reverse_the_original_entry_twice()
    {
        $service = app(SalesReturnService::class);

        $salesReturn = $service->createSalesReturn([
            'sales_id' => $this->sale->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);
        $firstEntryId = JournalEntry::where('sourceable_type', SalesReturn::class)
            ->where('sourceable_id', $salesReturn->id)
            ->value('id');

        $salesReturn = $service->updateSalesReturn($salesReturn, [
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 3, 'price' => 50],
            ]),
        ]);

        $service->deleteSalesReturn($salesReturn);

        // Delete may only reverse the live post-edit entry; reversing the
        // already-reversed original again would swing the GL negative.
        $this->assertEquals(
            1,
            JournalEntry::where('reversed_entry_id', $firstEntryId)->count()
        );
        $this->assertEqualsWithDelta(0.0, $this->netAccountsReceivableEffect($salesReturn), 0.001);
    }

    public function test_completing_a_return_does_not_book_the_entry_create_already_posted()
    {
        $service = app(SalesReturnService::class);

        $salesReturn = $service->createSalesReturn([
            'sales_id' => $this->sale->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);
        $this->assertCount(
            1,
            JournalEntry::where('sourceable_type', SalesReturn::class)
                ->where('sourceable_id', $salesReturn->id)
                ->get()
        );

        $service->completeSalesReturn($salesReturn);

        // createSalesReturn() books the entry the moment the stock moves;
        // completion only issues the credit. Booking again here doubled every
        // Pending → Completed return's GL impact by exactly 100%.
        $this->assertCount(
            1,
            JournalEntry::where('sourceable_type', SalesReturn::class)
                ->where('sourceable_id', $salesReturn->id)
                ->get(),
            'completeSalesReturn() must not re-book what createSalesReturn() posted.'
        );
        $this->assertEqualsWithDelta(-100.0, $this->netAccountsReceivableEffect($salesReturn), 0.001);
        $this->assertEquals('Completed', $salesReturn->fresh()->status);
    }

    public function test_canceling_a_pending_return_reverts_the_stock_and_the_journal()
    {
        $service = app(SalesReturnService::class);
        $stockBefore = $this->product->fresh()->stock_quantity;

        // Created Pending: the stock is already in and the journal already
        // posted - that pair is the whole reason cancel has to act here.
        $salesReturn = $service->createSalesReturn([
            'sales_id' => $this->sale->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);
        $entryId = JournalEntry::where('sourceable_type', SalesReturn::class)
            ->where('sourceable_id', $salesReturn->id)
            ->value('id');

        $this->assertEquals($stockBefore + 2, $this->product->fresh()->stock_quantity);
        $this->assertEqualsWithDelta(-100.0, $this->netAccountsReceivableEffect($salesReturn), 0.001);

        $service->cancelSalesReturn($salesReturn);

        // Cancel from Pending used to skip every revert (Completed-only gate):
        // the goods stayed in stock and the GL kept a live entry against a
        // canceled return. Delete already reverted unconditionally; cancel now
        // holds the same contract.
        $this->assertEquals('Canceled', $salesReturn->fresh()->status);
        $this->assertEquals($stockBefore, $this->product->fresh()->stock_quantity);
        $this->assertEqualsWithDelta(0.0, $this->netAccountsReceivableEffect($salesReturn), 0.001);
        $this->assertTrue(JournalEntry::findOrFail($entryId)->hasDependentEntries());
        $this->assertEquals(
            1,
            JournalEntry::where('reversed_entry_id', $entryId)->count(),
            'The entry must be reversed exactly once.'
        );

        // Canceling twice is a no-op, not a second revert.
        $service->cancelSalesReturn($salesReturn);
        $this->assertEquals($stockBefore, $this->product->fresh()->stock_quantity);
        $this->assertEquals(1, JournalEntry::where('reversed_entry_id', $entryId)->count());
        $this->assertEqualsWithDelta(0.0, $this->netAccountsReceivableEffect($salesReturn), 0.001);
    }

    public function test_completing_a_canceled_return_is_refused_and_creates_no_credit()
    {
        $service = app(SalesReturnService::class);
        $stockBefore = $this->product->fresh()->stock_quantity;

        $salesReturn = $service->createSalesReturn([
            'sales_id' => $this->sale->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);
        $service->cancelSalesReturn($salesReturn);
        $salesReturn = $salesReturn->fresh();

        // The bulk action skips canceled rows, so this throw only ever fires
        // for a direct caller. Without it a Canceled row could be flipped to
        // Completed and mint a customer credit against a return that holds no
        // stock and has no live journal entry.
        $refused = false;
        try {
            $service->completeSalesReturn($salesReturn);
        } catch (\Exception $e) {
            $refused = true;
            $this->assertStringContainsString('canceled', $e->getMessage());
        }

        $this->assertTrue($refused, 'completeSalesReturn() must refuse a canceled return.');
        $this->assertEquals('Canceled', $salesReturn->fresh()->status);
        $this->assertDatabaseMissing('customer_credits', ['sales_return_id' => $salesReturn->id]);
        $this->assertEquals($stockBefore, $this->product->fresh()->stock_quantity);
    }

    public function test_bulk_complete_skips_canceled_rows_and_counts_them()
    {
        $service = app(SalesReturnService::class);

        $live = $service->createSalesReturn([
            'sales_id' => $this->sale->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 50],
            ]),
        ]);
        $canceled = $service->createSalesReturn([
            'sales_id' => $this->sale->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 50],
            ]),
        ]);
        $service->cancelSalesReturn($canceled);

        $counts = $service->bulkCompleteSalesReturns([$live->id, $canceled->id]);

        $this->assertEquals(1, $counts['completed']);
        $this->assertEquals(1, $counts['skipped']);
        $this->assertEquals('Completed', $live->fresh()->status);
        $this->assertEquals('Canceled', $canceled->fresh()->status);
    }

    public function test_deleting_a_canceled_return_does_not_move_the_stock_a_second_time()
    {
        $service = app(SalesReturnService::class);
        $stockBefore = $this->product->fresh()->stock_quantity;

        $salesReturn = $service->createSalesReturn([
            'sales_id' => $this->sale->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);
        $service->cancelSalesReturn($salesReturn);
        $this->assertEquals($stockBefore, $this->product->fresh()->stock_quantity);

        $service->deleteSalesReturn($salesReturn->fresh());

        // cancel already handed the 2 units back, so delete of a Canceled row
        // is a pure row removal: the unconditional revert it used to do took
        // the same goods twice and drove inventory below the sale.
        $this->assertDatabaseMissing('sales_returns', ['id' => $salesReturn->id]);
        $this->assertEquals($stockBefore, $this->product->fresh()->stock_quantity);
    }

    public function test_creating_a_return_born_canceled_moves_no_stock_and_posts_no_journal()
    {
        $service = app(SalesReturnService::class);
        $stockBefore = $this->product->fresh()->stock_quantity;

        $salesReturn = $service->createSalesReturn([
            'sales_id' => $this->sale->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Canceled',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);

        // The backstop: a return born Canceled holds nothing, so neither the
        // stock move nor the journal entry is ever made. Without it a seeder
        // or factory could mint a Canceled row that cancel would then owe a
        // double revert for.
        $this->assertEquals('Canceled', $salesReturn->status);
        $this->assertEquals($stockBefore, $this->product->fresh()->stock_quantity);
        $this->assertEquals(
            0,
            JournalEntry::where('sourceable_type', SalesReturn::class)
                ->where('sourceable_id', $salesReturn->id)
                ->count()
        );
        $this->assertDatabaseMissing('customer_credits', ['sales_return_id' => $salesReturn->id]);
    }

    public function test_delete_recalculates_the_sale_from_live_returns_only()
    {
        $service = app(SalesReturnService::class);

        $this->sale->salesItems()->create([
            'product_id' => $this->product->id,
            'quantity' => 10,
            'price' => 50,
            'customer_price' => 50,
            'discount' => 0,
            'total' => 500,
        ]);

        $live = $service->createSalesReturn([
            'sales_id' => $this->sale->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);
        $canceled = $service->createSalesReturn([
            'sales_id' => $this->sale->id,
            'return_date' => now()->format('Y-m-d'),
            // Born Canceled: no stock move and no journal entry - the row
            // exists purely to be counted (or not) by the recalc under test.
            'status' => 'Canceled',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 50],
            ]),
        ]);

        // createSalesReturn() closes with updateSaleStatus(), which normalizes
        // on payments only: this factory sale has none, so it reads Unpaid.
        $this->sale->refresh();
        $this->assertEquals('Unpaid', $this->sale->status);

        $service->deleteSalesReturn($live->fresh());

        // Without the live-only recount the canceled sibling (2 units) kept
        // the sale on Partial with nothing actually returned. The recount
        // reverts to Unpaid, same as create, edit and cancel already do.
        $this->sale->refresh();
        $this->assertEquals('Unpaid', $this->sale->status);
        $this->assertEquals(
            0,
            $this->sale->salesReturns()->where('status', '!=', 'Canceled')->count(),
            'No live returns may remain after the delete.'
        );
        $this->assertEquals(
            1,
            $this->sale->salesReturns()->count(),
            'The canceled row itself must stay for history.'
        );
    }

    /**
     * Net debit/credit effect on Accounts Receivable from everything
     * journaled against this return: the sourceable entries plus their
     * reversals (reversal entries carry no sourceable themselves). A sales
     * return CREDITS AR, so the figure is negative: the customer owes less.
     */
    private function netAccountsReceivableEffect(SalesReturn $salesReturn): float
    {
        $entryIds = JournalEntry::where('sourceable_type', SalesReturn::class)
            ->where('sourceable_id', $salesReturn->id)
            ->pluck('id');

        $reversalIds = JournalEntry::whereIn('reversed_entry_id', $entryIds)->pluck('id');

        $accountsReceivableId = $this->tenant->accounting_settings['accounts_receivable_account_id'];

        return (float) Transaction::whereIn('journal_entry_id', $entryIds->merge($reversalIds))
            ->where('account_id', $accountsReceivableId)
            ->selectRaw("SUM(CASE WHEN type = 'debit' THEN amount ELSE -amount END) AS net")
            ->value('net');
    }
}
