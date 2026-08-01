<?php

namespace Tests\Unit\Services;

use App\Models\POItem;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
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
        $purchaseReturn = PurchaseReturn::factory()->create([
            'purchase_id' => $this->purchase->id,
            'total_amount' => 100,
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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
