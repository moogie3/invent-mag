<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Services\PurchaseReturnService;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Traits\CreatesTenant;

class PurchaseReturnServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

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
        
        $this->product = Product::factory()->create(['stock_quantity' => 100]);
        $this->purchase = Purchase::factory()->create();
        
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
            'total_amount' => 100
        ]);
        PurchaseReturnItem::factory()->create([
            'purchase_return_id' => $purchaseReturn->id,
            'product_id' => $this->product->id,
            'quantity' => 1
        ]);
        
        $this->product->decrement('stock_quantity', 1);
        $this->product->refresh();
        $this->assertEquals(99, $this->product->stock_quantity);

        $updateData = [
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Updated reason',
            'status' => 'Completed',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 3, 'price' => 50]
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
