<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\Customer;
use App\Models\CustomerCredit;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Warehouse;
use App\Services\SalesReturnService;
use Carbon\Carbon;
use Database\Seeders\AccountSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class SalesReturnControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected Sales $sale;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(AccountSeeder::class);
        $this->user->assignRole('superuser');

        // Ensure the current tenant has the necessary accounting settings
        $tenantId = app('currentTenant')->id;
        $this->tenant->accounting_settings = [
            'cash_account_id' => Account::where('code', '1110-'.$tenantId)->first()->id,
            'accounts_receivable_account_id' => Account::where('code', '1130-'.$tenantId)->first()->id,
            'sales_revenue_account_id' => Account::where('code', '4100-'.$tenantId)->first()->id,
            'sales_returns_account_id' => Account::where('code', '4200-'.$tenantId)->first()?->id ?? 1, // Fallback if seeder differs
            'cost_of_goods_sold_account_id' => Account::where('code', '5200-'.$tenantId)->first()->id,
            'inventory_account_id' => Account::where('code', '1140-'.$tenantId)->first()->id,
        ];
        $this->tenant->save();
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::factory()->create(['is_main' => true]);
        $this->product = Product::factory()->withStock(10)->create();
        $customer = Customer::factory()->create();

        $this->sale = Sales::factory()->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id, // Added
        ]);

        // Ensure sales item created and stock deducted (simulated)
        SalesItem::factory()->create([
            'sales_id' => $this->sale->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'customer_price' => 100,
            'total' => 500,
        ]);

        // Ensure pivot reflects post-sale state (10 - 5 = 5)
        // Note: Factory withStock(10) sets initial stock.
        // We simulate the sale already happened.
    }

    public function test_it_can_display_the_sales_return_index_page()
    {
        SalesReturn::create([
            'sales_id' => $this->sale->id,
            'user_id' => $this->user->id,
            'return_date' => '2025-12-12',
            'total_amount' => 100,
            'status' => 'Completed',
            'reason' => 'Test reason',
        ]);
        SalesReturn::create([
            'sales_id' => $this->sale->id,
            'user_id' => $this->user->id,
            'return_date' => '2025-12-12',
            'total_amount' => 200,
            'status' => 'Completed',
            'reason' => 'Test reason',
        ]);
        SalesReturn::create([
            'sales_id' => $this->sale->id,
            'user_id' => $this->user->id,
            'return_date' => '2025-12-12',
            'total_amount' => 300,
            'status' => 'Completed',
            'reason' => 'Test reason',
        ]);

        $response = $this->get(route('admin.sales-returns.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales-returns.index');
        $response->assertViewHas('returns');
    }

    public function test_it_can_display_the_create_page()
    {
        $response = $this->get(route('admin.sales-returns.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales-returns.create');
        $response->assertViewHas('sales');
    }

    public function test_it_can_store_a_new_sales_return()
    {
        $returnData = [
            'sales_id' => $this->sale->id,
            'return_date' => Carbon::now()->format('Y-m-d'),
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 100],
            ]),
            'total_amount' => 100,
            'status' => 'Completed',
            'reason' => 'Damaged goods', // Added reason for the return
        ];

        $response = $this->post(route('admin.sales-returns.store'), $returnData);

        $response->assertRedirect(route('admin.sales-returns.index'));
        $response->assertSessionHas('success', 'Sales return created successfully.');
        $this->assertDatabaseHas('sales_returns', ['sales_id' => $this->sale->id]);
    }

    /**
     * status was validated as required|string and nothing else, so any
     * string at all was written to the row: the index counts only ever
     * look for Pending/Completed/Canceled, so a bogus value silently
     * vanished from every status tile while still sitting in the table.
     * The allowed values already exist on the model.
     */
    public function test_store_rejects_a_status_outside_the_allowed_list()
    {
        $response = $this->post(route('admin.sales-returns.store'), [
            'sales_id' => $this->sale->id,
            'return_date' => Carbon::now()->format('Y-m-d'),
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 100],
            ]),
            'total_amount' => 100,
            'status' => 'Totally-Made-Up',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseMissing('sales_returns', ['sales_id' => $this->sale->id]);
    }

    /**
     * The item row casts quantity to integer (2.5 becomes 2) while the
     * stock move uses the raw 2.5, so shelf and document disagreed by half
     * a unit. A crafted post could do it: the form's parseInt cannot.
     */
    public function test_store_rejects_a_fractional_returned_quantity()
    {
        $before = $this->product->fresh()->stock_quantity;

        $response = $this->post(route('admin.sales-returns.store'), [
            'sales_id' => $this->sale->id,
            'return_date' => Carbon::now()->format('Y-m-d'),
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2.5, 'price' => 100],
            ]),
            'total_amount' => 250,
            'status' => 'Pending',
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseMissing('sales_returns', ['sales_id' => $this->sale->id]);
        $this->assertEquals($before, $this->product->fresh()->stock_quantity, 'A refused return must not move stock.');
    }

    /**
     * A product the invoice never sold cannot be returned: the service
     * refuses it, and that refusal has to come back as the flash error the
     * session partial renders - not as a 500 and not as a silent success
     * with a return row priced off nothing.
     */
    public function test_store_refuses_a_product_that_is_not_on_the_invoice()
    {
        $productNotOnInvoice = Product::factory()->create();

        $response = $this->post(route('admin.sales-returns.store'), [
            'sales_id' => $this->sale->id,
            'return_date' => Carbon::now()->format('Y-m-d'),
            'items' => json_encode([
                ['product_id' => $productNotOnInvoice->id, 'returned_quantity' => 1, 'price' => 100],
            ]),
            'total_amount' => 100,
            'status' => 'Pending',
            'reason' => 'Not on this invoice',
        ]);

        $response->assertRedirect();
        $this->assertNotNull(session('error'), 'The refusal must surface as the flash error.');
        $this->assertStringContainsString('not on invoice', session('error'));
        $this->assertDatabaseMissing('sales_returns', ['sales_id' => $this->sale->id]);
    }

    public function test_it_can_show_a_sales_return()
    {
        $salesReturn = SalesReturn::create([
            'sales_id' => $this->sale->id,
            'user_id' => $this->user->id,
            'return_date' => '2025-12-12',
            'total_amount' => 100,
            'status' => 'Completed',
            'reason' => 'Test reason',
        ]);
        SalesReturnItem::create([ // Create a SalesReturnItem
            'sales_return_id' => $salesReturn->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);

        $response = $this->get(route('admin.sales-returns.show', $salesReturn->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales-returns.show');
        $response->assertViewHas('salesReturn', $salesReturn);
    }

    /**
     * The show page must render the DERIVED reversal, the exact figure the
     * journal books (ratio 0.5 of a 200 net sale -> PPN 11, not 22), rather
     * than a copy of the parent sale's tax figures.
     */
    public function test_it_shows_the_derived_tax_reversal()
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        $tenantId = app('currentTenant')->id;
        $ppnKeluaranAccount = Account::where('code', '2130-'.$tenantId)->firstOrFail();
        $this->tenant->update([
            'accounting_settings' => array_merge(
                $this->tenant->accounting_settings ?? [],
                ['ppn_keluaran_account_id' => $ppnKeluaranAccount->id]
            ),
        ]);

        $this->sale->update([
            'ppn_enabled' => true,
            'ppn_rate' => 11.0,
            'ppn_amount' => 22.0,
            'tax_rate' => 11,
            'tax_type' => 'exclusive',
            'total' => 222.0,
        ]);

        // Original sale journal: Dr AR 222 / Cr Revenue 200 /
        // Cr PPN Keluaran 22, the net base the derivation prorates from.
        $settings = $this->tenant->accounting_settings;
        $originalJournal = JournalEntry::create([
            'date' => now(),
            'description' => 'Sale Invoice #'.$this->sale->invoice,
            'sourceable_id' => $this->sale->id,
            'sourceable_type' => Sales::class,
            'status' => 'posted',
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $settings['accounts_receivable_account_id'],
            'type' => 'debit',
            'amount' => 222.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $settings['sales_revenue_account_id'],
            'type' => 'credit',
            'amount' => 200.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $ppnKeluaranAccount->id,
            'type' => 'credit',
            'amount' => 22.0,
        ]);

        // Partial return: 100 of the 200 net, so half the VAT reverses.
        $salesReturn = SalesReturn::create([
            'sales_id' => $this->sale->id,
            'user_id' => $this->user->id,
            'return_date' => '2025-12-12',
            'total_amount' => 100,
            'status' => 'Completed',
            'reason' => 'Partial return',
        ]);

        $response = $this->get(route('admin.sales-returns.show', $salesReturn->id));

        $response->assertStatus(200);
        $response->assertViewHas('taxReversal', function (array $reversal) {
            return $reversal['ratio'] == 0.5 && $reversal['ppn'] == 11.0;
        });
        // The derived figure is actually rendered on the page.
        $response->assertSee(__('messages.return_ppn_reversal'));
        $response->assertSee('11');
    }

    /**
     * The form preview endpoint must return the same derived figures the
     * journal books, because it is the same code path.
     */
    public function test_it_derives_the_tax_reversal_as_json()
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        $tenantId = app('currentTenant')->id;
        $ppnKeluaranAccount = Account::where('code', '2130-'.$tenantId)->firstOrFail();
        $this->tenant->update([
            'accounting_settings' => array_merge(
                $this->tenant->accounting_settings ?? [],
                ['ppn_keluaran_account_id' => $ppnKeluaranAccount->id]
            ),
        ]);

        $this->sale->update([
            'ppn_enabled' => true,
            'ppn_rate' => 11.0,
            'ppn_amount' => 22.0,
            'total' => 222.0,
        ]);

        $settings = $this->tenant->accounting_settings;
        $originalJournal = JournalEntry::create([
            'date' => now(),
            'description' => 'Sale Invoice #'.$this->sale->invoice,
            'sourceable_id' => $this->sale->id,
            'sourceable_type' => Sales::class,
            'status' => 'posted',
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $settings['accounts_receivable_account_id'],
            'type' => 'debit',
            'amount' => 222.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $settings['sales_revenue_account_id'],
            'type' => 'credit',
            'amount' => 200.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $ppnKeluaranAccount->id,
            'type' => 'credit',
            'amount' => 22.0,
        ]);

        // Half of the 200 net returned: half the output VAT reverses.
        $response = $this->getJson(route('admin.sales-returns.derive-reversal', [
            'sale_id' => $this->sale->id,
            'total' => 100,
        ]));

        $response->assertOk();
        $data = $response->json();
        $this->assertEquals(0.5, $data['ratio']);
        $this->assertEquals(11.0, $data['ppn']);
        $this->assertEquals(0.0, $data['pph']);

        // A zero total derives nothing to reverse, so the preview stays hidden.
        $empty = $this->getJson(route('admin.sales-returns.derive-reversal', [
            'sale_id' => $this->sale->id,
            'total' => 0,
        ]));
        $empty->assertOk();
        $this->assertEquals(0.0, $empty->json('ppn'));

        // An unknown sale is a 404, never another tenant's document.
        $this->getJson(route('admin.sales-returns.derive-reversal', [
            'sale_id' => 999999,
            'total' => 100,
        ]))->assertNotFound();
    }

    public function test_it_can_display_the_edit_page()
    {
        $salesReturn = SalesReturn::create([
            'sales_id' => $this->sale->id,
            'user_id' => $this->user->id,
            'return_date' => '2025-12-12',
            'total_amount' => 100,
            'status' => 'Completed',
        ]);

        $response = $this->get(route('admin.sales-returns.edit', $salesReturn->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales-returns.edit');
        $response->assertViewHas('salesReturn', $salesReturn);
    }

    public function test_it_can_update_a_sales_return()
    {
        $salesReturn = SalesReturn::create([
            'sales_id' => $this->sale->id,
            'user_id' => $this->user->id,
            'return_date' => '2025-12-12',
            'total_amount' => 100,
            'status' => 'Completed',
        ]);
        SalesReturnItem::create([
            'sales_return_id' => $salesReturn->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);

        $updateData = [
            'sales_id' => $this->sale->id,
            'return_date' => Carbon::now()->addDay()->format('Y-m-d'),
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 150],
            ]),
            'total_amount' => 300,
            'status' => 'Pending',
            'reason' => 'Customer changed mind', // Added reason for the return update
        ];

        $response = $this->put(route('admin.sales-returns.update', $salesReturn->id), $updateData);

        $response->assertRedirect(route('admin.sales-returns.index'));
        $response->assertSessionHas('success', 'Sales return updated successfully.');
        $this->assertDatabaseHas('sales_returns', [
            'id' => $salesReturn->id,
            'status' => 'Pending',
        ]);
    }

    public function test_it_can_delete_a_sales_return()
    {
        $salesReturn = SalesReturn::factory()->create();

        $response = $this->delete(route('admin.sales-returns.destroy', $salesReturn->id));

        $response->assertRedirect(route('admin.sales-returns.index'));
        $response->assertSessionHas('success', 'Sales return deleted successfully.');
        $this->assertDatabaseMissing('sales_returns', ['id' => $salesReturn->id]);
    }

    public function test_it_can_get_sales_items()
    {
        $response = $this->get(route('admin.sales-returns.items', $this->sale->id));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    public function test_it_can_bulk_delete_sales_returns()
    {
        $returns = SalesReturn::factory()->count(3)->create();
        $ids = $returns->pluck('id')->toArray();

        $response = $this->postJson(route('admin.sales-returns.bulk-delete'), ['ids' => $ids]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        foreach ($ids as $id) {
            $this->assertDatabaseMissing('sales_returns', ['id' => $id]);
        }
    }

    public function test_it_can_bulk_complete_sales_returns()
    {
        $returns = SalesReturn::factory()->count(3)->create(['status' => 'Pending']);
        $ids = $returns->pluck('id')->toArray();

        $response = $this->postJson(route('admin.sales-returns.bulk-complete'), ['ids' => $ids]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        foreach ($ids as $id) {
            $this->assertDatabaseHas('sales_returns', ['id' => $id, 'status' => 'Completed']);
        }
    }

    public function test_bulk_complete_skips_canceled_rows_and_reports_them()
    {
        $live = SalesReturn::factory()->create(['status' => 'Pending']);
        $canceled = SalesReturn::factory()->create(['status' => 'Canceled']);

        $response = $this->postJson(route('admin.sales-returns.bulk-complete'), [
            'ids' => [$live->id, $canceled->id],
        ]);

        // Option (b): the valid row completes, the canceled row is skipped
        // and named in the result instead of failing the whole batch.
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'counts' => ['completed' => 1, 'already_completed' => 0, 'skipped' => 1],
        ]);
        $this->assertStringContainsString('skipped', $response->json('message'));
        $this->assertDatabaseHas('sales_returns', ['id' => $live->id, 'status' => 'Completed']);
        $this->assertDatabaseHas('sales_returns', ['id' => $canceled->id, 'status' => 'Canceled']);
    }

    public function test_it_can_bulk_cancel_sales_returns()
    {
        $returns = SalesReturn::factory()->count(3)->create(['status' => 'Pending']);
        $ids = $returns->pluck('id')->toArray();

        $response = $this->postJson(route('admin.sales-returns.bulk-cancel'), ['ids' => $ids]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        foreach ($ids as $id) {
            $this->assertDatabaseHas('sales_returns', ['id' => $id, 'status' => 'Canceled']);
        }
    }

    public function test_bulk_cancel_with_an_already_canceled_row_is_idempotent()
    {
        $live = SalesReturn::factory()->create(['status' => 'Pending']);
        $canceled = SalesReturn::factory()->create(['status' => 'Canceled']);

        $response = $this->postJson(route('admin.sales-returns.bulk-cancel'), [
            'ids' => [$live->id, $canceled->id],
        ]);

        // cancelSalesReturn() early-returns on a Canceled row, so re-selecting
        // one must not revert its stock or reverse its journal a second time.
        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('sales_returns', ['id' => $live->id, 'status' => 'Canceled']);
        $this->assertDatabaseHas('sales_returns', ['id' => $canceled->id, 'status' => 'Canceled']);
        $this->assertEquals(
            0,
            JournalEntry::whereNotNull('reversed_entry_id')->count(),
            'Neither factory row ever posted an entry, so cancel may not mint a reversal.'
        );
    }

    public function test_it_can_show_modal_view()
    {
        $salesReturn = SalesReturn::factory()->create(['sales_id' => $this->sale->id]);

        $response = $this->get(route('admin.sales-returns.modal-view', $salesReturn->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.layouts.modals.sales.srmodals-view');
        $response->assertViewHas('salesReturn', $salesReturn);
    }

    /**
     * Build a Completed return against a paid sale, which is the only way a
     * customer credit comes into existence.
     */
    private function returnWithCredit(): SalesReturn
    {
        $this->sale->update(['total' => 500]);
        $this->sale->payments()->create([
            'amount' => 500,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Cash',
        ]);

        return app(SalesReturnService::class)->createSalesReturn([
            'sales_id' => $this->sale->id,
            'return_date' => now()->format('Y-m-d'),
            'status' => 'Completed',
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 100],
            ]),
        ]);
    }

    public function test_it_can_refund_a_customer_credit_as_cash()
    {
        $salesReturn = $this->returnWithCredit();
        $creditId = $salesReturn->fresh()->customer_credit_id;
        $this->assertNotNull($creditId);

        $response = $this->post(route('admin.sales-returns.refund', $salesReturn->id));

        $response->assertRedirect(route('admin.sales-returns.show', $salesReturn->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('customer_credits', [
            'id' => $creditId,
            'status' => 'refunded',
            'available_amount' => 0,
        ]);

        // Dr AR / Cr Cash, sourced to the credit so a later return edit or
        // delete cannot reverse money that has already left.
        $entry = JournalEntry::where('sourceable_type', CustomerCredit::class)
            ->where('sourceable_id', $creditId)
            ->where('description', 'like', 'Customer Credit Refund%')
            ->first();
        $this->assertNotNull($entry);

        $settings = $this->tenant->accounting_settings;
        $types = $entry->transactions->pluck('type', 'account_id');
        $this->assertEquals('debit', $types[$settings['accounts_receivable_account_id']]);
        $this->assertEquals('credit', $types[$settings['cash_account_id']]);
    }

    public function test_refunding_twice_pays_out_only_once()
    {
        $salesReturn = $this->returnWithCredit();
        $creditId = $salesReturn->fresh()->customer_credit_id;

        $this->post(route('admin.sales-returns.refund', $salesReturn->id))
            ->assertSessionHas('success');

        $second = $this->post(route('admin.sales-returns.refund', $salesReturn->id));
        $second->assertSessionHas('error');

        $this->assertEquals(
            1,
            JournalEntry::where('sourceable_type', CustomerCredit::class)
                ->where('sourceable_id', $creditId)
                ->where('description', 'like', 'Customer Credit Refund%')
                ->count(),
            'A second refund must not book a second cash-out.'
        );
    }

    public function test_refund_is_denied_without_the_payments_permission()
    {
        $salesReturn = $this->returnWithCredit();

        // No role, so no create-payments: the route must refuse rather than
        // let anyone who can view a return push cash out of the business.
        // Spatie caches the role relations on the model, so the detach has
        // to be followed by a refresh or can() still answers from cache.
        $this->user->roles()->detach();
        $this->user->refresh();
        $this->assertFalse($this->user->can('create-payments'));

        $this->post(route('admin.sales-returns.refund', $salesReturn->id))
            ->assertForbidden();

        $this->assertDatabaseHas('customer_credits', [
            'id' => $salesReturn->fresh()->customer_credit_id,
            'status' => 'active',
        ]);
    }
}
