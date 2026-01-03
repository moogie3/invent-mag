<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\POItem;
use App\Models\Warehouse;
use App\Models\Account;
use Database\Factories\PurchaseFactory;
use Database\Factories\POItemFactory;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Carbon\Carbon;
use App\Services\PurchaseService;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;
use Database\Seeders\AccountSeeder;

class PurchaseControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected Supplier $supplier;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(AccountSeeder::class);
        $this->user->assignRole('superuser');

        // Ensure the authenticated user has the necessary accounting settings
        $this->user->accounting_settings = [
            'cash_account_id' => Account::where('name', 'accounting.accounts.cash.name')->first()->id,
            'accounts_payable_account_id' => Account::where('name', 'accounting.accounts.accounts_payable.name')->first()->id,
            'inventory_account_id' => Account::where('name', 'accounting.accounts.inventory.name')->first()->id,
        ];
        $this->user->save();
        $this->actingAs($this->user);

        Warehouse::factory()->create(['is_main' => true]);
        $this->supplier = Supplier::factory()->create();
        $this->product = Product::factory()->create();
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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
                'total' => 0, // Explicitly set total to 0
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

    public function test_get_purchase_metrics_returns_json()
    {
        $expectedMetrics = [
            'totalPurchases' => 10,
            'totalPaid' => 5,
            'totalUnpaid' => 5,
        ];

        $mockService = Mockery::mock(PurchaseService::class);
        $mockService->shouldReceive('getPurchaseMetrics')
                    ->once()
                    ->andReturn($expectedMetrics);
        $this->app->instance(PurchaseService::class, $mockService);

        $response = $this->get(route('admin.po.metrics'));

        $response->assertStatus(200);
        $response->assertJson($expectedMetrics);
    }

    public function test_get_expiring_soon_purchases_returns_json()
    {
        $expectedPurchases = [
            ['id' => 1, 'name' => 'Purchase 1'],
            ['id' => 2, 'name' => 'Purchase 2'],
        ];

        $mockService = Mockery::mock(PurchaseService::class);
        $mockService->shouldReceive('getExpiringPurchases')
                    ->once()
                    ->andReturn($expectedPurchases);
        $this->app->instance(PurchaseService::class, $mockService);

        $response = $this->get(route('admin.po.expiring-soon'));

        $response->assertStatus(200);
        $response->assertJson($expectedPurchases);
    }

    public function test_modal_view_returns_view()
    {
        $purchase = PurchaseFactory::new()->create();
        $mockService = Mockery::mock(PurchaseService::class);
        $mockService->shouldReceive('getPurchaseForModal')
                    ->once()
                    ->with($purchase->id)
                    ->andReturn($purchase);
        $this->app->instance(PurchaseService::class, $mockService);

        $response = $this->get(route('admin.po.modal-view', $purchase->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.layouts.modals.po.pomodals-view');
        $response->assertViewHas('pos', $purchase);
    }

    public function test_modal_view_handles_not_found()
    {
        $mockService = Mockery::mock(PurchaseService::class);
        $mockService->shouldReceive('getPurchaseForModal')
                    ->once()
                    ->with(999)
                    ->andThrow(new \Illuminate\Database\Eloquent\ModelNotFoundException());
        $this->app->instance(PurchaseService::class, $mockService);

        $response = $this->get(route('admin.po.modal-view', 999));

        $response->assertStatus(404);
    }

    public function test_store_purchase_with_invalid_data_returns_validation_errors()
    {
        $invalidData = [
            'invoice' => '', // required
            'supplier_id' => 999, // not exists
            'order_date' => 'not-a-date', // invalid date
            'due_date' => 'not-a-date', // invalid date
            'products' => 'not-a-json-string', // invalid json
        ];

        $response = $this->post(route('admin.po.store'), $invalidData);

        $response->assertSessionHasErrors(['invoice', 'supplier_id', 'order_date', 'due_date', 'products']);
        $response->assertStatus(302); // Redirect back on validation error
    }

    public function test_update_purchase_with_invalid_data_returns_validation_errors()
    {
        $purchase = PurchaseFactory::new()->create();

        $invalidData = [
            'invoice' => '', // required
            'supplier_id' => 999, // not exists
            'order_date' => 'not-a-date', // invalid date
            'due_date' => 'not-a-date', // invalid date
            'products' => 'not-a-json-string', // invalid json
        ];

        $response = $this->put(route('admin.po.update', $purchase->id), $invalidData);

        $response->assertSessionHasErrors(['invoice', 'supplier_id', 'order_date', 'due_date', 'products']);
        $response->assertStatus(302); // Redirect back on validation error
    }

    public function test_add_payment_with_invalid_data_returns_validation_errors()
    {
        $purchase = PurchaseFactory::new()->create();

        $invalidData = [
            'amount' => 'not-a-number', // invalid number
            'payment_date' => 'not-a-date', // invalid date
            'payment_method' => '', // required
        ];

        $response = $this->post(route('admin.po.add-payment', $purchase->id), $invalidData);

        $response->assertSessionHasErrors(['amount', 'payment_date', 'payment_method']);
        $response->assertStatus(302); // Redirect back on validation error
    }

    public function test_store_purchase_handles_service_level_exception()
    {
        $purchaseData = [
            'invoice' => 'PO-12345',
            'supplier_id' => $this->supplier->id,
            'order_date' => Carbon::now()->format('Y-m-d'),
            'due_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
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

        $mockService = Mockery::mock(PurchaseService::class);
        $mockService->shouldReceive('createPurchase')
                    ->once()
                    ->andThrow(new \Exception('Service exception'));
        $this->app->instance(PurchaseService::class, $mockService);

        $response = $this->post(route('admin.po.store'), $purchaseData);

        $response->assertSessionHasErrors(['error']);
        $response->assertStatus(302);
    }

    public function test_update_purchase_handles_service_level_exception()
    {
        $purchase = PurchaseFactory::new()->create();
        $updateData = [
            'invoice' => $purchase->invoice,
            'supplier_id' => $this->supplier->id,
            'order_date' => Carbon::now()->format('Y-m-d'),
            'due_date' => Carbon::now()->addDays(14)->format('Y-m-d'),
            'products' => json_encode([
                [
                    'product_id' => $this->product->id,
                    'quantity' => 3,
                    'price' => $this->product->price,
                    'total' => $this->product->price * 3,
                    'expiry_date' => Carbon::now()->addDays(60)->format('Y-m-d'),
                ],
            ]),
        ];

        $mockService = Mockery::mock(PurchaseService::class);
        $mockService->shouldReceive('updatePurchase')
                    ->once()
                    ->andThrow(new \Exception('Service exception'));
        $this->app->instance(PurchaseService::class, $mockService);

        $response = $this->put(route('admin.po.update', $purchase->id), $updateData);

        $response->assertSessionHasErrors(['error']);
        $response->assertStatus(302);
    }

    public function test_destroy_purchase_handles_service_level_exception()
    {
        $purchase = PurchaseFactory::new()->create();

        $mockService = Mockery::mock(PurchaseService::class);
        $mockService->shouldReceive('deletePurchase')
                    ->once()
                    ->andThrow(new \Exception('Service exception'));
        $this->app->instance(PurchaseService::class, $mockService);

        $response = $this->delete(route('admin.po.destroy', $purchase->id));

        $response->assertSessionHas('error', 'Error deleting purchase order. Please try again.');
        $response->assertRedirect(route('admin.po'));
    }
}
