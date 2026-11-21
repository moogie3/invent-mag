<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\POItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Carbon\Carbon;
use Database\Seeders\AccountSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class PurchaseReturnControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected Purchase $purchase;

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
            'accounts_payable_account_id' => Account::where('code', '2110-'.$tenantId)->first()->id,
            'inventory_account_id' => Account::where('code', '1140-'.$tenantId)->first()->id,
            'cost_of_goods_sold_account_id' => Account::where('code', '5100-'.$tenantId)->first()->id,
        ];
        $this->tenant->save();
        $this->actingAs($this->user);

        $this->product = Product::factory()->create();
        $this->purchase = Purchase::factory()->create();
        // Pinned (the factory picks 1-100): the tests below return 1 to 3
        // units against this line, so it has to be big enough for every run.
        POItem::factory()->create([
            'po_id' => $this->purchase->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
        ]);
    }

    public function test_it_can_display_the_purchase_return_index_page()
    {
        PurchaseReturn::factory()->count(3)->create(['purchase_id' => $this->purchase->id]);

        $response = $this->get(route('admin.por.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.por.index');
        $response->assertViewHas('returns');
    }

    public function test_it_can_display_the_create_page()
    {
        $response = $this->get(route('admin.por.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.por.create');
        $response->assertViewHas('purchases');
    }

    public function test_it_can_store_a_new_purchase_return()
    {
        // Set up accounting settings with all required accounts on the tenant
        $tenantId = app('currentTenant')->id;

        $cashAccount = Account::where('code', '1110-'.$tenantId)->first();
        $apAccount = Account::where('code', '2110-'.$tenantId)->first();
        $inventoryAccount = Account::where('code', '1140-'.$tenantId)->first();
        $cogsAccount = Account::where('code', '5100-'.$tenantId)->first();

        $this->tenant->accounting_settings = [
            'cash_account_id' => $cashAccount->id ?? null,
            'accounts_payable_account_id' => $apAccount->id ?? null,
            'inventory_account_id' => $inventoryAccount->id ?? null,
            'cost_of_goods_sold_account_id' => $cogsAccount->id ?? null,
        ];
        $this->tenant->save();

        // Create purchase with proper status
        $this->purchase->update(['status' => 'Paid', 'amount_received' => 100]);

        // Add product to purchase
        POItem::factory()->create([
            'po_id' => $this->purchase->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 50,
            'total' => 100,
        ]);

        $returnData = [
            'purchase_id' => $this->purchase->id,
            'return_date' => Carbon::now()->format('Y-m-d'),
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 50],
            ]),
            'total_amount' => 50,
            'status' => 'Completed', // This is for purchase return status
        ];

        $response = $this->post(route('admin.por.store'), $returnData);

        $response->assertRedirect(route('admin.por.index'));
        $response->assertSessionHas('success', 'Purchase return created successfully.');
        $this->assertDatabaseHas('purchase_returns', ['purchase_id' => $this->purchase->id]);
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
        $response = $this->post(route('admin.por.store'), [
            'purchase_id' => $this->purchase->id,
            'return_date' => Carbon::now()->format('Y-m-d'),
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 50],
            ]),
            'total_amount' => 50,
            'status' => 'Totally-Made-Up',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseMissing('purchase_returns', ['purchase_id' => $this->purchase->id]);
    }

    /**
     * The item row casts quantity to integer (2.5 becomes 2) while the
     * stock move uses the raw 2.5, so shelf and document disagreed by half
     * a unit. A crafted post could do it: the form's parseInt cannot.
     */
    public function test_store_rejects_a_fractional_returned_quantity()
    {
        $before = $this->product->fresh()->stock_quantity;

        $response = $this->post(route('admin.por.store'), [
            'purchase_id' => $this->purchase->id,
            'return_date' => Carbon::now()->format('Y-m-d'),
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2.5, 'price' => 50],
            ]),
            'total_amount' => 125,
            'status' => 'Pending',
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseMissing('purchase_returns', ['purchase_id' => $this->purchase->id]);
        $this->assertEquals($before, $this->product->fresh()->stock_quantity, 'A refused return must not move stock.');
    }

    public function test_it_can_show_a_purchase_return()
    {
        $purchaseReturn = PurchaseReturn::factory()->create(['purchase_id' => $this->purchase->id]);

        $response = $this->get(route('admin.por.show', $purchaseReturn->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.por.show');
        $response->assertViewHas('por', $purchaseReturn);
    }

    /**
     * The show page must render the DERIVED reversal, the exact figure the
     * journal books (ratio 0.5 of a 200 inventory base -> PPN 11 / PPh 2),
     * rather than a copy of the parent purchase's tax figures.
     */
    public function test_it_shows_the_derived_tax_reversal()
    {
        $tenantId = app('currentTenant')->id;
        $ppnMasukanAccount = Account::where('code', '1160-'.$tenantId)->firstOrFail();
        $pphPayableAccount = Account::where('code', '2150-'.$tenantId)->firstOrFail();
        $this->tenant->update([
            'accounting_settings' => array_merge(
                $this->tenant->accounting_settings ?? [],
                [
                    'ppn_masukan_account_id' => $ppnMasukanAccount->id,
                    'pph_payable_account_id' => $pphPayableAccount->id,
                ]
            ),
        ]);

        // Original purchase journal: Dr Inventory 200 / Dr PPN Masukan 22 /
        // Cr PPh Payable 4 / Cr AP 218, the net base the derivation
        // prorates from.
        $settings = $this->tenant->accounting_settings;
        $originalJournal = JournalEntry::create([
            'date' => now(),
            'description' => 'Purchase '.$this->purchase->invoice,
            'sourceable_id' => $this->purchase->id,
            'sourceable_type' => Purchase::class,
            'status' => 'posted',
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $settings['inventory_account_id'],
            'type' => 'debit',
            'amount' => 200.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $ppnMasukanAccount->id,
            'type' => 'debit',
            'amount' => 22.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $pphPayableAccount->id,
            'type' => 'credit',
            'amount' => 4.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $settings['accounts_payable_account_id'],
            'type' => 'credit',
            'amount' => 218.0,
        ]);

        // Partial return: 100 of the 200 net, so half of each reverses.
        $purchaseReturn = PurchaseReturn::factory()->create([
            'purchase_id' => $this->purchase->id,
            'total_amount' => 100,
        ]);

        $response = $this->get(route('admin.por.show', $purchaseReturn->id));

        $response->assertStatus(200);
        $response->assertViewHas('taxReversal', function (array $reversal) {
            return $reversal['ratio'] == 0.5
                && $reversal['ppn'] == 11.0
                && $reversal['pph'] == 2.0;
        });
        // The derived figures are actually rendered on the page.
        $response->assertSee(__('messages.return_ppn_reversal'));
        $response->assertSee(__('messages.return_pph_reversal'));
    }

    /**
     * The form preview endpoint must return the same derived figures the
     * journal books, because it is the same code path.
     */
    public function test_it_derives_the_tax_reversal_as_json()
    {
        $tenantId = app('currentTenant')->id;
        $ppnMasukanAccount = Account::where('code', '1160-'.$tenantId)->firstOrFail();
        $pphPayableAccount = Account::where('code', '2150-'.$tenantId)->firstOrFail();
        $this->tenant->update([
            'accounting_settings' => array_merge(
                $this->tenant->accounting_settings ?? [],
                [
                    'ppn_masukan_account_id' => $ppnMasukanAccount->id,
                    'pph_payable_account_id' => $pphPayableAccount->id,
                ]
            ),
        ]);

        $settings = $this->tenant->accounting_settings;
        $originalJournal = JournalEntry::create([
            'date' => now(),
            'description' => 'Purchase '.$this->purchase->invoice,
            'sourceable_id' => $this->purchase->id,
            'sourceable_type' => Purchase::class,
            'status' => 'posted',
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $settings['inventory_account_id'],
            'type' => 'debit',
            'amount' => 200.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $ppnMasukanAccount->id,
            'type' => 'debit',
            'amount' => 22.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $pphPayableAccount->id,
            'type' => 'credit',
            'amount' => 4.0,
        ]);
        $originalJournal->transactions()->create([
            'account_id' => $settings['accounts_payable_account_id'],
            'type' => 'credit',
            'amount' => 218.0,
        ]);

        // Half of the 200 net returned: half of each tax reverses.
        $response = $this->getJson(route('admin.por.derive-reversal', [
            'purchase_id' => $this->purchase->id,
            'total' => 100,
        ]));

        $response->assertOk();
        $data = $response->json();
        $this->assertEquals(0.5, $data['ratio']);
        $this->assertEquals(11.0, $data['ppn']);
        $this->assertEquals(2.0, $data['pph']);

        // A zero total derives nothing to reverse, so the preview stays hidden.
        $empty = $this->getJson(route('admin.por.derive-reversal', [
            'purchase_id' => $this->purchase->id,
            'total' => 0,
        ]));
        $empty->assertOk();
        $this->assertEquals(0.0, $empty->json('ppn'));

        // An unknown purchase is a 404, never another tenant's document.
        $this->getJson(route('admin.por.derive-reversal', [
            'purchase_id' => 999999,
            'total' => 100,
        ]))->assertNotFound();
    }

    public function test_it_can_display_the_edit_page()
    {
        $purchaseReturn = PurchaseReturn::factory()->create(['purchase_id' => $this->purchase->id]);

        $response = $this->get(route('admin.por.edit', $purchaseReturn->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.por.edit');
        $response->assertViewHas('por', $purchaseReturn);
    }

    public function test_it_can_update_a_purchase_return()
    {
        $purchaseReturn = PurchaseReturn::factory()->create(['purchase_id' => $this->purchase->id]);
        PurchaseReturnItem::factory()->create(['purchase_return_id' => $purchaseReturn->id]);

        $updateData = [
            'purchase_id' => $this->purchase->id,
            'return_date' => Carbon::now()->addDay()->format('Y-m-d'),
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 150],
            ]),
            'total_amount' => 300,
            'status' => 'Pending',
        ];

        $response = $this->put(route('admin.por.update', $purchaseReturn->id), $updateData);

        $response->assertRedirect(route('admin.por.index'));
        $response->assertSessionHas('success', 'Purchase return updated successfully.');
        $this->assertDatabaseHas('purchase_returns', [
            'id' => $purchaseReturn->id,
            'status' => 'Pending',
        ]);
    }

    public function test_it_can_delete_a_purchase_return()
    {
        $purchaseReturn = PurchaseReturn::factory()->create();

        $response = $this->delete(route('admin.por.destroy', $purchaseReturn->id));

        $response->assertRedirect(route('admin.por.index'));
        $response->assertSessionHas('success', 'Purchase return deleted successfully.');
        $this->assertDatabaseMissing('purchase_returns', ['id' => $purchaseReturn->id]);
    }

    public function test_it_can_get_purchase_items()
    {
        $response = $this->get(route('admin.por.items', $this->purchase->id));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    /**
     * available_quantity must reflect every LIVE return: a Pending one
     * already holds those units, a Canceled one holds none. The endpoint
     * counted only Completed returns, so the form offered quantities that
     * were already spoken for and every real submission over-returned.
     */
    public function test_get_purchase_items_counts_pending_returns_and_ignores_canceled_ones()
    {
        // setUp ordered 10 of $this->product.
        $pending = PurchaseReturn::factory()->create([
            'purchase_id' => $this->purchase->id,
            'status' => 'Pending',
        ]);
        PurchaseReturnItem::factory()->create([
            'purchase_return_id' => $pending->id,
            'product_id' => $this->product->id,
            'quantity' => 4,
        ]);

        $canceled = PurchaseReturn::factory()->create([
            'purchase_id' => $this->purchase->id,
            'status' => 'Canceled',
        ]);
        PurchaseReturnItem::factory()->create([
            'purchase_return_id' => $canceled->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
        ]);

        $response = $this->get(route('admin.por.items', $this->purchase->id));

        $response->assertStatus(200);
        $item = collect($response->json())->firstWhere('product_id', $this->product->id);
        $this->assertNotNull($item);
        // 10 ordered - 4 held by the Pending return; the Canceled one holds none.
        $this->assertEquals(6, $item['available_quantity']);
    }

    /**
     * A cap refusal is a normal business answer, not a crash: it has to
     * come back as the flash error the session partial renders, with no row
     * written.
     */
    public function test_store_surfaces_a_refused_return_as_an_error_instead_of_a_500()
    {
        // setUp ordered 10; ask for 11.
        $response = $this->post(route('admin.por.store'), [
            'purchase_id' => $this->purchase->id,
            'return_date' => Carbon::now()->format('Y-m-d'),
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 11, 'price' => 50],
            ]),
            'total_amount' => 550,
            'status' => 'Pending',
        ]);

        $response->assertRedirect();
        $this->assertNotNull(session('error'), 'The refusal must surface as the flash error.');
        $this->assertStringContainsString('only 10 of the 10 purchased units can still be returned', session('error'));
        $this->assertDatabaseMissing('purchase_returns', ['purchase_id' => $this->purchase->id]);
    }

    /**
     * Same contract on update: the edited return's own old units are
     * excluded from its check, so asking for 11 against 10 ordered is
     * refused as a flash error rather than a 500.
     */
    public function test_update_surfaces_a_refused_return_as_an_error_instead_of_a_500()
    {
        $purchaseReturn = PurchaseReturn::factory()->create([
            'purchase_id' => $this->purchase->id,
            'status' => 'Pending',
        ]);
        PurchaseReturnItem::factory()->create([
            'purchase_return_id' => $purchaseReturn->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $response = $this->put(route('admin.por.update', $purchaseReturn->id), [
            'purchase_id' => $this->purchase->id,
            'return_date' => Carbon::now()->addDay()->format('Y-m-d'),
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 11, 'price' => 50],
            ]),
            'total_amount' => 550,
            'status' => 'Pending',
        ]);

        $response->assertRedirect();
        $this->assertNotNull(session('error'), 'The refusal must surface as the flash error.');
        $this->assertStringContainsString('only 10 of the 10 purchased units can still be returned', session('error'));
        $this->assertEquals(1, $purchaseReturn->fresh()->items->sum('quantity'));
    }

    public function test_it_can_bulk_delete_purchase_returns()
    {
        $returns = PurchaseReturn::factory()->count(3)->create();
        $ids = $returns->pluck('id')->toArray();

        $response = $this->postJson(route('admin.por.bulk-delete'), ['ids' => $ids]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        foreach ($ids as $id) {
            $this->assertDatabaseMissing('purchase_returns', ['id' => $id]);
        }
    }

    public function test_it_can_bulk_complete_purchase_returns()
    {
        $returns = PurchaseReturn::factory()->count(3)->create(['status' => 'Pending']);
        $ids = $returns->pluck('id')->toArray();

        $response = $this->postJson(route('admin.por.bulk-complete'), ['ids' => $ids]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        foreach ($ids as $id) {
            $this->assertDatabaseHas('purchase_returns', ['id' => $id, 'status' => 'Completed']);
        }
    }

    public function test_bulk_complete_skips_canceled_rows_and_reports_them()
    {
        $live = PurchaseReturn::factory()->create(['status' => 'Pending']);
        $canceled = PurchaseReturn::factory()->create(['status' => 'Canceled']);

        $response = $this->postJson(route('admin.por.bulk-complete'), [
            'ids' => [$live->id, $canceled->id],
        ]);

        // Option (b): the valid row completes, the canceled row is skipped
        // and named in the result instead of failing the whole batch. The
        // raw status write this endpoint used to do had no guard at all.
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'counts' => ['completed' => 1, 'already_completed' => 0, 'skipped' => 1],
        ]);
        $this->assertStringContainsString('skipped', $response->json('message'));
        $this->assertDatabaseHas('purchase_returns', ['id' => $live->id, 'status' => 'Completed']);
        $this->assertDatabaseHas('purchase_returns', ['id' => $canceled->id, 'status' => 'Canceled']);
    }

    public function test_it_can_bulk_cancel_purchase_returns()
    {
        $returns = PurchaseReturn::factory()->count(3)->create(['status' => 'Pending']);
        $ids = $returns->pluck('id')->toArray();

        $response = $this->postJson(route('admin.por.bulk-cancel'), ['ids' => $ids]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        foreach ($ids as $id) {
            $this->assertDatabaseHas('purchase_returns', ['id' => $id, 'status' => 'Canceled']);
        }
    }

    public function test_bulk_cancel_with_an_already_canceled_row_is_idempotent()
    {
        $live = PurchaseReturn::factory()->create(['status' => 'Pending']);
        $canceled = PurchaseReturn::factory()->create(['status' => 'Canceled']);

        $response = $this->postJson(route('admin.por.bulk-cancel'), [
            'ids' => [$live->id, $canceled->id],
        ]);

        // Mirror of the sales side: re-selecting a Canceled row is a no-op,
        // not a second stock revert or journal reversal.
        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('purchase_returns', ['id' => $live->id, 'status' => 'Canceled']);
        $this->assertDatabaseHas('purchase_returns', ['id' => $canceled->id, 'status' => 'Canceled']);
        $this->assertEquals(
            0,
            JournalEntry::whereNotNull('reversed_entry_id')->count(),
            'Neither factory row ever posted an entry, so cancel may not mint a reversal.'
        );
    }

    public function test_it_can_show_modal_view()
    {
        $purchaseReturn = PurchaseReturn::factory()->create(['purchase_id' => $this->purchase->id]);

        $response = $this->get(route('admin.por.modal-view', $purchaseReturn->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.layouts.modals.po.pormodals-view');
        $response->assertViewHas('por', $purchaseReturn);
    }
}
