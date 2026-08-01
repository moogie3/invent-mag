<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Tax;
use App\Models\User; // Added
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\SalesService;
use Database\Seeders\AccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class SalesServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected SalesService $salesService;

    protected MockInterface $accountingServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->accountingServiceMock = Mockery::mock(AccountingService::class);
        $this->salesService = new SalesService($this->accountingServiceMock);

        // Seed the accounts for the current tenant
        $this->seed(AccountSeeder::class);

        // Retrieve SAK-compliant accounts from the seeder
        $tenantId = app('currentTenant')->id;
        $cash = Account::where('code', '1110-'.$tenantId)->first();
        $accountsReceivable = Account::where('code', '1130-'.$tenantId)->first();
        $salesRevenue = Account::where('code', '4100-'.$tenantId)->first();
        $costOfGoodsSold = Account::where('code', '5200-'.$tenantId)->first();
        $inventory = Account::where('code', '1140-'.$tenantId)->first();

        // Ensure accounts exist
        $this->assertNotNull($cash, 'Cash account not found in seeder.');
        $this->assertNotNull($accountsReceivable, 'Accounts Receivable account not found in seeder.');
        $this->assertNotNull($salesRevenue, 'Sales Revenue account not found in seeder.');
        $this->assertNotNull($costOfGoodsSold, 'Cost of Goods Sold account not found in seeder.');
        $this->assertNotNull($inventory, 'Inventory account not found in seeder.');

        // The tenant is already created and current by the CreatesTenant trait.
        // Update the tenant's accounting settings (settings belong to the tenant, not a user).
        $this->tenant->update([
            'accounting_settings' => [
                'cash_account_id' => $cash->id,
                'accounts_receivable_account_id' => $accountsReceivable->id,
                'sales_revenue_account_id' => $salesRevenue->id,
                'cost_of_goods_sold_account_id' => $costOfGoodsSold->id,
                'inventory_account_id' => $inventory->id,
            ],
        ]);

        // Refresh the user instance and update Auth
        $this->user = $this->user->fresh();
        Auth::setUser($this->user);
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
        $warehouse = Warehouse::factory()->create(['is_main' => true]);

        $product = Product::factory()->create(['price' => 50]);
        // Set initial stock in warehouse
        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 10]
        );

        $products = json_encode([
            ['product_id' => $product->id, 'quantity' => 2, 'customer_price' => 100, 'discount' => 0, 'discount_type' => 'fixed'],
        ]);
        $data = [
            'products' => $products,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id, // Added
            'order_date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
        ];

        $this->accountingServiceMock->shouldReceive('createJournalEntry')->once();

        $sale = $this->salesService->createSale($data);

        $this->assertInstanceOf(Sales::class, $sale);
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id]);
        $this->assertDatabaseHas('sales_items', ['sales_id' => $sale->id, 'product_id' => $product->id]);

        // Verify stock in pivot table
        $this->assertDatabaseHas('product_warehouse', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 8,
        ]);
    }

    #[Test]
    public function it_creates_a_journal_entry_on_sale_creation()
    {
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $product = Product::factory()->create(['price' => 50]);
        // Set initial stock
        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 10]
        );

        $products = json_encode([
            ['product_id' => $product->id, 'quantity' => 2, 'customer_price' => 100], // Selling price = 100
        ]);
        $data = [
            'products' => $products,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
        ];

        $grandTotal = 200; // 2 * 100
        $cogs = 100; // 2 * 50

        $accountingSettings = app('currentTenant')->accounting_settings;
        $salesRevenueAccountName = Account::find($accountingSettings['sales_revenue_account_id'])->name;
        $accountsReceivableAccountName = Account::find($accountingSettings['accounts_receivable_account_id'])->name;
        $costOfGoodsSoldAccountName = Account::find($accountingSettings['cost_of_goods_sold_account_id'])->name;
        $inventoryAccountName = Account::find($accountingSettings['inventory_account_id'])->name;

        $this->accountingServiceMock
            ->shouldReceive('createJournalEntry')
            ->once()
            ->with(
                Mockery::any(), // description
                Mockery::any(), // date
                Mockery::on(function ($transactions) use ($grandTotal, $cogs, $salesRevenueAccountName, $accountsReceivableAccountName, $costOfGoodsSoldAccountName, $inventoryAccountName) {
                    $this->assertCount(4, $transactions);
                    $this->assertEquals($grandTotal, $this->findTransactionAmount($transactions, $accountsReceivableAccountName, 'debit'));
                    $this->assertEquals($grandTotal, $this->findTransactionAmount($transactions, $salesRevenueAccountName, 'credit'));
                    $this->assertEquals($cogs, $this->findTransactionAmount($transactions, $costOfGoodsSoldAccountName, 'debit'));
                    $this->assertEquals($cogs, $this->findTransactionAmount($transactions, $inventoryAccountName, 'credit'));

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
                    $accountingSettings = app('currentTenant')->accounting_settings;
                    $cashAccountName = Account::find($accountingSettings['cash_account_id'])->name;
                    $accountsReceivableAccountName = Account::find($accountingSettings['accounts_receivable_account_id'])->name;

                    $this->assertCount(2, $transactions);
                    $this->assertEquals(500, $this->findTransactionAmount($transactions, $cashAccountName, 'debit'));
                    $this->assertEquals(500, $this->findTransactionAmount($transactions, $accountsReceivableAccountName, 'credit'));

                    return true;
                }),
                Mockery::any() // Payment model
            );

        $this->salesService->addPayment($sale, $data);
    }

    #[Test]
    public function it_can_update_a_sale()
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 10]
        );

        $sale = Sales::factory()->create(['warehouse_id' => $warehouse->id]);
        $oldItem = SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $product->id, 'quantity' => 1]);

        // Stock should have been decremented when sale was created (conceptually)
        // Since we created sale/items directly via factory, stock wasn't auto-decremented.
        // Let's manually set pivot to what it "should" be after sale: 9.
        ProductWarehouse::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->update(['quantity' => 9]);

        $newProduct = Product::factory()->create();
        ProductWarehouse::updateOrCreate(
            ['product_id' => $newProduct->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $newProduct->tenant_id],
            ['quantity' => 20]
        );

        $products = json_encode([
            ['product_id' => $newProduct->id, 'quantity' => 5, 'customer_price' => 200, 'discount' => 10, 'discount_type' => 'percentage'],
        ]);
        $data = [
            'products' => $products,
            'customer_id' => $sale->customer_id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addMonths(2)->toDateString(),
            'discount_total' => 5,
            'discount_total_type' => 'fixed',
        ];

        $this->salesService->updateSale($sale, $data);

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'order_discount' => 5]);
        $this->assertDatabaseMissing('sales_items', ['id' => $oldItem->id]);
        $this->assertDatabaseHas('sales_items', ['sales_id' => $sale->id, 'product_id' => $newProduct->id]);

        // Old product stock should revert: 9 + 1 = 10
        $this->assertDatabaseHas('product_warehouse', ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 10]);

        // New product stock should decrement: 20 - 5 = 15
        $this->assertDatabaseHas('product_warehouse', ['product_id' => $newProduct->id, 'warehouse_id' => $warehouse->id, 'quantity' => 15]);
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
        $warehouse = Warehouse::factory()->create();
        $sale = Sales::factory()->create(['warehouse_id' => $warehouse->id]);
        $product = Product::factory()->create();
        $item = SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $product->id, 'quantity' => 1]);

        // Setup initial stock state as if sale happened
        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 9]
        );

        $this->salesService->deleteSale($sale);

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertDatabaseMissing('sales_items', ['id' => $item->id]);

        // Stock should revert: 9 + 1 = 10
        $this->assertDatabaseHas('product_warehouse', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
        ]);
    }

    #[Test]
    public function it_can_bulk_delete_sales()
    {
        $warehouse = Warehouse::factory()->create();
        // Create 3 sales
        $sales = Sales::factory()->count(3)->create(['warehouse_id' => $warehouse->id]);

        foreach ($sales as $sale) {
            $product = Product::factory()->create();
            SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $product->id, 'quantity' => 1]);

            // Init stock
            ProductWarehouse::updateOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $sale->tenant_id],
                ['quantity' => 9]
            );
        }

        $ids = $sales->pluck('id')->toArray();

        $this->salesService->bulkDeleteSales($ids);

        foreach ($sales as $sale) {
            $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        }
    }

    #[Test]
    public function it_can_bulk_mark_sales_as_paid()
    {
        $product = Product::factory()->create();
        $sales = Sales::factory()->count(3)->create(['total' => 100, 'status' => 'Unpaid', 'amount_received' => 0]);

        foreach ($sales as $sale) {
            SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $product->id, 'quantity' => 1, 'total' => 100]);
        }

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

    #[Test]
    public function it_stores_pph_snapshot_when_creating_sale_with_pph_tax()
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        // Create a separate regular tax so it doesn't get auto-selected as PPh
        $regularTax = Tax::create([
            'name' => 'PPN 11%',
            'rate' => 11,
            'type' => 'exclusive',
            'is_active' => true,
            'is_default' => true,
            'is_pph' => false,
        ]);

        $pphTax = Tax::create([
            'name' => 'PPh 23',
            'rate' => 2,
            'type' => 'exclusive',
            'is_active' => true,
            'is_pph' => true,
        ]);

        // Create a PPh receivable account for journal entries
        $pphReceivableAccount = Account::create([
            'name' => 'PPh Receivable '.$this->tenant->id,
            'code' => '1150'.$this->tenant->id,
            'type' => 'asset',
            'tenant_id' => $this->tenant->id,
        ]);
        $this->tenant->update([
            'accounting_settings' => array_merge(
                $this->tenant->accounting_settings ?? [],
                ['pph_receivable_account_id' => $pphReceivableAccount->id]
            ),
        ]);

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create(['is_main' => true]);
        $product = Product::factory()->create(['price' => 50]);
        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 10]
        );

        $products = json_encode([
            ['product_id' => $product->id, 'quantity' => 2, 'customer_price' => 100, 'discount' => 0, 'discount_type' => 'fixed'],
        ]);
        $data = [
            'products' => $products,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
            'tax_id' => $regularTax->id,
            'pph_tax_id' => $pphTax->id,
        ];

        $this->accountingServiceMock->shouldReceive('createJournalEntry')->times(2);

        $sale = $this->salesService->createSale($data);

        $this->assertTrue($sale->fresh()->pph_enabled);
        $this->assertEquals(2.0, $sale->fresh()->pph_rate);
        // PPh = 200 * 2% = 4.00
        $this->assertEquals(4.0, $sale->fresh()->pph_amount);
        $this->assertEquals($pphTax->id, $sale->fresh()->pph_tax_id);

        // Subtotal 200, PPN 11% = 22, PPh 2% = 4
        // Grand total = 200 + 22 - 4 = 218
        $this->assertEquals(218.0, $sale->fresh()->total);
    }

    #[Test]
    public function it_does_not_store_pph_when_tenant_not_indonesian()
    {
        $pphTax = Tax::create([
            'name' => 'PPh 23',
            'rate' => 2,
            'type' => 'exclusive',
            'is_active' => true,
            'is_pph' => true,
        ]);

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create(['is_main' => true]);
        $product = Product::factory()->create(['price' => 50]);
        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 10]
        );

        $products = json_encode([
            ['product_id' => $product->id, 'quantity' => 2, 'customer_price' => 100, 'discount' => 0, 'discount_type' => 'fixed'],
        ]);
        $data = [
            'products' => $products,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
            'pph_tax_id' => $pphTax->id,
        ];

        $this->accountingServiceMock->shouldReceive('createJournalEntry')->once();

        $sale = $this->salesService->createSale($data);

        $this->assertFalse($sale->fresh()->pph_enabled);
        $this->assertEquals(0.0, $sale->fresh()->pph_amount);
        $this->assertNull($sale->fresh()->pph_tax_id);

        // Grand total = 200 (no PPh subtracted)
        $this->assertEquals(200.0, $sale->fresh()->total);
    }

    #[Test]
    public function it_creates_pph_journal_entry_when_pph_applied()
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        $pphTax = Tax::create([
            'name' => 'PPh 23',
            'rate' => 2,
            'type' => 'exclusive',
            'is_active' => true,
            'is_pph' => true,
        ]);

        $pphReceivableAccount = Account::create([
            'name' => 'PPh Receivable '.$this->tenant->id,
            'code' => '1150'.$this->tenant->id,
            'type' => 'asset',
            'tenant_id' => $this->tenant->id,
        ]);
        $this->tenant->update([
            'accounting_settings' => array_merge(
                $this->tenant->accounting_settings ?? [],
                ['pph_receivable_account_id' => $pphReceivableAccount->id]
            ),
        ]);

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create(['is_main' => true]);
        $product = Product::factory()->create(['price' => 50]);
        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 10]
        );

        $products = json_encode([
            ['product_id' => $product->id, 'quantity' => 2, 'customer_price' => 100, 'discount' => 0, 'discount_type' => 'fixed'],
        ]);
        $data = [
            'products' => $products,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
            'pph_tax_id' => $pphTax->id,
        ];

        $this->accountingServiceMock
            ->shouldReceive('createJournalEntry')
            ->times(2);

        $this->salesService->createSale($data);
    }

    #[Test]
    public function it_does_not_create_pph_journal_entry_without_pph_receivable_account()
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        $pphTax = Tax::create([
            'name' => 'PPh 23',
            'rate' => 2,
            'type' => 'exclusive',
            'is_active' => true,
            'is_pph' => true,
        ]);

        // Do NOT create pph_receivable_account_id setting
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create(['is_main' => true]);
        $product = Product::factory()->create(['price' => 50]);
        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 10]
        );

        $products = json_encode([
            ['product_id' => $product->id, 'quantity' => 2, 'customer_price' => 100, 'discount' => 0, 'discount_type' => 'fixed'],
        ]);
        $data = [
            'products' => $products,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
            'pph_tax_id' => $pphTax->id,
        ];

        // Only main sale journal entry, no PPh journal entry
        $this->accountingServiceMock->shouldReceive('createJournalEntry')->once();

        $sale = $this->salesService->createSale($data);

        // PPh snapshot is still stored
        $this->assertTrue($sale->fresh()->pph_enabled);
        $this->assertEquals(4.0, $sale->fresh()->pph_amount);
    }

    #[Test]
    public function it_stores_pph_snapshot_when_updating_sale_with_pph_tax()
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        $pphTax = Tax::create([
            'name' => 'PPh 23',
            'rate' => 2,
            'type' => 'exclusive',
            'is_active' => true,
            'is_pph' => true,
        ]);

        $pphReceivableAccount = Account::create([
            'name' => 'PPh Receivable '.$this->tenant->id,
            'code' => '1150'.$this->tenant->id,
            'type' => 'asset',
            'tenant_id' => $this->tenant->id,
        ]);
        $this->tenant->update([
            'accounting_settings' => array_merge(
                $this->tenant->accounting_settings ?? [],
                ['pph_receivable_account_id' => $pphReceivableAccount->id]
            ),
        ]);

        $warehouse = Warehouse::factory()->create(['is_main' => true]);
        $product = Product::factory()->create(['price' => 50]);
        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 10]
        );

        $sale = Sales::factory()->create([
            'warehouse_id' => $warehouse->id,
            'pph_enabled' => false,
            'pph_amount' => 0,
        ]);
        SalesItem::factory()->create([
            'sales_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'customer_price' => 100,
            'total' => 100,
        ]);

        $products = json_encode([
            ['product_id' => $product->id, 'quantity' => 2, 'customer_price' => 100, 'discount' => 0, 'discount_type' => 'fixed'],
        ]);
        $data = [
            'products' => $products,
            'customer_id' => $sale->customer_id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
            'pph_tax_id' => $pphTax->id,
        ];

        $this->salesService->updateSale($sale, $data);

        $sale->refresh();
        $this->assertTrue($sale->pph_enabled);
        $this->assertEquals(2.0, $sale->pph_rate);
        $this->assertEquals(4.0, $sale->pph_amount);
        $this->assertEquals($pphTax->id, $sale->pph_tax_id);
    }

    #[Test]
    public function it_pph_reduces_grand_total_correctly_in_snapshot()
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        // Regular tax 10% + PPh 2%
        $regularTax = Tax::create([
            'name' => 'PPN 10%',
            'rate' => 10,
            'type' => 'exclusive',
            'is_active' => true,
            'is_pph' => false,
        ]);
        $pphTax = Tax::create([
            'name' => 'PPh 23',
            'rate' => 2,
            'type' => 'exclusive',
            'is_active' => true,
            'is_pph' => true,
        ]);

        $pphReceivableAccount = Account::create([
            'name' => 'PPh Receivable '.$this->tenant->id,
            'code' => '1150'.$this->tenant->id,
            'type' => 'asset',
            'tenant_id' => $this->tenant->id,
        ]);
        $this->tenant->update([
            'accounting_settings' => array_merge(
                $this->tenant->accounting_settings ?? [],
                ['pph_receivable_account_id' => $pphReceivableAccount->id]
            ),
        ]);

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create(['is_main' => true]);
        $product = Product::factory()->create(['price' => 50]);
        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 10]
        );

        $products = json_encode([
            ['product_id' => $product->id, 'quantity' => 2, 'customer_price' => 100, 'discount' => 0, 'discount_type' => 'fixed'],
        ]);
        $data = [
            'products' => $products,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
            'tax_id' => $regularTax->id,
            'pph_tax_id' => $pphTax->id,
        ];

        $this->accountingServiceMock->shouldReceive('createJournalEntry')->times(2);

        $sale = $this->salesService->createSale($data);

        // Subtotal = 200, PPN = 20, PPh = 4
        // Grand total = 200 + 20 - 4 = 216
        $this->assertEquals(216.0, $sale->fresh()->total);
        $this->assertEquals(20.0, $sale->fresh()->total_tax);
        $this->assertEquals(4.0, $sale->fresh()->pph_amount);
        $this->assertTrue($sale->fresh()->pph_enabled);
    }
}
