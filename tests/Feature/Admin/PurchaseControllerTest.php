<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\POItem;
use App\Models\Warehouse;
use Database\Factories\PurchaseFactory;
use Database\Factories\POItemFactory;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Carbon\Carbon;

class PurchaseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Supplier $supplier;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.defaults.guard' => 'web']);

        $this->seed(CurrencySeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('superuser');

        $this->actingAs($this->user);

        Warehouse::factory()->create(['is_main' => true]);
        $this->supplier = Supplier::factory()->create();
        $this->product = Product::factory()->create();
    }

    public function test_it_can_display_the_purchase_index_page()
    {
        PurchaseFactory::new()->count(5)->create([
            'supplier_id' => $this->supplier->id,
        ]);

        $response = $this->get(route('admin.po'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.po.index');
        $response->assertViewHas('pos');
    }

    public function test_it_can_display_the_purchase_create_page()
    {
        $response = $this->get(route('admin.po.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.po.purchase-create');
        $response->assertViewHasAll(['suppliers', 'products']); // Assuming these are passed
    }

    public function test_it_can_store_a_new_purchase()
    {
        $purchaseData = [
            'invoice' => 'PO-' . rand(10000, 99999),
            'supplier_id' => $this->supplier->id,
            'order_date' => Carbon::now()->format('Y-m-d'),
            'due_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'discount_total' => 10,
            'discount_total_type' => 'fixed',
            'products' => json_encode([
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'price' => $this->product->price,
                    'total' => $this->product->price * 5,
                    'expiry_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
                ],
            ]),
        ];

        $response = $this->post(route('admin.po.store'), $purchaseData);

        $response->assertRedirect(route('admin.po'));
        $response->assertSessionHas('success', 'Purchase Order created successfully.');

        $this->assertDatabaseHas('po', [
            'supplier_id' => $this->supplier->id,
            'invoice' => $purchaseData['invoice'],
        ]);

        $this->assertDatabaseHas('po_items', [
            'product_id' => $this->product->id,
            'quantity' => 5,
        ]);
    }

    public function test_it_can_display_the_purchase_edit_page()
    {
        $purchase = PurchaseFactory::new()->create([
            'supplier_id' => $this->supplier->id,
        ]);
        POItemFactory::new()->create(['po_id' => $purchase->id, 'product_id' => $this->product->id]);

        $response = $this->get(route('admin.po.edit', $purchase->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.po.purchase-edit');
        $response->assertViewHas('pos', $purchase);
        $response->assertViewHasAll(['suppliers', 'products']); // Assuming these are passed
    }

    public function test_it_can_update_a_purchase()
    {
        $purchase = PurchaseFactory::new()->create([
            'supplier_id' => $this->supplier->id,
        ]);
        POItemFactory::new()->create(['po_id' => $purchase->id, 'product_id' => $this->product->id]);

        $newProduct = Product::factory()->create();

        $updateData = [
            'invoice' => $purchase->invoice,
            'supplier_id' => $this->supplier->id,
            'order_date' => Carbon::now()->format('Y-m-d'),
            'due_date' => Carbon::now()->addDays(14)->format('Y-m-d'),
            'discount_total' => 5,
            'discount_total_type' => 'percentage',
            'products' => json_encode([
                [
                    'product_id' => $newProduct->id,
                    'quantity' => 3,
                    'price' => $newProduct->price,
                    'total' => $newProduct->price * 3,
                    'expiry_date' => Carbon::now()->addDays(60)->format('Y-m-d'),
                ],
            ]),
        ];

        $response = $this->put(route('admin.po.update', $purchase->id), $updateData);

        $response->assertRedirect(route('admin.po.view', $purchase->id));
        $response->assertSessionHas('success', 'Purchase order updated successfully.');

        $this->assertDatabaseHas('po', [
            'id' => $purchase->id,
            'discount_total' => 5,
            'discount_total_type' => 'percentage',
        ]);

        $this->assertDatabaseHas('po_items', [
            'po_id' => $purchase->id,
            'product_id' => $newProduct->id,
            'quantity' => 3,
        ]);
    }

    public function test_it_can_delete_a_purchase()
    {
        $purchase = PurchaseFactory::new()->create([
            'supplier_id' => $this->supplier->id,
        ]);
        POItemFactory::new()->create(['po_id' => $purchase->id, 'product_id' => $this->product->id]);

        $response = $this->delete(route('admin.po.destroy', $purchase->id));

        $response->assertRedirect(route('admin.po'));
        $response->assertSessionHas('success', 'Purchase order deleted successfully');

        $this->assertDatabaseMissing('po', ['id' => $purchase->id]);
        $this->assertDatabaseMissing('po_items', ['po_id' => $purchase->id]);
    }

    public function test_it_can_display_the_purchase_view_page()
    {
        $purchase = PurchaseFactory::new()->create([
            'supplier_id' => $this->supplier->id,
        ]);
        POItemFactory::new()->create(['po_id' => $purchase->id, 'product_id' => $this->product->id]);

        $response = $this->get(route('admin.po.view', $purchase->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.po.purchase-view');
        $response->assertViewHas('pos', $purchase);
    }

    public function test_it_can_add_payment_to_a_purchase()
    {
        $purchase = PurchaseFactory::new()->create([
            'supplier_id' => $this->supplier->id,
            'total' => 100,
            'status' => 'Unpaid',
        ]);

        $paymentData = [
            'amount' => 50,
            'payment_date' => Carbon::now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'notes' => 'Partial payment',
        ];

        $response = $this->post(route('admin.po.add-payment', $purchase->id), $paymentData);

        $response->assertRedirect(route('admin.po.view', $purchase->id));
        $response->assertSessionHas('success', 'Payment added successfully.');

        $this->assertDatabaseHas('payments', [
            'paymentable_id' => $purchase->id,
            'paymentable_type' => Purchase::class,
            'amount' => 50,
        ]);
    }

    public function test_it_can_bulk_delete_purchases()
    {
        $purchases = PurchaseFactory::new()->count(3)->create([
            'supplier_id' => $this->supplier->id,
        ]);
        foreach ($purchases as $purchase) {
            POItemFactory::new()->create(['po_id' => $purchase->id, 'product_id' => $this->product->id]);
        }

        $idsToDelete = $purchases->pluck('id')->toArray();

        $response = $this->postJson(route('po.bulk-delete'), ['ids' => $idsToDelete]);

        $response->assertOk()
                 ->assertJson([
                     'success' => true,
                     'message' => "Successfully deleted purchase order(s)",
                 ]);

        foreach ($idsToDelete as $id) {
            $this->assertDatabaseMissing('po', ['id' => $id]);
            $this->assertDatabaseMissing('po_items', ['po_id' => $id]);
        }
    }

    public function test_it_can_bulk_mark_paid_purchases()
    {
        $purchases = PurchaseFactory::new()
            ->count(3)
            ->has(POItemFactory::new()->state([
                'price' => 100,
                'quantity' => 1,
                'discount' => 0,
                'total' => 100,
            ]), 'items')
            ->create([
                'supplier_id' => $this->supplier->id,
                'status' => 'Unpaid',
                'discount_total' => 0,
            ]);

        foreach($purchases as $purchase) {
            $purchase->update(['total' => $purchase->grand_total]);
        }

        $idsToMarkPaid = $purchases->pluck('id')->toArray();

        $response = $this->postJson(route('po.bulk-mark-paid'), ['ids' => $idsToMarkPaid]);

        $response->assertOk()
                 ->assertJson([
                     'success' => true,
                     'message' => "Successfully marked 3 purchase order(s) as paid.",
                     'updated_count' => 3,
                 ]);

        foreach ($idsToMarkPaid as $id) {
            $this->assertDatabaseHas('po', [
                'id' => $id,
                'status' => 'Paid',
            ]);
        }
    }
}
