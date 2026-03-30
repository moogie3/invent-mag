<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\POItem;
use App\Models\PurchaseReturnItem;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;
use Database\Seeders\AccountSeeder;

class PurchaseReturnControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected Purchase $purchase;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(AccountSeeder::class);
        $this->user->assignRole('superuser');

        // Ensure the authenticated user has the necessary accounting settings
        $tenantId = app('currentTenant')->id;
        $this->user->accounting_settings = [
            'cash_account_id' => Account::where('code', '1110-' . $tenantId)->first()->id,
            'accounts_payable_account_id' => Account::where('code', '2110-' . $tenantId)->first()->id,
            'inventory_account_id' => Account::where('code', '1140-' . $tenantId)->first()->id,
            'cost_of_goods_sold_account_id' => Account::where('code', '5100-' . $tenantId)->first()->id,
        ];
        $this->user->save();
        $this->actingAs($this->user);

        $this->product = Product::factory()->create();
        $this->purchase = Purchase::factory()->create();
        POItem::factory()->create([
            'po_id' => $this->purchase->id,
            'product_id' => $this->product->id,
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
        // Set up accounting settings with all required accounts
        $tenantId = app('currentTenant')->id;
        
        $cashAccount = \App\Models\Account::where('code', '1110-' . $tenantId)->first();
        $apAccount = \App\Models\Account::where('code', '2110-' . $tenantId)->first();
        $inventoryAccount = \App\Models\Account::where('code', '1140-' . $tenantId)->first();
        $cogsAccount = \App\Models\Account::where('code', '5100-' . $tenantId)->first();
        
        $this->user->accounting_settings = [
            'cash_account_id' => $cashAccount->id ?? null,
            'accounts_payable_account_id' => $apAccount->id ?? null,
            'inventory_account_id' => $inventoryAccount->id ?? null,
            'cost_of_goods_sold_account_id' => $cogsAccount->id ?? null,
        ];
        $this->user->save();

        // Create purchase with proper status
        $this->purchase->update(['status' => 'Paid', 'amount_received' => 100]);

        // Add product to purchase
        \App\Models\POItem::factory()->create([
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
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 50]
            ]),
            'total_amount' => 50,
            'status' => 'Completed', // This is for purchase return status
        ];

        $response = $this->post(route('admin.por.store'), $returnData);

        $response->assertRedirect(route('admin.por.index'));
        $response->assertSessionHas('success', 'Purchase return created successfully.');
        $this->assertDatabaseHas('purchase_returns', ['purchase_id' => $this->purchase->id]);
    }

    public function test_it_can_show_a_purchase_return()
    {
        $purchaseReturn = PurchaseReturn::factory()->create(['purchase_id' => $this->purchase->id]);

        $response = $this->get(route('admin.por.show', $purchaseReturn->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.por.show');
        $response->assertViewHas('por', $purchaseReturn);
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
                ['product_id' => $this->product->id, 'returned_quantity' => 2, 'price' => 150]
            ]),
            'total_amount' => 300,
            'status' => 'Pending',
        ];

        $response = $this->put(route('admin.por.update', $purchaseReturn->id), $updateData);

        $response->assertRedirect(route('admin.por.index'));
        $response->assertSessionHas('success', 'Purchase return updated successfully.');
        $this->assertDatabaseHas('purchase_returns', [
            'id' => $purchaseReturn->id,
            'status' => 'Pending'
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

    public function test_it_can_show_modal_view()
    {
        $purchaseReturn = PurchaseReturn::factory()->create(['purchase_id' => $this->purchase->id]);

        $response = $this->get(route('admin.por.modal-view', $purchaseReturn->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.layouts.modals.po.pormodals-view');
        $response->assertViewHas('por', $purchaseReturn);
    }
}