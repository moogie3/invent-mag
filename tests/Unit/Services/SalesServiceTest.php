<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\SalesService;
use Tests\Unit\BaseUnitTestCase;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;

class SalesServiceTest extends BaseUnitTestCase
{
    protected SalesService $salesService;
    protected User $user;
    protected MockInterface $accountingServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accountingServiceMock = Mockery::mock(AccountingService::class);
        $this->salesService = new SalesService($this->accountingServiceMock);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(SalesService::class, $this->salesService);
    }

    #[Test]
    public function it_can_get_sales_index_data()
    {
        Sales::factory()->count(5)->create();
        $data = $this->salesService->getSalesIndexData([], 10);

        $this->assertArrayHasKey('sales', $data);
        $this->assertArrayHasKey('totalinvoice', $data);
        $this->assertArrayHasKey('unpaidDebt', $data);
        $this->assertArrayHasKey('totalMonthly', $data);
        $this->assertArrayHasKey('pendingOrders', $data);
        $this->assertArrayHasKey('dueInvoices', $data);
        $this->assertArrayHasKey('posTotal', $data);
    }

    #[Test]
    public function it_can_get_sales_create_data()
    {
        $data = $this->salesService->getSalesCreateData();

        $this->assertArrayHasKey('sales', $data);
        $this->assertArrayHasKey('customers', $data);
        $this->assertArrayHasKey('products', $data);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('tax', $data);
    }

    #[Test]
    public function it_can_get_sales_edit_data()
    {
        $sale = Sales::factory()->create();
        $data = $this->salesService->getSalesEditData($sale->id);

        $this->assertArrayHasKey('sales', $data);
        $this->assertArrayHasKey('customers', $data);
        $this->assertArrayHasKey('tax', $data);
        $this->assertArrayHasKey('isPaid', $data);
    }

    #[Test]
    public function it_can_get_sales_view_data()
    {
        $sale = Sales::factory()->has(SalesItem::factory()->count(2), 'salesItems')->create();
        $data = $this->salesService->getSalesViewData($sale->id);

        $this->assertArrayHasKey('sales', $data);
        $this->assertArrayHasKey('customer', $data);
        $this->assertArrayHasKey('tax', $data);
        $this->assertArrayHasKey('summary', $data);
    }

    #[Test]
    public function it_can_get_sales_metrics()
    {
        Sales::factory()->count(3)->create();
        $metrics = $this->salesService->getSalesMetrics();

        $this->assertArrayHasKey('totalinvoice', $metrics);
        $this->assertArrayHasKey('unpaidDebt', $metrics);
        $this->assertArrayHasKey('totalMonthly', $metrics);
        $this->assertArrayHasKey('pendingOrders', $metrics);
        $this->assertArrayHasKey('dueInvoices', $metrics);
        $this->assertArrayHasKey('posTotal', $metrics);
    }

    #[Test]
    public function it_can_create_a_sale()
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 50]);
        $products = json_encode([
            ['product_id' => $product->id, 'quantity' => 2, 'customer_price' => 100, 'discount' => 0, 'discount_type' => 'fixed'],
        ]);
        $data = [
            'products' => $products,
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
        ];

        $this->accountingServiceMock->shouldReceive('createJournalEntry')->once();

        $sale = $this->salesService->createSale($data);

        $this->assertInstanceOf(Sales::class, $sale);
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'customer_id' => $customer->id]);
        $this->assertDatabaseHas('sales_items', ['sales_id' => $sale->id, 'product_id' => $product->id]);
        $this->assertEquals(8, $product->fresh()->stock_quantity);
    }

    #[Test]
    public function it_creates_a_journal_entry_on_sale_creation()
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 50]); // Purchase price = 50
        $products = json_encode([
            ['product_id' => $product->id, 'quantity' => 2, 'customer_price' => 100], // Selling price = 100
        ]);
        $data = [
            'products' => $products,
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
        ];

        $grandTotal = 200; // 2 * 100
        $cogs = 100; // 2 * 50

        $this->accountingServiceMock
            ->shouldReceive('createJournalEntry')
            ->once()
            ->with(
                Mockery::any(), // description
                Mockery::any(), // date
                Mockery::on(function ($transactions) use ($grandTotal, $cogs) {
                    $this->assertCount(4, $transactions);
                    $this->assertEquals($grandTotal, $this->findTransactionAmount($transactions, 'Accounts Receivable', 'debit'));
                    $this->assertEquals($grandTotal, $this->findTransactionAmount($transactions, 'Sales Revenue', 'credit'));
                    $this->assertEquals($cogs, $this->findTransactionAmount($transactions, 'Cost of Goods Sold', 'debit'));
                    $this->assertEquals($cogs, $this->findTransactionAmount($transactions, 'Inventory', 'credit'));
                    return true;
                }),
                Mockery::type(Sales::class)
            );

        $this->salesService->createSale($data);
    }

    #[Test]
    public function it_creates_a_journal_entry_on_payment()
    {
        $sale = Sales::factory()->create(['total' => 1000]);
        $data = [
            'amount' => 500,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Cash',
        ];

        $this->accountingServiceMock
            ->shouldReceive('createJournalEntry')
            ->once()
            ->with(
                "Payment for Sale #{$sale->invoice}",
                Mockery::any(),
                Mockery::on(function ($transactions) {
                    $this->assertCount(2, $transactions);
                    $this->assertEquals(500, $this->findTransactionAmount($transactions, 'Cash', 'debit'));
                    $this->assertEquals(500, $this->findTransactionAmount($transactions, 'Accounts Receivable', 'credit'));
                    return true;
                }),
                Mockery::any() // Payment model
            );

        $this->salesService->addPayment($sale, $data);
    }


    #[Test]
    public function it_can_update_a_sale()
    {
        $sale = Sales::factory()->has(SalesItem::factory()->count(1), 'salesItems')->create();
        $oldItem = $sale->salesItems->first();
        $product = Product::find($oldItem->product_id);
        $initialStock = $product->stock_quantity;

        $newProduct = Product::factory()->create(['stock_quantity' => 20]);
        $products = json_encode([
            ['product_id' => $newProduct->id, 'quantity' => 5, 'customer_price' => 200, 'discount' => 10, 'discount_type' => 'percentage'],
        ]);
        $data = [
            'products' => $products,
            'customer_id' => $sale->customer_id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addMonths(2)->toDateString(),
            'order_discount' => 5,
            'order_discount_type' => 'fixed',
        ];

        $this->salesService->updateSale($sale, $data);

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'order_discount' => 5]);
        $this->assertDatabaseMissing('sales_items', ['id' => $oldItem->id]);
        $this->assertDatabaseHas('sales_items', ['sales_id' => $sale->id, 'product_id' => $newProduct->id]);
        $this->assertEquals($initialStock + $oldItem->quantity, Product::find($oldItem->product_id)->fresh()->stock_quantity);
        $this->assertEquals(15, $newProduct->fresh()->stock_quantity);
    }

    #[Test]
    public function it_can_add_a_payment()
    {
        $sale = Sales::factory()->create(['total' => 1000]);
        $data = [
            'amount' => 500,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Cash',
        ];

        $this->accountingServiceMock->shouldReceive('createJournalEntry')->once();

        $this->salesService->addPayment($sale, $data);

        $this->assertDatabaseHas('payments', ['paymentable_id' => $sale->id, 'amount' => 500]);
        $this->assertEquals('Partial', $sale->fresh()->status);
    }

    #[Test]
    public function it_can_update_sale_status()
    {
        $sale = Sales::factory()->create(['total' => 1000]);

        // Test Partial
        $sale->payments()->create(['amount' => 500, 'payment_date' => now()->toDateString(), 'payment_method' => 'Cash']);
        $this->salesService->updateSaleStatus($sale->fresh());
        $this->assertEquals('Partial', $sale->fresh()->status);

        // Test Paid
        $sale->payments()->create(['amount' => 500, 'payment_date' => now()->toDateString(), 'payment_method' => 'Cash']);
        $this->salesService->updateSaleStatus($sale->fresh());
        $this->assertEquals('Paid', $sale->fresh()->status);

        // Test Unpaid
        $sale->payments()->delete();
        $this->salesService->updateSaleStatus($sale->fresh());
        $this->assertEquals('Unpaid', $sale->fresh()->status);
    }

    #[Test]
    public function it_can_delete_a_sale()
    {
        $sale = Sales::factory()->has(SalesItem::factory()->count(1), 'salesItems')->create();
        $item = $sale->salesItems->first();
        $product = Product::find($item->product_id);
        $initialStock = $product->stock_quantity;

        $this->salesService->deleteSale($sale);

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertDatabaseMissing('sales_items', ['id' => $item->id]);
        $this->assertEquals($initialStock + $item->quantity, $product->fresh()->stock_quantity);
    }

    #[Test]
    public function it_can_bulk_delete_sales()
    {
        $sales = Sales::factory()->count(3)->has(SalesItem::factory()->count(1), 'salesItems')->create();
        $ids = $sales->pluck('id')->toArray();

        $this->salesService->bulkDeleteSales($ids);

        foreach ($sales as $sale) {
            $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        }
    }

    #[Test]
    public function it_can_bulk_mark_sales_as_paid()
    {
        $sales = Sales::factory()->count(3)->create(['total' => 100, 'status' => 'Unpaid']);
        $ids = $sales->pluck('id')->toArray();

        $this->accountingServiceMock->shouldReceive('createJournalEntry')->times(3);

        $count = $this->salesService->bulkMarkPaid($ids);

        $this->assertEquals(3, $count);
        foreach ($sales as $sale) {
            $this->assertEquals('Paid', $sale->fresh()->status);
        }
    }

    #[Test]
    public function it_can_get_sales_for_modal()
    {
        $sale = Sales::factory()->create();
        $result = $this->salesService->getSalesForModal($sale->id);

        $this->assertEquals($sale->id, $result->id);
    }

    #[Test]
    public function it_can_get_past_customer_price_for_product()
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $sale = Sales::factory()->create(['customer_id' => $customer->id]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $product->id, 'customer_price' => 150]);

        $price = $this->salesService->getPastCustomerPriceForProduct($customer, $product);

        $this->assertEquals(150, $price);
    }

    #[Test]
    public function it_can_get_expiring_sales_count()
    {
        Sales::factory()->create(['due_date' => now()->addDays(5), 'status' => 'Unpaid']);
        Sales::factory()->create(['due_date' => now()->addDays(20), 'status' => 'Unpaid']);
        Sales::factory()->create(['due_date' => now()->addDays(5), 'status' => 'Paid']);

        $count = $this->salesService->getExpiringSalesCount();

        $this->assertEquals(1, $count);
    }

    #[Test]
    public function it_can_get_expiring_sales()
    {
        Sales::factory()->create(['due_date' => now()->addDays(5), 'status' => 'Unpaid']);
        Sales::factory()->create(['due_date' => now()->addDays(100), 'status' => 'Unpaid']);

        $expiringSales = $this->salesService->getExpiringSales();

        $this->assertCount(1, $expiringSales);
    }

    private function findTransactionAmount(array $transactions, string $accountName, string $type): ?float
    {
        foreach ($transactions as $transaction) {
            if ($transaction['account_name'] === $accountName && $transaction['type'] === $type) {
                return $transaction['amount'];
            }
        }
        return null;
    }
}
