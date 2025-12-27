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
use Tests\Feature\BaseFeatureTestCase;
use Carbon\Carbon;

class PurchaseReturnControllerTest extends BaseFeatureTestCase
{
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = \Database\Seeders\AccountSeeder::class;
    protected $permissionSeeder = \Database\Seeders\PermissionSeeder::class;

    protected User $user;
    protected Purchase $purchase;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->assignRole('superuser');
        $this->actingAs($this->user);
        
        $inventoryAccount = Account::where('code', '1140')->first();
        $accountsPayableAccount = Account::where('code', '2110')->first();
        $cashAccount = Account::where('code', '1110')->first();

        // Set accounting settings for the user
        $this->user->accounting_settings = [
            'inventory_account_id' => $inventoryAccount->id,
            'accounts_payable_account_id' => $accountsPayableAccount->id,
            'cash_account_id' => $cashAccount->id,
        ];
        $this->user->save();
        $this->user->refresh(); // Refresh the user model to ensure latest settings are loaded

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
        $returnData = [
            'purchase_id' => $this->purchase->id,
            'return_date' => Carbon::now()->format('Y-m-d'),
            'items' => json_encode([
                ['product_id' => $this->product->id, 'returned_quantity' => 1, 'price' => 100]
            ]),
            'total_amount' => 100,
            'status' => 'Completed',
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
