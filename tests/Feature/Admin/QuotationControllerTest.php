<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Sales;
use App\Models\Tax;
use App\Models\Warehouse;
use App\Services\SalesService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class QuotationControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected Customer $customer;

    protected Tax $tax;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.defaults.guard' => 'web']);
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');

        Warehouse::factory()->create(['is_main' => true]);
        $this->customer = Customer::factory()->create();
        $this->tax = Tax::factory()->create([
            'rate' => 11,
            'type' => 'exclusive',
            'is_default' => true,
        ]);
    }

    private function createQuotation(array $overrides = []): Quotation
    {
        return Quotation::create(array_merge([
            'quotation_number' => 'QTN-'.now()->format('Ymd').'-0001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'subtotal' => 1000,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_id' => $this->tax->id,
            'tax_type' => 'exclusive',
            'tax_rate' => 11,
            'tax_amount' => 110,
            'total' => 1110,
            'status' => 'draft',
            'items' => [
                ['product_id' => 1, 'name' => 'Test Product', 'quantity' => 1, 'price' => 1000, 'discount' => 0, 'total' => 1000],
            ],
            'notes' => null,
            'terms_conditions' => null,
        ], $overrides));
    }

    public function test_it_can_display_the_quotation_index_page()
    {
        $this->createQuotation();
        $this->createQuotation(['quotation_number' => 'QTN-'.now()->format('Ymd').'-0002']);

        $response = $this->get(route('admin.quotations'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.quotations.index');
        $response->assertViewHas('quotations');
    }

    public function test_it_can_display_the_create_page()
    {
        $response = $this->get(route('admin.quotations.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.quotations.quotation-create');
        $response->assertViewHasAll(['customers', 'products', 'quotation']);
    }

    public function test_it_can_store_a_new_quotation()
    {
        $data = [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'products' => json_encode([
                ['product_id' => 1, 'name' => 'Widget', 'quantity' => 2, 'price' => 500, 'discount' => 0, 'total' => 1000],
            ]),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_id' => $this->tax->id,
            'notes' => 'Test quotation',
            'terms_conditions' => 'Net 30',
        ];

        $response = $this->post(route('admin.quotations.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Quotation created successfully.');

        $this->assertDatabaseHas('quotations', [
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'status' => 'draft',
            'tax_type' => 'exclusive',
            'notes' => 'Test quotation',
            'terms_conditions' => 'Net 30',
        ]);
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->post(route('admin.quotations.store'), [
            'customer_id' => null,
            'quotation_date' => null,
            'products' => null,
        ]);

        $response->assertSessionHasErrors(['customer_id', 'quotation_date', 'products']);
    }

    public function test_store_validates_customer_exists()
    {
        $response = $this->post(route('admin.quotations.store'), [
            'customer_id' => 99999,
            'quotation_date' => now()->toDateString(),
            'products' => json_encode([['product_id' => 1, 'quantity' => 1, 'price' => 100]]),
        ]);

        $response->assertSessionHasErrors(['customer_id']);
    }

    public function test_it_can_display_the_show_page()
    {
        $quotation = $this->createQuotation();

        $response = $this->get(route('admin.quotations.show', $quotation->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.quotations.show');
        $response->assertViewHas('quotation');
    }

    public function test_it_can_display_the_edit_page()
    {
        $quotation = $this->createQuotation();

        $response = $this->get(route('admin.quotations.edit', $quotation->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.quotations.quotation-edit');
        $response->assertViewHasAll(['quotation', 'customers', 'products']);
    }

    public function test_it_can_update_a_quotation()
    {
        $quotation = $this->createQuotation();

        $data = [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(60)->toDateString(),
            'products' => json_encode([
                ['product_id' => 1, 'name' => 'Updated Product', 'quantity' => 5, 'price' => 200, 'discount' => 0, 'total' => 1000],
            ]),
            'discount_total' => 10,
            'discount_total_type' => 'fixed',
            'tax_id' => $this->tax->id,
            'notes' => 'Updated notes',
            'terms_conditions' => 'Updated terms',
        ];

        $response = $this->put(route('admin.quotations.update', $quotation->id), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Quotation updated successfully.');

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'notes' => 'Updated notes',
            'terms_conditions' => 'Updated terms',
            'discount_value' => 10,
            'discount_amount' => 10,
        ]);
    }

    public function test_it_can_delete_a_quotation()
    {
        $quotation = $this->createQuotation();

        $response = $this->delete(route('admin.quotations.destroy', $quotation->id));

        $response->assertRedirect(route('admin.quotations'));
        $response->assertSessionHas('success', 'Quotation deleted successfully.');

        $this->assertDatabaseMissing('quotations', ['id' => $quotation->id]);
    }

    public function test_it_can_update_status()
    {
        $quotation = $this->createQuotation();

        $response = $this->post(route('admin.quotations.status', $quotation->id), [
            'status' => 'sent',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Quotation status updated.');

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => 'sent',
        ]);
    }

    public function test_update_status_validates_status_value()
    {
        $quotation = $this->createQuotation();

        $response = $this->post(route('admin.quotations.status', $quotation->id), [
            'status' => 'invalid_status',
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_it_can_convert_to_sales_when_accepted()
    {
        $product = Product::factory()->withStock(10)->create(['price' => 600]);
        $quotation = $this->createQuotation([
            'status' => 'accepted',
            'total' => 1110,
            'items' => [
                ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'price' => 1000, 'discount' => 0, 'total' => 1000],
            ],
        ]);

        $response = $this->post(route('admin.quotations.convert-to-sales', $quotation->id));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Quotation converted to sales order successfully.');

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => 'converted',
        ]);

        $sale = Sales::find($quotation->fresh()->converted_to_sales_id);

        $this->assertNotNull($sale);
        $this->assertSame('Unpaid', $sale->status, 'Converted sales must join the normal Unpaid/Partial lifecycle');
        $this->assertEqualsWithDelta(1110, $sale->total, 0.01);

        // The conversion used to build a $productsData array and then never
        // use it — a converted sale had NO line items at all.
        $this->assertDatabaseHas('sales_items', [
            'sales_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $this->assertEquals(9, $product->fresh()->stock_quantity, 'Conversion must move stock like any other sale');

        // Non-Indonesian tenant (test default): no PPN split, no withholding.
        $this->assertFalse($sale->ppn_enabled);
        $this->assertEqualsWithDelta(0, $sale->pph_amount, 0.01);

        $journal = JournalEntry::where('sourceable_type', Sales::class)
            ->where('sourceable_id', $sale->id)
            ->where('description', 'Sale Invoice #'.$sale->invoice)
            ->first();

        $this->assertNotNull($journal, 'Converted sale must post its invoice journal');

        $settings = $this->tenant->accounting_settings;
        $this->assertEqualsWithDelta(
            $journal->transactions()->where('type', 'debit')->sum('amount'),
            $journal->transactions()->where('type', 'credit')->sum('amount'),
            0.01,
            'Journal must balance'
        );

        // Dr AR 1110 / Cr Revenue 1110 (no VAT line for a non-ID tenant)
        $this->assertEqualsWithDelta(1110, $journal->transactions()->where('account_id', $settings['accounts_receivable_account_id'])->where('type', 'debit')->sum('amount'), 0.01);
        $this->assertEqualsWithDelta(1110, $journal->transactions()->where('account_id', $settings['sales_revenue_account_id'])->where('type', 'credit')->sum('amount'), 0.01);

        // Dr COGS / Cr Inventory at product cost (no PO batches → product price)
        $this->assertEqualsWithDelta(600, $journal->transactions()->where('account_id', $settings['cost_of_goods_sold_account_id'])->where('type', 'debit')->sum('amount'), 0.01);
        $this->assertEqualsWithDelta(600, $journal->transactions()->where('account_id', $settings['inventory_account_id'])->where('type', 'credit')->sum('amount'), 0.01);

        // Exactly one entry — no withholding or credit side-entries
        $this->assertSame(1, JournalEntry::where('sourceable_type', Sales::class)->where('sourceable_id', $sale->id)->count());
    }

    public function test_convert_to_sales_posts_ppn_split_journal_for_indonesian_tenant()
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        $ppnKeluaran = Account::create([
            'name' => 'PPN Keluaran '.$this->tenant->id,
            'code' => '2130-'.$this->tenant->id,
            'type' => 'liability',
            'tenant_id' => $this->tenant->id,
        ]);
        $this->tenant->update([
            'accounting_settings' => array_merge(
                $this->tenant->accounting_settings ?? [],
                ['ppn_keluaran_account_id' => $ppnKeluaran->id]
            ),
        ]);

        $this->tax->update(['is_ppn' => true]);

        $product = Product::factory()->withStock(10)->create(['price' => 400]);
        $quotation = $this->createQuotation([
            'status' => 'accepted',
            'subtotal' => 1000,
            'tax_amount' => 110,
            'total' => 1110,
            'items' => [
                ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'price' => 1000, 'discount' => 0, 'total' => 1000],
            ],
        ]);

        $response = $this->post(route('admin.quotations.convert-to-sales', $quotation->id));
        $response->assertSessionHas('success');

        $sale = Sales::find($quotation->fresh()->converted_to_sales_id);

        $this->assertTrue($sale->ppn_enabled, 'ID tenant + PPN tax must carry the e-Faktur snapshot');
        $this->assertEqualsWithDelta(110, $sale->ppn_amount, 0.01);

        $journal = JournalEntry::where('sourceable_type', Sales::class)
            ->where('sourceable_id', $sale->id)
            ->where('description', 'Sale Invoice #'.$sale->invoice)
            ->first();

        $this->assertNotNull($journal);

        $settings = $this->tenant->accounting_settings;
        $this->assertEqualsWithDelta(
            $journal->transactions()->where('type', 'debit')->sum('amount'),
            $journal->transactions()->where('type', 'credit')->sum('amount'),
            0.01,
            'Journal must balance'
        );

        // Dr AR 1110 (gross) / Cr Revenue 1000 (net of VAT) / Cr PPN Keluaran 110
        $this->assertEqualsWithDelta(1110, $journal->transactions()->where('account_id', $settings['accounts_receivable_account_id'])->where('type', 'debit')->sum('amount'), 0.01);
        $this->assertEqualsWithDelta(1000, $journal->transactions()->where('account_id', $settings['sales_revenue_account_id'])->where('type', 'credit')->sum('amount'), 0.01);
        $this->assertEqualsWithDelta(110, $journal->transactions()->where('account_id', $ppnKeluaran->id)->where('type', 'credit')->sum('amount'), 0.01);

        // COGS pair still posted at product cost
        $this->assertEqualsWithDelta(400, $journal->transactions()->where('account_id', $settings['cost_of_goods_sold_account_id'])->where('type', 'debit')->sum('amount'), 0.01);
        $this->assertEqualsWithDelta(400, $journal->transactions()->where('account_id', $settings['inventory_account_id'])->where('type', 'credit')->sum('amount'), 0.01);
    }

    public function test_convert_to_sales_copies_gated_pph_and_posts_withholding_journal()
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        $pphReceivable = Account::create([
            'name' => 'PPh Receivable '.$this->tenant->id,
            'code' => '1190-'.$this->tenant->id,
            'type' => 'asset',
            'tenant_id' => $this->tenant->id,
        ]);
        $this->tenant->update([
            'accounting_settings' => array_merge(
                $this->tenant->accounting_settings ?? [],
                ['pph_receivable_account_id' => $pphReceivable->id]
            ),
        ]);

        $pphTax = Tax::factory()->create(['rate' => 2, 'type' => 'exclusive', 'is_pph' => true, 'is_default' => false]);

        $product = Product::factory()->withStock(10)->create(['price' => 500]);
        // subtotal 1000 + PPN 110 = 1110, minus PPh 2% of 1000 = 20 → 1090
        $quotation = $this->createQuotation([
            'status' => 'accepted',
            'subtotal' => 1000,
            'tax_amount' => 110,
            'total' => 1090,
            'pph_enabled' => true,
            'pph_rate' => 2.0,
            'pph_amount' => 20,
            'pph_tax_id' => $pphTax->id,
            'items' => [
                ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'price' => 1000, 'discount' => 0, 'total' => 1000],
            ],
        ]);

        $response = $this->post(route('admin.quotations.convert-to-sales', $quotation->id));
        $response->assertSessionHas('success');

        $sale = Sales::find($quotation->fresh()->converted_to_sales_id);

        $this->assertTrue($sale->pph_enabled);
        $this->assertEqualsWithDelta(20, $sale->pph_amount, 0.01);
        $this->assertEqualsWithDelta(1090, $sale->total, 0.01);

        $entries = JournalEntry::where('sourceable_type', Sales::class)
            ->where('sourceable_id', $sale->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $entries, 'Invoice entry + PPh withholding entry expected');

        $invoice = $entries->firstWhere('description', 'Sale Invoice #'.$sale->invoice);
        $withholding = $entries->firstWhere('description', 'PPh Withholding on Sale #'.$sale->invoice);

        $this->assertNotNull($invoice);
        $this->assertNotNull($withholding, 'Withholding must be booked when pph_amount > 0');

        $settings = $this->tenant->accounting_settings;

        // Invoice: Dr AR = total + pph (gross before withholding) = 1110
        $this->assertEqualsWithDelta(1110, $invoice->transactions()->where('account_id', $settings['accounts_receivable_account_id'])->where('type', 'debit')->sum('amount'), 0.01);

        // Withholding: Dr PPh Receivable 20 / Cr AR 20 → net AR = 1090 = total
        $this->assertEqualsWithDelta(20, $withholding->transactions()->where('account_id', $pphReceivable->id)->where('type', 'debit')->sum('amount'), 0.01);
        $this->assertEqualsWithDelta(20, $withholding->transactions()->where('account_id', $settings['accounts_receivable_account_id'])->where('type', 'credit')->sum('amount'), 0.01);
    }

    public function test_convert_to_sales_keeps_gross_in_revenue_when_ppn_keluaran_account_is_missing()
    {
        // ID tenant + PPN tax, but NO PPN Keluaran account configured —
        // setupTenant() only sets the four core accounts. This pins
        // createSale()'s documented fallback at the conversion call site:
        // without the liability account the VAT stays in revenue instead
        // of failing the whole conversion.
        $this->tenant->update(['tax_jurisdiction' => 'ID']);
        $this->tax->update(['is_ppn' => true]);

        $this->assertArrayNotHasKey(
            'ppn_keluaran_account_id',
            $this->tenant->accounting_settings ?? [],
            'This test requires the fallback path: no PPN Keluaran account'
        );

        $product = Product::factory()->withStock(10)->create(['price' => 400]);
        $quotation = $this->createQuotation([
            'status' => 'accepted',
            'subtotal' => 1000,
            'tax_amount' => 110,
            'total' => 1110,
            'items' => [
                ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'price' => 1000, 'discount' => 0, 'total' => 1000],
            ],
        ]);

        $response = $this->post(route('admin.quotations.convert-to-sales', $quotation->id));
        $response->assertSessionHas('success');

        $sale = Sales::find($quotation->fresh()->converted_to_sales_id);

        // The e-Faktur snapshot still travels with the sale…
        $this->assertTrue($sale->ppn_enabled);
        $this->assertEqualsWithDelta(110, $sale->ppn_amount, 0.01);

        $journal = JournalEntry::where('sourceable_type', Sales::class)
            ->where('sourceable_id', $sale->id)
            ->where('description', 'Sale Invoice #'.$sale->invoice)
            ->first();

        $this->assertNotNull($journal);

        $settings = $this->tenant->accounting_settings;

        // …but the journal is the plain four-line form: no PPN Keluaran
        // transaction exists, so the gross (1110, VAT included) stays on
        // the revenue credit — identical to createSale()'s fallback.
        $this->assertCount(4, $journal->transactions);

        $this->assertEqualsWithDelta(
            $journal->transactions()->where('type', 'debit')->sum('amount'),
            $journal->transactions()->where('type', 'credit')->sum('amount'),
            0.01,
            'Journal must balance'
        );
        $this->assertEqualsWithDelta(1110, $journal->transactions()->where('account_id', $settings['accounts_receivable_account_id'])->where('type', 'debit')->sum('amount'), 0.01);
        $this->assertEqualsWithDelta(1110, $journal->transactions()->where('account_id', $settings['sales_revenue_account_id'])->where('type', 'credit')->sum('amount'), 0.01);
        $this->assertEqualsWithDelta(400, $journal->transactions()->where('account_id', $settings['cost_of_goods_sold_account_id'])->where('type', 'debit')->sum('amount'), 0.01);
        $this->assertEqualsWithDelta(400, $journal->transactions()->where('account_id', $settings['inventory_account_id'])->where('type', 'credit')->sum('amount'), 0.01);
    }

    public function test_convert_to_sales_for_non_indonesian_tenant_posts_plain_four_line_journal()
    {
        // US tenant: the PPN gate is jurisdiction AND tax-is_ppn, so the
        // e-Faktur snapshot never applies. The converted sale must look
        // exactly like any other non-ID sale — no PPN, no PPh, one
        // four-line journal with the tax left in revenue.
        $this->tenant->update(['tax_jurisdiction' => 'US']);

        $product = Product::factory()->withStock(10)->create(['price' => 400]);
        $quotation = $this->createQuotation([
            'status' => 'accepted',
            'subtotal' => 1000,
            'tax_amount' => 110,
            'total' => 1110,
            'items' => [
                ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'price' => 1000, 'discount' => 0, 'total' => 1000],
            ],
        ]);

        $response = $this->post(route('admin.quotations.convert-to-sales', $quotation->id));
        $response->assertSessionHas('success');

        $sale = Sales::find($quotation->fresh()->converted_to_sales_id);

        $this->assertFalse($sale->ppn_enabled);
        $this->assertEqualsWithDelta(0, $sale->ppn_amount, 0.01);
        $this->assertFalse($sale->pph_enabled);
        $this->assertEqualsWithDelta(0, $sale->pph_amount, 0.01);

        $entries = JournalEntry::where('sourceable_type', Sales::class)
            ->where('sourceable_id', $sale->id)
            ->get();

        $this->assertCount(1, $entries, 'Only the invoice entry — no withholding, no PPN split');

        $journal = $entries->first();
        $settings = $this->tenant->accounting_settings;

        $this->assertCount(4, $journal->transactions);

        $this->assertEqualsWithDelta(
            $journal->transactions()->where('type', 'debit')->sum('amount'),
            $journal->transactions()->where('type', 'credit')->sum('amount'),
            0.01,
            'Journal must balance'
        );
        // Non-ID sales carry no VAT split: AR = revenue = the full total
        $this->assertEqualsWithDelta(1110, $journal->transactions()->where('account_id', $settings['accounts_receivable_account_id'])->where('type', 'debit')->sum('amount'), 0.01);
        $this->assertEqualsWithDelta(1110, $journal->transactions()->where('account_id', $settings['sales_revenue_account_id'])->where('type', 'credit')->sum('amount'), 0.01);
        $this->assertEqualsWithDelta(400, $journal->transactions()->where('account_id', $settings['cost_of_goods_sold_account_id'])->where('type', 'debit')->sum('amount'), 0.01);
        $this->assertEqualsWithDelta(400, $journal->transactions()->where('account_id', $settings['inventory_account_id'])->where('type', 'credit')->sum('amount'), 0.01);
    }

    public function test_convert_to_sales_fails_when_not_accepted()
    {
        $quotation = $this->createQuotation(['status' => 'draft']);

        $response = $this->post(route('admin.quotations.convert-to-sales', $quotation->id));

        $response->assertSessionHas('error', 'Only accepted quotations can be converted to sales orders.');

        $this->assertDatabaseMissing('sales', [
            'customer_id' => $this->customer->id,
        ]);
    }

    public function test_it_can_bulk_delete_quotations()
    {
        $q1 = $this->createQuotation(['quotation_number' => 'QTN-'.now()->format('Ymd').'-0010']);
        $q2 = $this->createQuotation(['quotation_number' => 'QTN-'.now()->format('Ymd').'-0011']);

        $response = $this->post(route('admin.quotations.bulk-delete'), [
            'ids' => [$q1->id, $q2->id],
        ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('quotations', ['id' => $q1->id]);
        $this->assertDatabaseMissing('quotations', ['id' => $q2->id]);
    }

    public function test_bulk_delete_validates_ids_required()
    {
        $response = $this->post(route('admin.quotations.bulk-delete'), []);

        $response->assertSessionHasErrors(['ids']);
    }

    public function test_it_can_bulk_export_quotations_as_csv()
    {
        $this->createQuotation(['quotation_number' => 'QTN-20260101-0001']);

        $response = $this->post(route('admin.quotations.bulk-export'), [
            'export_option' => 'csv',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_bulk_export_validates_export_option()
    {
        $response = $this->post(route('admin.quotations.bulk-export'), [
            'export_option' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['export_option']);
    }

    public function test_it_can_get_product_details()
    {
        $product = Product::factory()->create(['selling_price' => 750]);

        $response = $this->get(route('admin.quotations.product.details', $product->id));

        $response->assertStatus(200);
        $response->assertJson([
            'price' => 750,
            'name' => $product->name,
        ]);
    }

    public function test_it_can_load_modal_view()
    {
        $quotation = $this->createQuotation();

        $response = $this->get(route('admin.quotations.modal-view', $quotation->id));

        $response->assertStatus(200);
    }

    public function test_it_can_print_quotation()
    {
        $quotation = $this->createQuotation();

        $response = $this->get(route('admin.quotations.print', $quotation->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.quotations.quotation-print-pdf');
    }

    public function test_store_calculates_correct_totals_with_percentage_discount()
    {
        $data = [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'products' => json_encode([
                ['product_id' => 1, 'name' => 'Item A', 'quantity' => 1, 'price' => 1000, 'discount' => 0, 'total' => 1000],
            ]),
            'discount_total' => 10,
            'discount_total_type' => 'percentage',
            'tax_id' => $this->tax->id,
        ];

        $response = $this->post(route('admin.quotations.store'), $data);

        $response->assertRedirect();

        // Subtotal=1000, discount=10% => 100, after discount=900, tax=11% => 99, total=999
        $this->assertDatabaseHas('quotations', [
            'subtotal' => 1000,
            'discount_value' => 10,
            'discount_amount' => 100,
            'tax_rate' => 11,
            'total' => 999,
        ]);
    }

    public function test_store_calculates_correct_totals_with_fixed_discount()
    {
        $data = [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'products' => json_encode([
                ['product_id' => 1, 'name' => 'Item A', 'quantity' => 1, 'price' => 1000, 'discount' => 0, 'total' => 1000],
            ]),
            'discount_total' => 200,
            'discount_total_type' => 'fixed',
            'tax_id' => $this->tax->id,
        ];

        $response = $this->post(route('admin.quotations.store'), $data);

        $response->assertRedirect();

        // Subtotal=1000, discount=200 fixed, after discount=800, tax=11% => 88, total=888
        $this->assertDatabaseHas('quotations', [
            'subtotal' => 1000,
            'discount_value' => 200,
            'discount_amount' => 200,
            'tax_rate' => 11,
            'total' => 888,
        ]);
    }

    /**
     * The create form posts its lines as `items[...]` inputs, not as the
     * hidden `products` JSON blob that sales and purchases post, so every
     * real submission was rejected with "products must be json". The line
     * totals must also follow the sales convention: a fixed discount comes
     * off every unit, and the stored lines carry the product name and the
     * worked-out total the print and show views read.
     */
    public function test_it_creates_a_quotation_from_the_item_inputs_the_form_posts()
    {
        $product = Product::factory()->create(['name' => 'Browser Line']);

        $response = $this->post(route('admin.quotations.store'), [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'price' => 1000, 'discount' => 50, 'discount_type' => 'fixed'],
            ],
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_id' => $this->tax->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Stored lines carry what the print and show views read.
        $quotation = Quotation::latest('id')->firstOrFail();
        $this->assertSame('Browser Line', $quotation->items[0]['name'] ?? null);
        $this->assertSame(1900.0, (float) ($quotation->items[0]['total'] ?? 0));

        // (1000 - 50 per unit) * 2 = 1900, not the raw 2000. Tax 11% = 209.
        $this->assertDatabaseHas('quotations', [
            'subtotal' => 1900,
            'tax_amount' => 209,
            'total' => 2109,
        ]);
    }

    /**
     * The edit form posts the same `items[...]` inputs as the create form,
     * so update() has to accept them and re-price the lines the same way.
     */
    public function test_it_updates_a_quotation_from_the_item_inputs_the_form_posts()
    {
        $product = Product::factory()->create(['name' => 'Edited Line']);
        $quotation = $this->createQuotation();

        $response = $this->put(route('admin.quotations.update', $quotation->id), [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'price' => 500, 'discount' => 10, 'discount_type' => 'percentage'],
            ],
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_id' => $this->tax->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // 10% of 500 = 50 off each unit, (500 - 50) * 3 = 1350. Tax 11% = 148.5.
        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'subtotal' => 1350,
            'tax_amount' => 148.5,
            'total' => 1498.5,
        ]);

        $quotation->refresh();
        $this->assertSame('Edited Line', $quotation->items[0]['name'] ?? null);
        $this->assertSame(1350.0, (float) ($quotation->items[0]['total'] ?? 0));
    }

    /**
     * quotation.js reads `.item-*` classes and posts a product_id per row.
     * The server-rendered edit row carried different classes (so nothing
     * recalculated on the edit form) and no product_id input at all (so the
     * converted sale would have had no product to line up with).
     */
    public function test_the_edit_form_posts_product_ids_and_the_classes_the_script_reads()
    {
        $quotation = $this->createQuotation();

        $response = $this->get(route('admin.quotations.edit', $quotation->id));

        $response->assertStatus(200);
        $response->assertSee('name="items[0][product_id]"', false);
        $response->assertSee('class="form-control text-end item-quantity"', false);
        $response->assertSee('class="form-control text-end item-subtotal"', false);
    }

    /**
     * A non-Indonesian tenant must not have its quotation total shrunk by
     * withholding: pph_amount is recorded as 0, so subtracting it anyway
     * would make the total disagree with the snapshot on the same row.
     *
     * This mirrors SalesServiceTest's non-ID gate — the controller change
     * had no coverage until now.
     */
    public function test_store_does_not_apply_pph_for_non_indonesian_tenant()
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

        $data = [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'products' => json_encode([
                ['product_id' => 1, 'name' => 'Widget', 'quantity' => 2, 'price' => 500, 'discount' => 0, 'total' => 1000],
            ]),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_id' => $this->tax->id,
            'pph_tax_id' => $pphTax->id,
        ];

        $response = $this->post(route('admin.quotations.store'), $data);
        $response->assertRedirect();

        // Subtotal 1000 + 11% tax = 1110. With PPh ungated this would drop
        // to 1090 while pph_amount stayed 0 — total and snapshot disagreeing.
        $quotation = Quotation::latest('id')->first();
        $this->assertEquals(1110.0, (float) $quotation->total, 'total must not shrink for a non-ID tenant');
        $this->assertEquals(0.0, (float) $quotation->pph_amount);
        $this->assertFalse((bool) $quotation->pph_enabled);
        $this->assertNull($quotation->pph_tax_id);
    }

    /**
     * Control for the test above: an Indonesian tenant with the same PPh tax
     * must apply it, so the gate is proven to discriminate rather than simply
     * never firing.
     */
    public function test_store_applies_pph_for_indonesian_tenant()
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);
        $this->assertTrue(app('currentTenant')->isIndonesian());

        $pphTax = Tax::create([
            'name' => 'PPh 23',
            'rate' => 2,
            'type' => 'exclusive',
            'is_active' => true,
            'is_pph' => true,
        ]);

        $data = [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'products' => json_encode([
                ['product_id' => 1, 'name' => 'Widget', 'quantity' => 2, 'price' => 500, 'discount' => 0, 'total' => 1000],
            ]),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_id' => $this->tax->id,
            'pph_tax_id' => $pphTax->id,
        ];

        $response = $this->post(route('admin.quotations.store'), $data);
        $response->assertRedirect();

        // 1000 + 11% = 1110, less 2% withholding on the taxable 1000 = 20.
        $quotation = Quotation::latest('id')->first();
        $this->assertEquals(1090.0, (float) $quotation->total, 'ID tenant must have PPh applied');
        $this->assertEquals(20.0, (float) $quotation->pph_amount);
        $this->assertTrue((bool) $quotation->pph_enabled);
        $this->assertEquals($pphTax->id, $quotation->pph_tax_id);
    }

    /**
     * The update path carries its own copy of the gate; it must agree with
     * store, or editing a quotation would silently change its total.
     */
    public function test_update_does_not_apply_pph_for_non_indonesian_tenant()
    {
        $quotation = $this->createQuotation();

        $this->tenant->update(['tax_jurisdiction' => 'US']);

        $pphTax = Tax::create([
            'name' => 'PPh 23',
            'rate' => 2,
            'type' => 'exclusive',
            'is_active' => true,
            'is_pph' => true,
        ]);

        $data = [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(60)->toDateString(),
            'products' => json_encode([
                ['product_id' => 1, 'name' => 'Widget', 'quantity' => 2, 'price' => 500, 'discount' => 0, 'total' => 1000],
            ]),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_id' => $this->tax->id,
            'pph_tax_id' => $pphTax->id,
        ];

        $response = $this->put(route('admin.quotations.update', $quotation->id), $data);
        $response->assertRedirect();

        $fresh = $quotation->fresh();
        $this->assertEquals(1110.0, (float) $fresh->total, 'update must not shrink the total for a non-ID tenant');
        $this->assertEquals(0.0, (float) $fresh->pph_amount);
        $this->assertFalse((bool) $fresh->pph_enabled);
        $this->assertNull($fresh->pph_tax_id);
    }

    public function test_show_returns_404_for_nonexistent_quotation()
    {
        $response = $this->get(route('admin.quotations.show', 99999));

        $response->assertStatus(404);
    }

    public function test_edit_returns_404_for_nonexistent_quotation()
    {
        $response = $this->get(route('admin.quotations.edit', 99999));

        $response->assertStatus(404);
    }

    public function test_delete_returns_404_for_nonexistent_quotation()
    {
        $response = $this->delete(route('admin.quotations.destroy', 99999));

        $response->assertStatus(404);
    }

    public function test_quotation_number_is_auto_generated()
    {
        $data = [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'products' => json_encode([
                ['product_id' => 1, 'name' => 'Item', 'quantity' => 1, 'price' => 100, 'discount' => 0, 'total' => 100],
            ]),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
        ];

        $this->post(route('admin.quotations.store'), $data);

        $prefix = 'QTN-'.now()->format('Ymd');
        $this->assertDatabaseHas('quotations', [
            'quotation_number' => $prefix.'-0001',
        ]);

        // Create another - should increment
        $data['products'] = json_encode([
            ['product_id' => 1, 'name' => 'Item', 'quantity' => 1, 'price' => 200, 'discount' => 0, 'total' => 200],
        ]);
        $this->post(route('admin.quotations.store'), $data);

        $this->assertDatabaseHas('quotations', [
            'quotation_number' => $prefix.'-0002',
        ]);
    }

    /**
     * A quotation keeps its discount two ways: discount_value with
     * discount_type (10 + percentage, what the customer agreed to) and
     * discount_amount (100, the worked-out figure). The conversion used to
     * copy discount_amount while keeping discount_type, which told every
     * re-calculating reader to take 100% off instead of 10%: the sales view,
     * the invoice PDF and the invoice email all showed 0 instead of 900,
     * balance due showed 0, and the CRM revenue figure went with it.
     */
    public function test_convert_to_sales_stores_a_discount_pair_that_still_recalculates()
    {
        $product = Product::factory()->withStock(10)->create(['price' => 60]);
        $quotation = $this->createQuotation([
            'status' => 'accepted',
            'subtotal' => 1000,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'discount_amount' => 100,
            // setUp()'s 11% default tax, kept explicit so the conversion and
            // a later edit resolve the same one: 1,000 less 10% is 900, plus
            // 11% tax is 999.
            'tax_id' => $this->tax->id,
            'tax_type' => 'exclusive',
            'tax_rate' => 11,
            'tax_amount' => 99,
            'total' => 999,
            'items' => [
                ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'price' => 1000, 'discount' => 0, 'discount_type' => 'fixed', 'total' => 1000],
            ],
        ]);

        $this->post(route('admin.quotations.convert-to-sales', $quotation->id))
            ->assertSessionHas('success', 'Quotation converted to sales order successfully.');

        $sale = Sales::find($quotation->fresh()->converted_to_sales_id);
        $this->assertNotNull($sale);

        // The pair has to describe ONE number, the way createSale() and
        // updateSale() store it: raw value plus the type that reads it.
        $this->assertEqualsWithDelta(10, $sale->order_discount, 0.01);
        $this->assertSame('percentage', $sale->order_discount_type);
        $this->assertEqualsWithDelta(999, $sale->total, 0.01);

        // Recalculated from the line items, which is what every screen,
        // the PDF, the invoice email and balance() rely on.
        $sale->load('salesItems');
        $this->assertEqualsWithDelta(999, $sale->grand_total, 0.01, 'The converted sale recalculated to the wrong total.');

        $viewData = app(SalesService::class)->getSalesViewData($sale->id);
        $this->assertEqualsWithDelta(999, $viewData['finalTotal'], 0.01, 'The sales view page showed the wrong total.');

        $settings = $this->tenant->accounting_settings;
        $journal = JournalEntry::where('sourceable_type', Sales::class)
            ->where('sourceable_id', $sale->id)
            ->where('description', 'Sale Invoice #'.$sale->invoice)
            ->first();

        $this->assertNotNull($journal);
        $this->assertEqualsWithDelta(999, $journal->transactions()->where('account_id', $settings['accounts_receivable_account_id'])->where('type', 'debit')->sum('amount'), 0.01);
        $this->assertEqualsWithDelta(999, $journal->transactions()->where('account_id', $settings['sales_revenue_account_id'])->where('type', 'credit')->sum('amount'), 0.01);
    }

    /**
     * The other half of the same bug. Once the pair was wrong, opening the
     * converted sale and pressing Save without changing anything recomputed
     * the total to 0 and re-journaled the sale as zero revenue against the
     * full cost of goods: the entry still balanced (0 + 60 = 0 + 60) so
     * nothing failed, it just quietly booked a sale that earned nothing.
     */
    public function test_editing_a_converted_sale_does_not_zero_it_out()
    {
        $product = Product::factory()->withStock(10)->create(['price' => 60]);
        $quotation = $this->createQuotation([
            'status' => 'accepted',
            'subtotal' => 1000,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'discount_amount' => 100,
            'tax_id' => $this->tax->id,
            'tax_type' => 'exclusive',
            'tax_rate' => 11,
            'tax_amount' => 99,
            'total' => 999,
            'items' => [
                ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'price' => 1000, 'discount' => 0, 'discount_type' => 'fixed', 'total' => 1000],
            ],
        ]);

        $this->post(route('admin.quotations.convert-to-sales', $quotation->id));
        $sale = Sales::find($quotation->fresh()->converted_to_sales_id);

        // Exactly what the edit form round-trips back: the pre-filled
        // discount box and its dropdown, plus the unchanged line.
        app(SalesService::class)->updateSale($sale->fresh(), [
            'customer_id' => $this->customer->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'discount_total' => $sale->order_discount,
            'discount_total_type' => $sale->order_discount_type,
            'products' => json_encode([
                ['product_id' => $product->id, 'quantity' => 1, 'customer_price' => 1000, 'discount' => 0, 'discount_type' => 'fixed'],
            ]),
        ]);

        $sale->refresh()->load('salesItems');
        $this->assertEqualsWithDelta(999, $sale->total, 0.01, 'Saving an unchanged converted sale destroyed its total.');
        $this->assertEqualsWithDelta(999, $sale->grand_total, 0.01);

        $settings = $this->tenant->accounting_settings;

        // Net for one account across every entry the sale owns plus the
        // reversals of those entries. A reversal carries no source document
        // of its own, so walking this way counts each transaction exactly
        // once and the signs cancel what should cancel.
        $net = function (string $accountKey) use ($sale, $settings): float {
            $total = 0.0;

            $entries = JournalEntry::where('sourceable_type', Sales::class)
                ->where('sourceable_id', $sale->id)
                ->get();

            foreach ($entries as $entry) {
                $reversals = JournalEntry::where('reversed_entry_id', $entry->id)->get();

                foreach ($entry->transactions->concat($reversals->flatMap->transactions) as $transaction) {
                    if ((int) $transaction->account_id === (int) $settings[$accountKey]) {
                        $total += ($transaction->type === 'debit' ? 1 : -1) * (float) $transaction->amount;
                    }
                }
            }

            return $total;
        };

        // Revenue nets to 999 credited, not 0: the original 999 credit is
        // cancelled by its reversal and the repost brings a fresh 999 back.
        $this->assertEqualsWithDelta(-999, $net('sales_revenue_account_id'), 0.01, 'Revenue was journaled as zero.');
        $this->assertEqualsWithDelta(999, $net('accounts_receivable_account_id'), 0.01, 'Receivables were journaled as zero.');
    }

    /**
     * A converted quotation is already the source of a posted sale, so the
     * form would only let the quote drift away from the sale it points at.
     * The view page is where it goes, the same way a POS sale is sent to
     * its receipt instead of the sales edit form.
     */
    public function test_editing_a_converted_quotation_takes_you_to_its_view_instead()
    {
        $sale = Sales::factory()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
        ]);
        $quotation = $this->createQuotation([
            'status' => 'converted',
            'converted_to_sales_id' => $sale->id,
        ]);

        $response = $this->get(route('admin.quotations.edit', $quotation->id));

        $response->assertRedirect(route('admin.quotations.show', $quotation->id));
    }

    /**
     * The write side of the same guard. Refused before validation, so the
     * quotation is left exactly as it was rather than half-updated.
     */
    public function test_updating_a_converted_quotation_is_refused()
    {
        $sale = Sales::factory()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
        ]);
        $quotation = $this->createQuotation([
            'status' => 'converted',
            'converted_to_sales_id' => $sale->id,
        ]);

        $response = $this->put(route('admin.quotations.update', $quotation->id), [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'products' => json_encode([
                ['product_id' => 1, 'name' => 'Changed', 'quantity' => 9, 'price' => 999, 'discount' => 0, 'total' => 8991],
            ]),
            'discount_total' => 50,
            'discount_total_type' => 'fixed',
            'notes' => 'Should never land',
        ]);

        $response->assertSessionHas('error');

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'subtotal' => 1000,
            'total' => 1110,
            'notes' => null,
        ]);
    }

    /**
     * The sequence reads everything after the last dash rather than the
     * last four characters, so QTN-20261115-10000 continues at 10001. It
     * used to read 0000 from that number and try to mint 0001 again, which
     * the unique constraint on quotation_number rejected once a day passed
     * ten thousand quotations.
     */
    public function test_quotation_numbers_do_not_wrap_after_four_digits()
    {
        $prefix = 'QTN-'.now()->format('Ymd');
        $this->createQuotation(['quotation_number' => $prefix.'-0001']);
        $this->createQuotation(['quotation_number' => $prefix.'-10000']);

        $response = $this->post(route('admin.quotations.store'), [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'products' => json_encode([
                ['product_id' => 1, 'name' => 'Widget', 'quantity' => 2, 'price' => 500, 'discount' => 0, 'total' => 1000],
            ]),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'tax_id' => $this->tax->id,
        ]);

        $response->assertSessionHas('success', 'Quotation created successfully.');
        $this->assertTrue(
            Quotation::where('quotation_number', $prefix.'-10001')->exists(),
            'The next quotation number wrapped back to 0001 instead of continuing past 10000.'
        );
    }

    protected function tearDown(): void
    {
        if (class_exists(\Mockery::class)) {
            \Mockery::close();
        }
        parent::tearDown();
    }
}
