<?php

namespace Tests\Unit\Services;

use App\Models\POItem; // Added
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Account;
use App\Models\Warehouse; // Added
use App\Models\ProductWarehouse;
use App\Services\AccountingService;
use App\Services\PurchaseService;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Mockery\MockInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\AccountSeeder;

class PurchaseServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected PurchaseService $purchaseService;
    protected User $user;
    protected MockInterface $accountingServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant(); // Creates $this->tenant and $this->user, and calls actingAs
        $this->user->assignRole('superuser'); // Ensure the user has permissions for services

        $this->accountingServiceMock = Mockery::mock(AccountingService::class);
        $this->purchaseService = new PurchaseService($this->accountingServiceMock);

        // Seed the accounts for the current tenant
        $this->seed(AccountSeeder::class);

        // Retrieve SAK-compliant accounts from the seeder, scoped to the current tenant
        $tenantId = app('currentTenant')->id;
        $cash = Account::where('code', '1110-' . $tenantId)->first();
        $accountsPayable = Account::where('code', '2110-' . $tenantId)->first();
        $inventory = Account::where('code', '1140-' . $tenantId)->first();

        // Ensure accounts exist (they should, due to AccountSeeder)
        $this->assertNotNull($cash, 'Cash account not found in seeder.');
        $this->assertNotNull($accountsPayable, 'Accounts Payable account not found in seeder.');
        $this->assertNotNull($inventory, 'Inventory account not found in seeder.');

        // Update the existing user (created by setupTenant) with accounting settings
        $this->user->update([
            'accounting_settings' => [
                'accounts_payable_account_id' => $accountsPayable->id,
                'inventory_account_id' => $inventory->id,
                'cash_account_id' => $cash->id,
            ]
        ]);
        
        $this->user = $this->user->fresh();
        Auth::setUser($this->user);
    }

    #[Test]
    public function test_get_purchase_index_data()
    {
        Supplier::factory()->count(2)->create(['location' => 'IN']);
        Supplier::factory()->count(2)->create(['location' => 'OUT']);
        Purchase::factory()->count(5)->create();

        $data = $this->purchaseService->getPurchaseIndexData([], 10);

        $this->assertArrayHasKey('pos', $data);
        $this->assertArrayHasKey('inCount', $data);
        $this->assertArrayHasKey('outCount', $data);
        $this->assertArrayHasKey('inCountamount', $data);
        $this->assertArrayHasKey('outCountamount', $data);
        $this->assertArrayHasKey('totalinvoice', $data);
        $this->assertArrayHasKey('totalMonthly', $data);
        $this->assertArrayHasKey('paymentMonthly', $data);
    }

    #[Test]
    public function test_get_purchase_create_data()
    {
        $data = $this->purchaseService->getPurchaseCreateData();

        $this->assertArrayHasKey('pos', $data);
        $this->assertArrayHasKey('suppliers', $data);
        $this->assertArrayHasKey('products', $data);
        $this->assertArrayHasKey('warehouses', $data); // Added check
    }

    #[Test]
    public function test_get_purchase_edit_data()
    {
        $purchase = Purchase::factory()->create();
        $data = $this->purchaseService->getPurchaseEditData($purchase->id);

        $this->assertArrayHasKey('pos', $data);
        $this->assertArrayHasKey('suppliers', $data);
        $this->assertArrayHasKey('products', $data);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('isPaid', $data);
        $this->assertArrayHasKey('warehouses', $data); // Added check
    }

    #[Test]
    public function test_get_purchase_view_data()
    {
        $purchase = Purchase::factory()->hasItems(2)->create();
        $data = $this->purchaseService->getPurchaseViewData($purchase->id);

        $this->assertArrayHasKey('pos', $data);
        $this->assertArrayHasKey('suppliers', $data);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('itemCount', $data);
        $this->assertArrayHasKey('subtotal', $data);
        $this->assertArrayHasKey('orderDiscount', $data);
        $this->assertArrayHasKey('finalTotal', $data);
        $this->assertArrayHasKey('totalProductDiscount', $data);
    }

    #[Test]
    public function test_get_purchase_for_modal()
    {
        $purchase = Purchase::factory()->hasItems(2)->create();
        $result = $this->purchaseService->getPurchaseForModal($purchase->id);

        $this->assertEquals($purchase->id, $result->id);
    }

    #[Test]
    public function test_create_purchase()
    {
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create(['is_main' => true]);
        
        $product1 = Product::factory()->create();
        ProductWarehouse::updateOrCreate(
            ['product_id' => $product1->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product1->tenant_id],
            ['quantity' => 10]
        );

        $product2 = Product::factory()->create();
        ProductWarehouse::updateOrCreate(
            ['product_id' => $product2->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product2->tenant_id],
            ['quantity' => 5]
        );

        $purchaseData = [
            'invoice' => 'INV-001',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id, // Added
            'order_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'discount_total' => 10,
            'discount_total_type' => 'fixed',
            'products' => json_encode([
                ['product_id' => $product1->id, 'quantity' => 2, 'price' => 100],
                ['product_id' => $product2->id, 'quantity' => 3, 'price' => 50],
            ]),
        ];

        $this->accountingServiceMock->shouldReceive('createJournalEntry')->once();

        $purchase = $this->purchaseService->createPurchase($purchaseData);

        $this->assertDatabaseHas('po', ['invoice' => 'INV-001', 'warehouse_id' => $warehouse->id]);
        $this->assertDatabaseHas('po_items', ['po_id' => $purchase->id, 'product_id' => $product1->id]);
        
        // Verify pivot table increment
        $this->assertDatabaseHas('product_warehouse', ['product_id' => $product1->id, 'warehouse_id' => $warehouse->id, 'quantity' => 12]);
        $this->assertDatabaseHas('product_warehouse', ['product_id' => $product2->id, 'warehouse_id' => $warehouse->id, 'quantity' => 8]);
    }

    #[Test]
    public function test_it_creates_a_journal_entry_on_purchase_creation()
    {
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product1 = Product::factory()->create();
        
        // Init pivot
        ProductWarehouse::updateOrCreate(
            ['product_id' => $product1->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product1->tenant_id],
            ['quantity' => 0]
        );

        $purchaseData = [
            'invoice' => 'INV-001',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'products' => json_encode([
                ['product_id' => $product1->id, 'quantity' => 2, 'price' => 100],
            ]),
        ];

        $finalTotal = 200;

        $accountingSettings = $this->user->accounting_settings;
        $inventoryAccountName = Account::find($accountingSettings['inventory_account_id'])->name;
        $accountsPayableAccountName = Account::find($accountingSettings['accounts_payable_account_id'])->name;

        $this->accountingServiceMock
            ->shouldReceive('createJournalEntry')
            ->once()
            ->with(
                "Purchase Order #INV-001",
                Mockery::any(),
                Mockery::on(function ($transactions) use ($finalTotal, $inventoryAccountName, $accountsPayableAccountName) {
                    $this->assertCount(2, $transactions);
                    $this->assertEquals($finalTotal, $this->findTransactionAmount($transactions, $inventoryAccountName, 'debit'));
                    $this->assertEquals($finalTotal, $this->findTransactionAmount($transactions, $accountsPayableAccountName, 'credit'));
                    return true;
                }),
                Mockery::type(Purchase::class)
            );

        $this->purchaseService->createPurchase($purchaseData);
    }

    #[Test]
    public function test_it_creates_a_journal_entry_on_payment()
    {
        $purchase = Purchase::factory()->create(['invoice' => 'PO-123']);
        $paymentData = [
            'amount' => 500,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Cash',
        ];

        $this->accountingServiceMock
            ->shouldReceive('createJournalEntry')
            ->once()
            ->with(
                "Payment for PO #PO-123",
                Mockery::any(),
                Mockery::on(function ($transactions) {
                    $accountingSettings = Auth::user()->accounting_settings;
                    $accountsPayableAccountName = Account::find($accountingSettings['accounts_payable_account_id'])->name;
                    $cashAccountName = Account::find($accountingSettings['cash_account_id'])->name;

                    $this->assertCount(2, $transactions);
                    $this->assertEquals(500, $this->findTransactionAmount($transactions, $accountsPayableAccountName, 'debit'));
                    $this->assertEquals(500, $this->findTransactionAmount($transactions, $cashAccountName, 'credit'));
                    return true;
                }),
                Mockery::any() // Payment model
            );

        $this->purchaseService->addPayment($purchase, $paymentData);
    }

    #[Test]
    public function test_update_purchase()
    {
        $warehouse = Warehouse::factory()->create();
        $purchase = Purchase::factory()->create(['warehouse_id' => $warehouse->id]);
        $product = Product::factory()->create();
        $oldItem = POItem::factory()->create(['po_id' => $purchase->id, 'product_id' => $product->id, 'quantity' => 1]);
        
        // Manually set initial stock state (purchase incremented it)
        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 11]
        );

        $supplier = Supplier::factory()->create();
        $newProduct = Product::factory()->create();
        ProductWarehouse::updateOrCreate(
            ['product_id' => $newProduct->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $newProduct->tenant_id],
            ['quantity' => 20]
        );

        $updateData = [
            'invoice' => 'UNIT-INV-UPDATED',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addDays(60)->toDateString(),
            'discount_total' => 20,
            'discount_total_type' => 'percentage',
            'status' => 'Paid',
            'payment_type' => 'Cash',
            'products' => json_encode([
                ['product_id' => $newProduct->id, 'quantity' => 5, 'price' => 200],
            ]),
        ];

        // Expect createJournalEntry to be called for the payment
        $this->accountingServiceMock->shouldReceive('createJournalEntry')->once();

        $this->purchaseService->updatePurchase($purchase, $updateData);

        $this->assertDatabaseHas('po', ['id' => $purchase->id, 'status' => 'Paid']);
        $this->assertDatabaseMissing('po_items', ['id' => $oldItem->id]);
        $this->assertDatabaseHas('po_items', ['po_id' => $purchase->id, 'product_id' => $newProduct->id]);
        
        // Old product stock should decrement (revert): 11 - 1 = 10
        $this->assertDatabaseHas('product_warehouse', ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 10]);
        
        // New product stock should increment: 20 + 5 = 25
        $this->assertDatabaseHas('product_warehouse', ['product_id' => $newProduct->id, 'warehouse_id' => $warehouse->id, 'quantity' => 25]);
    }

    #[Test]
    public function test_add_payment()
    {
        $purchase = Purchase::factory()
            ->hasItems(1, [
                'quantity' => 10,
                'price' => 100,
                'total' => 1000,
                'discount' => 0
            ])
            ->create(['discount_total' => 0]);

        $paymentData = [
            'amount' => 500,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Cash',
        ];

        $this->accountingServiceMock->shouldReceive('createJournalEntry')->once();

        $this->purchaseService->addPayment($purchase, $paymentData);
        $this->assertEquals('Partial', $purchase->fresh()->status);
    }

    #[Test]
    public function test_update_purchase_status()
    {
        $purchase = Purchase::factory()
            ->hasItems(1, [
                'quantity' => 10,
                'price' => 100,
                'total' => 1000,
                'discount' => 0
            ])
            ->create(['discount_total' => 0, 'total' => 1000]); // Explicitly set total

        // Test Partial
        $purchase->payments()->create(['amount' => 500, 'payment_date' => now()->toDateString(), 'payment_method' => 'Cash']);
        $this->purchaseService->updatePurchaseStatus($purchase);
        $this->assertEquals('Partial', $purchase->fresh()->status);

        // Test Paid
        // Calculate exact remaining balance to pay
        $remaining = $purchase->grand_total - 500;
        $purchase->payments()->create(['amount' => $remaining, 'payment_date' => now()->toDateString(), 'payment_method' => 'Cash']);
        
        $this->purchaseService->updatePurchaseStatus($purchase);
        $this->assertEquals('Paid', $purchase->fresh()->status);

        // Test Unpaid
        $purchase->payments()->delete();
        $this->purchaseService->updatePurchaseStatus($purchase);
        $this->assertEquals('Unpaid', $purchase->fresh()->status);
    }

    #[Test]
    public function test_delete_purchase()
    {
        $warehouse = Warehouse::factory()->create();
        $purchase = Purchase::factory()->create(['warehouse_id' => $warehouse->id]);
        $product = Product::factory()->create();
        $item = POItem::factory()->create(['po_id' => $purchase->id, 'product_id' => $product->id, 'quantity' => 1]);
        
        // Initial stock state (after purchase was made)
        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 11]
        );

        $this->purchaseService->deletePurchase($purchase);

        $this->assertDatabaseMissing('po', ['id' => $purchase->id]);
        $this->assertDatabaseMissing('po_items', ['id' => $item->id]);
        
        // Stock should decrement: 11 - 1 = 10
        $this->assertDatabaseHas('product_warehouse', ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 10]);
    }

    #[Test]
    public function test_bulk_delete_purchases()
    {
        $purchases = Purchase::factory()->count(3)->hasItems(1)->create();
        $ids = $purchases->pluck('id')->toArray();

        $this->purchaseService->bulkDeletePurchases($ids);

        foreach ($purchases as $purchase) {
            $this->assertDatabaseMissing('po', ['id' => $purchase->id]);
        }
    }

    #[Test]
    public function test_bulk_mark_paid()
    {
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        
        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 10]
        );

        $this->accountingServiceMock->shouldReceive('createJournalEntry')->times(6); // 3 for creation, 3 for payment

        $purchases = collect();
        for ($i = 0; $i < 3; $i++) {
            $purchaseData = [
                'invoice' => 'INV-00' . ($i + 1),
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'order_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'discount_total' => 0,
                'discount_total_type' => 'fixed',
                'products' => json_encode([
                    ['product_id' => $product->id, 'quantity' => 1, 'price' => 100],
                ]),
            ];
            $purchases->push($this->purchaseService->createPurchase($purchaseData));
        }

        $ids = $purchases->pluck('id')->toArray();

        $count = $this->purchaseService->bulkMarkPaid($ids);

        $this->assertEquals(3, $count);
        foreach ($purchases as $purchase) {
            $purchase->refresh(); // Refresh the purchase model to get the latest status
            $this->assertEquals('Paid', $purchase->status);
        }
    }

    #[Test]
    public function test_get_purchase_metrics()
    {
        Supplier::factory()->create(['location' => 'IN']);
        Supplier::factory()->create(['location' => 'OUT']);
        Purchase::factory()->count(5)->create();

        $metrics = $this->purchaseService->getPurchaseMetrics();

        $this->assertArrayHasKey('totalinvoice', $metrics);
        $this->assertArrayHasKey('inCount', $metrics);
        $this->assertArrayHasKey('inCountamount', $metrics);
        $this->assertArrayHasKey('outCount', $metrics);
        $this->assertArrayHasKey('outCountamount', $metrics);
        $this->assertArrayHasKey('totalMonthly', $metrics);
        $this->assertArrayHasKey('paymentMonthly', $metrics);
    }

    #[Test]
    public function test_get_expiring_purchase_count()
    {
        Purchase::factory()->create(['due_date' => now()->addDays(10), 'status' => 'Unpaid']);
        Purchase::factory()->create(['due_date' => now()->addDays(100), 'status' => 'Unpaid']);
        Purchase::factory()->create(['due_date' => now()->addDays(10), 'status' => 'Paid']);

        $count = $this->purchaseService->getExpiringPurchaseCount();

        $this->assertEquals(1, $count);
    }

    #[Test]
    public function test_get_expiring_purchases()
    {
        Purchase::factory()->create(['due_date' => now()->addDays(10), 'status' => 'Unpaid']);
        Purchase::factory()->create(['due_date' => now()->addDays(100), 'status' => 'Unpaid']);

        $expiringPurchases = $this->purchaseService->getExpiringPurchases();

        $this->assertCount(1, $expiringPurchases);
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
