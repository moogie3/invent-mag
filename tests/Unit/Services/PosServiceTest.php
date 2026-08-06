<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\Categories;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Services\PosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class PosServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected PosService $posService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->posService = app(PosService::class);

        // Create a default tax (10% exclusive) so POS tax calculations work in tests
        Tax::create([
            'name' => 'Test Tax',
            'rate' => 10,
            'type' => 'exclusive',
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(PosService::class, $this->posService);
    }

    #[Test]
    public function it_can_get_pos_index_data()
    {
        // 1. Setup data
        $categories = Categories::factory()->count(2)->create();
        $units = Unit::factory()->count(2)->create();
        $suppliers = Supplier::factory()->count(2)->create();

        Product::factory()->count(5)->create([
            'category_id' => $categories->first()->id,
            'units_id' => $units->first()->id,
            'supplier_id' => $suppliers->first()->id,
        ]);
        Customer::factory()->count(3)->create();
        Customer::factory()->create(['name' => 'Walk In Customer']);

        // 2. Call the service method
        $data = $this->posService->getPosIndexData();

        // 3. Assertions
        $this->assertArrayHasKey('products', $data);
        $this->assertArrayHasKey('customers', $data);
        $this->assertArrayHasKey('walkInCustomerId', $data);
        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('units', $data);
        $this->assertArrayHasKey('suppliers', $data);

        $this->assertCount(5, $data['products']);
        $this->assertCount(4, $data['customers']);
        $this->assertNotNull($data['walkInCustomerId']);
        $this->assertCount(2, $data['categories']);
        $this->assertCount(2, $data['units']);
        $this->assertCount(2, $data['suppliers']);
    }

    #[Test]
    public function it_can_create_a_sale()
    {
        // 1. Setup data
        $user = User::factory()->create();
        Auth::login($user);

        $customer = Customer::factory()->create();
        $product1 = Product::factory()->withStock(10)->create(['selling_price' => 100]);
        $product2 = Product::factory()->withStock(20)->create(['selling_price' => 50]);

        $productsPayload = json_encode([
            ['id' => $product1->id, 'quantity' => 2, 'price' => 100],
            ['id' => $product2->id, 'quantity' => 3, 'price' => 50],
        ]);

        $data = [
            'products' => $productsPayload,
            'customer_id' => $customer->id,
            'transaction_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'discount_total' => 10,
            'discount_total_type' => 'fixed',
            'tax_id' => Tax::first()->id,
            'amount_received' => 400,
            'change_amount' => 15,
        ];

        // 2. Call the service method
        $sale = $this->posService->createSale($data);

        // 3. Assertions
        $this->assertInstanceOf(Sales::class, $sale);
        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'customer_id' => $customer->id,
            'status' => 'Paid',
            'type' => Sales::TYPE_POS,
        ]);

        // Assert totals
        $this->assertEquals(10, $sale->order_discount);
        $this->assertEquals(34, $sale->total_tax); // (350 - 10) * 0.10 = 34
        $this->assertEquals(374, $sale->total); // 350 - 10 + 34 = 374

        $this->assertDatabaseHas('sales_items', ['sales_id' => $sale->id, 'product_id' => $product1->id, 'quantity' => 2]);
        $this->assertDatabaseHas('sales_items', ['sales_id' => $sale->id, 'product_id' => $product2->id, 'quantity' => 3]);

        $this->assertDatabaseHas('payments', ['paymentable_id' => $sale->id, 'paymentable_type' => Sales::class]);

        $this->assertEquals(8, $product1->fresh()->stock_quantity);
        $this->assertEquals(17, $product2->fresh()->stock_quantity);
    }

    #[Test]
    public function it_can_get_receipt_data()
    {
        // 1. Setup data
        $sale = Sales::factory()->create([
            'order_discount' => 10,
            'order_discount_type' => 'fixed',
            'tax_rate' => 10,
            'amount_received' => 400,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'customer_price' => 100, 'quantity' => 2, 'discount' => 0]); // 200
        SalesItem::factory()->create(['sales_id' => $sale->id, 'customer_price' => 50, 'quantity' => 3, 'discount' => 0]);  // 150

        // Manually set total because factory creates random total
        $sale->total = 350;
        $sale->save();

        // 2. Call the service method
        $data = $this->posService->getReceiptData($sale);

        // 3. Assertions
        $this->assertEquals(350, $data['subTotal']);
        $this->assertEquals(10, $data['orderDiscountAmount']);
        $this->assertEquals(10, $data['taxRate']);
        $this->assertEquals(34, $data['taxAmount']); // (350 - 10) * 0.10 = 34
        $this->assertEquals(374, $data['grandTotal']); // 350 - 10 + 34 = 374
        $this->assertEquals(400, $data['amountReceived']);
        $this->assertEquals(26, $data['change']); // 400 - 374 = 26
    }

    #[Test]
    public function it_stores_pph_snapshot_and_creates_pph_journal_on_pos_sale()
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

        $user = User::factory()->create();
        Auth::login($user);

        $customer = Customer::factory()->create();
        $product = Product::factory()->withStock(10)->create(['selling_price' => 100]);

        $productsPayload = json_encode([
            ['id' => $product->id, 'quantity' => 2, 'price' => 100],
        ]);

        $data = [
            'products' => $productsPayload,
            'customer_id' => $customer->id,
            'transaction_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_id' => Tax::first()->id,
            'pph_tax_id' => $pphTax->id,
            'amount_received' => 300,
        ];

        $sale = $this->posService->createSale($data);

        // Subtotal 200, tax 10% exclusive = 20, PPh 2% of 200 = 4
        // grandTotal = 200 + 20 - 4 = 216
        $this->assertTrue($sale->fresh()->pph_enabled);
        $this->assertEquals(2.0, $sale->fresh()->pph_rate);
        $this->assertEquals(4.0, $sale->fresh()->pph_amount);
        $this->assertEquals($pphTax->id, $sale->fresh()->pph_tax_id);
        $this->assertEquals(216.0, $sale->fresh()->total);
    }

    #[Test]
    public function it_splits_vat_out_of_revenue_on_indonesian_ppn_pos_sale()
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        $ppnTax = Tax::create([
            'name' => 'PPN 11%',
            'rate' => 11,
            'type' => 'exclusive',
            'is_active' => true,
            'is_default' => true,
            'is_ppn' => true,
        ]);

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

        $user = User::factory()->create();
        Auth::login($user);

        $customer = Customer::factory()->create();
        $product = Product::factory()->withStock(10)->create(['selling_price' => 100]);

        $productsPayload = json_encode([
            ['id' => $product->id, 'quantity' => 2, 'price' => 100],
        ]);

        $data = [
            'products' => $productsPayload,
            'customer_id' => $customer->id,
            'transaction_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_id' => $ppnTax->id,
            'amount_received' => 300,
        ];

        $sale = $this->posService->createSale($data);

        // Subtotal 200 + PPN 11% = 222
        $this->assertEquals(222.0, $sale->fresh()->total);

        // The sale journal must split VAT: Dr AR 222 / Cr Revenue 200 / Cr PPN Keluaran 22
        $saleJournal = JournalEntry::where('sourceable_type', Sales::class)
            ->where('sourceable_id', $sale->id)
            ->where('description', 'like', 'POS Sale%')
            ->first();

        $this->assertNotNull($saleJournal);

        $accountingSettings = $this->tenant->accounting_settings;
        $revenueAccount = Account::find($accountingSettings['sales_revenue_account_id']);
        $arAccount = Account::find($accountingSettings['accounts_receivable_account_id']);

        $this->assertEquals(222.0, $saleJournal->transactions->where('account_id', $arAccount->id)->where('type', 'debit')->first()?->amount);
        $this->assertEquals(200.0, $saleJournal->transactions->where('account_id', $revenueAccount->id)->where('type', 'credit')->first()?->amount);
        $this->assertEquals(22.0, $saleJournal->transactions->where('account_id', $ppnKeluaranAccount->id)->where('type', 'credit')->first()?->amount);
    }
}
