<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\PurchaseService;
use Database\Seeders\AccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class PurchaseServicePpnMasukanTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected PurchaseService $purchaseService;

    protected MockInterface $accountingServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->user->assignRole('superuser');

        $this->accountingServiceMock = Mockery::mock(AccountingService::class);
        $this->purchaseService = new PurchaseService($this->accountingServiceMock);

        $this->seed(AccountSeeder::class);

        $tenantId = app('currentTenant')->id;
        $cash = Account::where('code', '1110-'.$tenantId)->first();
        $accountsPayable = Account::where('code', '2110-'.$tenantId)->first();
        $inventory = Account::where('code', '1140-'.$tenantId)->first();
        $inputVat = Account::where('code', '1160-'.$tenantId)->first();

        $this->assertNotNull($inputVat, 'Input VAT (1160) account not found in seeder.');

        $this->tenant->update([
            'tax_jurisdiction' => 'ID',
            'accounting_settings' => [
                'accounts_payable_account_id' => $accountsPayable->id,
                'inventory_account_id' => $inventory->id,
                'cash_account_id' => $cash->id,
                'ppn_masukan_account_id' => $inputVat->id,
            ],
        ]);

        $this->user = $this->user->fresh();
        Auth::setUser($this->user);
    }

    private function createPpnTax(float $rate = 11): Tax
    {
        return Tax::create([
            'name' => 'PPN '.$rate.'%',
            'rate' => $rate,
            'type' => 'exclusive',
            'is_active' => true,
            'is_default' => true,
            'is_ppn' => true,
            'is_pph' => false,
        ]);
    }

    private function makePurchaseData(string $invoice, Tax $tax, int $quantity = 2, int $price = 100): array
    {
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        ProductWarehouse::updateOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'tenant_id' => $product->tenant_id],
            ['quantity' => 0]
        );

        return [
            'invoice' => $invoice,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_id' => $tax->id,
            'products' => json_encode([
                ['product_id' => $product->id, 'quantity' => $quantity, 'price' => $price],
            ]),
        ];
    }

    #[Test]
    public function test_create_purchase_with_ppn_tax_records_ppn_masukan_fields(): void
    {
        $ppnTax = $this->createPpnTax();
        $purchaseData = $this->makePurchaseData('PO-PPN-MASUKAN-001', $ppnTax);

        $this->accountingServiceMock->shouldReceive('createJournalEntry')->once();

        $purchase = $this->purchaseService->createPurchase($purchaseData);

        $fresh = $purchase->fresh();
        $this->assertEquals(1, $fresh->ppn_enabled);
        $this->assertEquals(11.0, $fresh->ppn_rate);
        $this->assertEquals(22.0, $fresh->ppn_amount);
        $this->assertEquals(222.0, $fresh->total);
    }

    #[Test]
    public function test_create_purchase_with_ppn_tax_splits_inventory_and_input_vat(): void
    {
        $ppnTax = $this->createPpnTax();
        $purchaseData = $this->makePurchaseData('PO-PPN-MASUKAN-002', $ppnTax);

        $accountingSettings = app('currentTenant')->accounting_settings;
        $inventoryAccountName = Account::find($accountingSettings['inventory_account_id'])->name;
        $accountsPayableAccountName = Account::find($accountingSettings['accounts_payable_account_id'])->name;
        $ppnMasukanAccountName = Account::find($accountingSettings['ppn_masukan_account_id'])->name;

        // Subtotal 200, PPN 11% exclusive = 22
        // Dr Inventory 200 (net), Dr PPN Masukan 22, Cr AP 222
        $this->accountingServiceMock
            ->shouldReceive('createJournalEntry')
            ->once()
            ->with(
                'Purchase Order #PO-PPN-MASUKAN-002',
                Mockery::any(),
                Mockery::on(function ($transactions) use ($inventoryAccountName, $accountsPayableAccountName, $ppnMasukanAccountName) {
                    $this->assertCount(3, $transactions);
                    $this->assertEquals(200, $this->findTransactionAmount($transactions, $inventoryAccountName, 'debit'));
                    $this->assertEquals(22, $this->findTransactionAmount($transactions, $ppnMasukanAccountName, 'debit'));
                    $this->assertEquals(222, $this->findTransactionAmount($transactions, $accountsPayableAccountName, 'credit'));

                    return true;
                }),
                Mockery::type(Purchase::class)
            );

        $this->purchaseService->createPurchase($purchaseData);
    }

    #[Test]
    public function test_create_purchase_with_inclusive_ppn_tax_still_balances(): void
    {
        $inclusivePpn = Tax::create([
            'name' => 'PPN Inclusive 11%',
            'rate' => 11,
            'type' => 'inclusive',
            'is_active' => true,
            'is_default' => true,
            'is_ppn' => true,
            'is_pph' => false,
        ]);
        $purchaseData = $this->makePurchaseData('PO-PPN-MASUKAN-005', $inclusivePpn);

        $accountingSettings = app('currentTenant')->accounting_settings;
        $inventoryAccountName = Account::find($accountingSettings['inventory_account_id'])->name;
        $accountsPayableAccountName = Account::find($accountingSettings['accounts_payable_account_id'])->name;
        $ppnMasukanAccountName = Account::find($accountingSettings['ppn_masukan_account_id'])->name;

        // Subtotal 200 already CONTAINS the VAT: inclusive 11% -> tax 19.82,
        // net 180.18. The supplier is owed the gross 200 either way.
        // Dr Inventory 180.18, Dr PPN Masukan 19.82, Cr AP 200
        $this->accountingServiceMock
            ->shouldReceive('createJournalEntry')
            ->once()
            ->with(
                'Purchase Order #PO-PPN-MASUKAN-005',
                Mockery::any(),
                Mockery::on(function ($transactions) use ($inventoryAccountName, $accountsPayableAccountName, $ppnMasukanAccountName) {
                    $this->assertCount(3, $transactions);
                    $this->assertEquals(180.18, $this->findTransactionAmount($transactions, $inventoryAccountName, 'debit'));
                    $this->assertEquals(19.82, $this->findTransactionAmount($transactions, $ppnMasukanAccountName, 'debit'));
                    $this->assertEquals(200, $this->findTransactionAmount($transactions, $accountsPayableAccountName, 'credit'));

                    // The accounting service is mocked here, so nothing in the
                    // app would catch an unbalanced entry in this test. Balance
                    // it by hand: this is the whole point of the assertion.
                    $debits = 0;
                    $credits = 0;
                    foreach ($transactions as $transaction) {
                        if ($transaction['type'] === 'debit') {
                            $debits += $transaction['amount'];
                        } else {
                            $credits += $transaction['amount'];
                        }
                    }
                    $this->assertEquals(round($credits, 2), round($debits, 2));

                    return true;
                }),
                Mockery::type(Purchase::class)
            );

        $purchase = $this->purchaseService->createPurchase($purchaseData);

        $fresh = $purchase->fresh();
        // Inclusive: the gross the supplier charges does not grow by the tax.
        $this->assertEquals(200.0, $fresh->total);
        $this->assertEquals(1, $fresh->ppn_enabled);
        $this->assertEquals(19.82, $fresh->ppn_amount);
    }

    #[Test]
    public function test_create_purchase_without_ppn_tax_records_no_ppn_masukan(): void
    {
        $regularTax = Tax::create([
            'name' => 'VAT 5%',
            'rate' => 5,
            'type' => 'exclusive',
            'is_active' => true,
            'is_default' => true,
            'is_ppn' => false,
            'is_pph' => false,
        ]);
        $purchaseData = $this->makePurchaseData('PO-PPN-MASUKAN-003', $regularTax);

        $this->accountingServiceMock->shouldReceive('createJournalEntry')->once();

        $purchase = $this->purchaseService->createPurchase($purchaseData);

        $fresh = $purchase->fresh();
        $this->assertEquals(0, $fresh->ppn_enabled);
        $this->assertNull($fresh->ppn_rate);
        $this->assertEquals(0.0, $fresh->ppn_amount);
        // Subtotal 200 + VAT 5% = 10 -> total 210
        $this->assertEquals(210.0, $fresh->total);
    }

    #[Test]
    public function test_create_purchase_without_ppn_tax_capitalizes_tax_into_inventory(): void
    {
        $regularTax = Tax::create([
            'name' => 'VAT 5%',
            'rate' => 5,
            'type' => 'exclusive',
            'is_active' => true,
            'is_default' => true,
            'is_ppn' => false,
            'is_pph' => false,
        ]);
        $purchaseData = $this->makePurchaseData('PO-PPN-MASUKAN-004', $regularTax);

        $accountingSettings = app('currentTenant')->accounting_settings;
        $inventoryAccountName = Account::find($accountingSettings['inventory_account_id'])->name;
        $accountsPayableAccountName = Account::find($accountingSettings['accounts_payable_account_id'])->name;

        // No PPN Masukan: inventory is debited at the gross amount (210)
        $this->accountingServiceMock
            ->shouldReceive('createJournalEntry')
            ->once()
            ->with(
                'Purchase Order #PO-PPN-MASUKAN-004',
                Mockery::any(),
                Mockery::on(function ($transactions) use ($inventoryAccountName, $accountsPayableAccountName) {
                    $this->assertCount(2, $transactions);
                    $this->assertEquals(210, $this->findTransactionAmount($transactions, $inventoryAccountName, 'debit'));
                    $this->assertEquals(210, $this->findTransactionAmount($transactions, $accountsPayableAccountName, 'credit'));

                    return true;
                }),
                Mockery::type(Purchase::class)
            );

        $this->purchaseService->createPurchase($purchaseData);
    }

    #[Test]
    public function test_ppn_masukan_journal_falls_back_to_gross_inventory_when_account_not_configured(): void
    {
        $ppnTax = $this->createPpnTax();
        $purchaseData = $this->makePurchaseData('PO-PPN-MASUKAN-005', $ppnTax);

        $settings = app('currentTenant')->accounting_settings;
        unset($settings['ppn_masukan_account_id']);
        $this->tenant->update(['accounting_settings' => $settings]);

        $accountingSettings = app('currentTenant')->accounting_settings;
        $inventoryAccountName = Account::find($accountingSettings['inventory_account_id'])->name;
        $accountsPayableAccountName = Account::find($accountingSettings['accounts_payable_account_id'])->name;

        // PPN Masukan account missing -> gracefully keeps inventory at gross (222)
        $this->accountingServiceMock
            ->shouldReceive('createJournalEntry')
            ->once()
            ->with(
                'Purchase Order #PO-PPN-MASUKAN-005',
                Mockery::any(),
                Mockery::on(function ($transactions) use ($inventoryAccountName, $accountsPayableAccountName) {
                    $this->assertCount(2, $transactions);
                    $this->assertEquals(222, $this->findTransactionAmount($transactions, $inventoryAccountName, 'debit'));
                    $this->assertEquals(222, $this->findTransactionAmount($transactions, $accountsPayableAccountName, 'credit'));

                    return true;
                }),
                Mockery::type(Purchase::class)
            );

        $this->purchaseService->createPurchase($purchaseData);
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
