<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\Categories;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\POItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\Tenant;
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

    /**
     * exists:customers,id is raw SQL and ignores the tenant scope, so the
     * id of another tenant's customer passed validation and was written
     * straight onto the POS sale. The customer must be re-resolved through
     * the scoped model before the sale is written. A walk-in sale carries
     * no customer at all, and that stays legal.
     */
    #[Test]
    public function it_refuses_a_customer_owned_by_another_tenant()
    {
        $otherTenant = Tenant::create(['name' => 'Other Tenant', 'domain' => 'other.localhost']);
        $foreignCustomer = Customer::factory()->create(['tenant_id' => $otherTenant->id]);
        $product = Product::factory()->withStock(10)->create(['selling_price' => 100]);

        $refused = null;
        try {
            $this->posService->createSale([
                'products' => json_encode([['id' => $product->id, 'quantity' => 2, 'price' => 100]]),
                'customer_id' => $foreignCustomer->id,
                'transaction_date' => now()->format('Y-m-d'),
                'payment_method' => 'Cash',
                'discount_total' => 0,
                'discount_total_type' => 'fixed',
                'tax_id' => Tax::first()->id,
                'amount_received' => 300,
            ]);
        } catch (\Exception $e) {
            $refused = $e->getMessage();
        }

        $this->assertNotNull($refused, 'A customer owned by another tenant must be refused.');
        $this->assertDatabaseMissing('sales', ['customer_id' => $foreignCustomer->id]);
        $this->assertEquals(10, $product->fresh()->stock_quantity, 'A refused sale must not move stock.');
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

    /**
     * The point of sale has to charge the goods it sells, or profit is
     * overstated on every transaction and the Inventory account keeps
     * claiming stock that walked out the door. The costing code used to read
     * $product->cost_price, a column that has never existed on products, so
     * the value was always null and every POS sale booked Rp 0 of cost while
     * still decrementing the warehouse.
     */
    #[Test]
    public function it_charges_the_goods_against_inventory_and_consumes_the_batch()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $customer = Customer::factory()->create();
        // products.price is what the goods cost us; selling_price is what the
        // cashier charges. The two must not be confused: the POS payload's
        // "price" is the selling price, the product column's price is cost.
        $product = Product::factory()->withStock(10)->create([
            'price' => 40,
            'selling_price' => 100,
        ]);

        // A clean purchase, so the batch is exactly what it claims: 10
        // units at 40 with nothing coming off the order. The line's total
        // has to agree with its price, because the Inventory debit was
        // built from that total, and costing now reads the same figure.
        $purchase = Purchase::factory()->create([
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_type' => 'none',
            'tax_rate' => 0,
            'total_tax' => 0,
            'ppn_enabled' => false,
            'ppn_amount' => 0,
        ]);

        $batch = POItem::factory()->forPurchase($purchase)->create([
            'product_id' => $product->id,
            'price' => 40,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'discount' => 0,
            'discount_type' => 'fixed',
            'total' => 400,
            'expiry_date' => now()->addMonth(),
        ]);

        $sale = $this->posService->createSale([
            'products' => json_encode([['id' => $product->id, 'quantity' => 3, 'price' => 100]]),
            'customer_id' => $customer->id,
            'transaction_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_id' => null,
            'amount_received' => 300,
            'change_amount' => 0,
        ]);

        // The batch was consumed the way a regular sale consumes it.
        $this->assertEquals(7, $batch->fresh()->remaining_quantity);

        $settings = app('currentTenant')->accounting_settings;
        $entry = JournalEntry::where('sourceable_type', Sales::class)
            ->where('sourceable_id', $sale->id)
            ->where('description', 'POS Sale Invoice #'.$sale->invoice)
            ->first();

        $this->assertNotNull($entry, 'The POS sale posted no invoice entry.');

        $cogs = $entry->transactions->firstWhere('account_id', $settings['cost_of_goods_sold_account_id']);
        $inventory = $entry->transactions->firstWhere('account_id', $settings['inventory_account_id']);

        // 3 units at the batch cost of 40 each.
        $this->assertEqualsWithDelta(120, (float) $cogs->amount, 0.005, 'POS booked no cost of goods.');
        $this->assertEqualsWithDelta(120, (float) $inventory->amount, 0.005, 'Inventory was never credited.');
        $this->assertSame('credit', $inventory->type);

        // And the entry still balances.
        $this->assertEqualsWithDelta(
            0,
            $entry->transactions->sum(fn ($t) => $t->type === 'debit' ? $t->amount : -$t->amount),
            0.005
        );
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
    public function it_treats_null_tax_id_as_no_tax_rather_than_falling_back_to_default()
    {
        // Regression: selecting "None (0%)" in the POS tax dropdown submits an
        // empty tax_id (null after ConvertEmptyStringsToNull). The server used
        // to fall back to the default 10% tax, so the grand total computed
        // server-side disagreed with the client and cash validation failed.
        $user = User::factory()->create();
        Auth::login($user);

        $customer = Customer::factory()->create();
        $product = Product::factory()->withStock(10)->create(['selling_price' => 100]);

        $data = [
            'products' => json_encode([['id' => $product->id, 'quantity' => 2, 'price' => 100]]),
            'customer_id' => $customer->id,
            'transaction_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_id' => null,
            'amount_received' => 200,
        ];

        $sale = $this->posService->createSale($data);

        $this->assertEquals(0.0, $sale->fresh()->total_tax);
        $this->assertEquals(200.0, $sale->fresh()->total); // 200, no 10% tax added
        $this->assertNull($sale->fresh()->tax_id);
        $this->assertEquals('none', $sale->fresh()->tax_type);
    }

    #[Test]
    public function it_treats_empty_string_tax_id_as_no_tax()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $customer = Customer::factory()->create();
        $product = Product::factory()->withStock(10)->create(['selling_price' => 100]);

        $data = [
            'products' => json_encode([['id' => $product->id, 'quantity' => 2, 'price' => 100]]),
            'customer_id' => $customer->id,
            'transaction_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_id' => '',
            'amount_received' => 200,
        ];

        $sale = $this->posService->createSale($data);

        $this->assertEquals(0.0, $sale->fresh()->total_tax);
        $this->assertEquals(200.0, $sale->fresh()->total);
    }

    #[Test]
    public function it_falls_back_to_default_tax_when_tax_id_key_is_absent()
    {
        // Legacy/API clients that omit tax_id entirely keep the old behavior:
        // the default active tax applies.
        $user = User::factory()->create();
        Auth::login($user);

        $customer = Customer::factory()->create();
        $product = Product::factory()->withStock(10)->create(['selling_price' => 100]);

        $data = [
            'products' => json_encode([['id' => $product->id, 'quantity' => 2, 'price' => 100]]),
            'customer_id' => $customer->id,
            'transaction_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'amount_received' => 220,
        ];

        $sale = $this->posService->createSale($data);

        $defaultTax = Tax::default()->first();
        $this->assertEquals($defaultTax->id, $sale->fresh()->tax_id);
        $this->assertEquals(20.0, $sale->fresh()->total_tax); // 200 * 10%
        $this->assertEquals(220.0, $sale->fresh()->total);
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

    /**
     * Non-Indonesian control for the test above: the same PPh tax must NOT
     * reduce the total, because pph_amount is recorded as 0 — subtracting it
     * anyway would make the total disagree with the snapshot on the same row.
     */
    #[Test]
    public function it_does_not_apply_pph_on_pos_sale_when_tenant_not_indonesian()
    {
        $this->tenant->update(['tax_jurisdiction' => 'US']);
        $this->assertFalse(app('currentTenant')->isIndonesian());

        $pphTax = Tax::create([
            'name' => 'PPh 23',
            'rate' => 2,
            'type' => 'exclusive',
            'is_active' => true,
            'is_pph' => true,
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

        // Subtotal 200, tax 10% exclusive = 20. Ungated, PPh 2% of 200 = 4
        // would have taken this to 216 while pph_amount stayed 0.
        $fresh = $sale->fresh();
        $this->assertEquals(220.0, (float) $fresh->total, 'total must not shrink for a non-ID tenant');
        $this->assertEquals(0.0, (float) $fresh->pph_amount);
        $this->assertFalse((bool) $fresh->pph_enabled);
        $this->assertNull($fresh->pph_tax_id);
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

    /**
     * The PPN snapshot is what every downstream PPN reader consults: the
     * e-Faktur guard (isPpn), Tenant::unissuedCount() and the sales-return
     * PPN reversal all read ppn_enabled/ppn_amount off the row. The POS sale
     * books the PPN Keluaran split in its journal but never wrote the
     * snapshot, so a POS PPN sale could not issue a Faktur Pajak and a
     * return of it reversed none of the VAT the original sale had credited.
     */
    #[Test]
    public function it_stores_the_ppn_snapshot_on_an_indonesian_ppn_pos_sale(): void
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

        $user = User::factory()->create();
        Auth::login($user);

        $customer = Customer::factory()->create();
        $product = Product::factory()->withStock(10)->create(['selling_price' => 100]);

        $sale = $this->posService->createSale([
            'products' => json_encode([['id' => $product->id, 'quantity' => 2, 'price' => 100]]),
            'customer_id' => $customer->id,
            'transaction_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_id' => $ppnTax->id,
            'amount_received' => 300,
        ]);

        $fresh = $sale->fresh();

        // Subtotal 200 + PPN 11% = 222, of which 22 is output VAT.
        $this->assertTrue((bool) $fresh->ppn_enabled, 'POS PPN sale must flag ppn_enabled, or e-Faktur refuses it.');
        $this->assertEquals(11.0, (float) $fresh->ppn_rate);
        $this->assertEquals(22.0, (float) $fresh->ppn_amount);
        $this->assertTrue($fresh->isPpn());
    }

    /**
     * Accounts Receivable must net to zero over the sale's own entries: the
     * invoice books it, the cash payment clears it and the withholding entry
     * moves the withheld slice to PPh Receivable. Booking the invoice NET of
     * PPh (the stored total is already net) while the withholding entry
     * credits AR again deducted the withheld amount twice and left AR
     * sitting at minus the PPh. The regular sale path books AR at the gross
     * (total + pph) for exactly this reason; POS must match it.
     */
    #[Test]
    public function pos_pph_sale_does_not_leave_accounts_receivable_negative(): void
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

        $sale = $this->posService->createSale([
            'products' => json_encode([['id' => $product->id, 'quantity' => 2, 'price' => 100]]),
            'customer_id' => $customer->id,
            'transaction_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_id' => Tax::first()->id,
            'pph_tax_id' => $pphTax->id,
            'amount_received' => 300,
        ]);

        // Subtotal 200 + tax 10% = 220 gross, PPh 2% of 200 = 4, net 216.
        $fresh = $sale->fresh();
        $this->assertEquals(216.0, (float) $fresh->total);
        $this->assertEquals(4.0, (float) $fresh->pph_amount);

        $settings = $this->tenant->accounting_settings;
        $arId = $settings['accounts_receivable_account_id'];

        $arNet = (float) JournalEntry::where('sourceable_type', Sales::class)
            ->where('sourceable_id', $sale->id)
            ->get()
            ->flatMap(fn ($entry) => $entry->transactions)
            ->filter(fn ($tx) => $tx->account_id === $arId)
            ->sum(fn ($tx) => $tx->type === 'debit' ? $tx->amount : -$tx->amount);

        $this->assertEqualsWithDelta(
            0,
            $arNet,
            0.005,
            'The invoice, the cash payment and the PPh withholding must clear AR to zero, not leave it at minus the withheld 4.'
        );
    }

    /**
     * The cash check runs before PPh is subtracted, so it demands the gross
     * (220) while the customer actually owes the net (216) - the exact figure
     * the POS cart sends as grandTotalInput. Paying precisely what the
     * receipt says must not be rejected as "not enough money".
     */
    #[Test]
    public function it_accepts_a_cash_payment_equal_to_the_pph_net_total(): void
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

        // Subtotal 200 + tax 10% = 220 gross, PPh 2% = 4, so the customer
        // hands over 216 and the tax office withholds 4 of it.
        $sale = $this->posService->createSale([
            'products' => json_encode([['id' => $product->id, 'quantity' => 2, 'price' => 100]]),
            'customer_id' => $customer->id,
            'transaction_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_id' => Tax::first()->id,
            'pph_tax_id' => $pphTax->id,
            'amount_received' => 216,
        ]);

        $fresh = $sale->fresh();
        $this->assertEquals(216.0, (float) $fresh->total);
        $this->assertEquals(216.0, (float) $fresh->amount_received);
        $this->assertEquals(0.0, (float) $fresh->change_amount);
    }
}
