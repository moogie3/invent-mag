<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Tax;
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
}
