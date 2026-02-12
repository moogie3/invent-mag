<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\Sales;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Services\SalesReturnService;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;
use Carbon\Carbon;
use Tests\Traits\CreatesTenant;

class SalesReturnServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected User $user;
    protected Sales $sale;
    protected Product $product;
    protected SalesReturnService $salesReturnService;
    protected $accountingServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant(); // Creates $this->tenant and $this->user, and calls actingAs
        $this->user->assignRole('superuser'); // Ensure the user has permissions for services

        $this->product = Product::factory()->withStock(100)->create();
        $this->sale = Sales::factory()->create();
        
        // Mock AccountingService
        $this->accountingServiceMock = Mockery::mock(AccountingService::class);
        $this->salesReturnService = new SalesReturnService($this->accountingServiceMock);
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

        $returnData = [
            'sales_id' => $this->sale->id,
            'return_date' => Carbon::now()->format('Y-m-d'),
            'reason' => 'Customer changed mind',
            'status' => 'Pending',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 100, 'return_price' => 100]
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
        $this->sale->refresh();
        $this->assertEquals('Partial', $this->sale->status);
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
            'total' => 500
        ]);
        
        $salesReturn = SalesReturn::factory()->create([
            'sales_id' => $this->sale->id,
            'total_amount' => 100,
            'status' => 'Pending'
        ]);
        SalesReturnItem::factory()->create([
            'sales_return_id' => $salesReturn->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100
        ]);
        
        $this->product->refresh();
        $this->assertEquals(100, $this->product->stock_quantity); // Initial stock

        $updateData = [
            'sales_id' => $this->sale->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Updated reason',
            'status' => 'Completed',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 3, 'price' => 50]
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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
