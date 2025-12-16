<?php

namespace Tests\Feature\Api\V1;

use App\Models\Purchase;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Categories;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use App\Services\AccountingService;
use Mockery;
use Tests\TestCase;

class PurchaseControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $userWithoutPermissions;
    private Supplier $supplier;
    private Product $product;
    private $accountingServiceMock;

    public function setUp(): void
    {
        parent::setUp();

        // Mock the AccountingService and bind it to the container
        $this->accountingServiceMock = Mockery::mock(AccountingService::class);
        $this->app->instance(AccountingService::class, $this->accountingServiceMock);


        // Create permissions
        $permissions = [
            'view-purchases',
            'create-purchases',
            'edit-purchases',
            'delete-purchases',
        ];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Create a user with permissions
        $this->user = User::factory()->create();
        $this->user->givePermissionTo($permissions);

        // Seed accounts and set accounting settings for the user
        $this->seed(\Database\Seeders\AccountSeeder::class);
        $inventoryAccount = \App\Models\Account::where('name', 'accounting.accounts.inventory.name')->first();
        $payableAccount = \App\Models\Account::where('name', 'accounting.accounts.accounts_payable.name')->first();
        $cashAccount = \App\Models\Account::where('name', 'accounting.accounts.cash.name')->first();
        $this->user->accounting_settings = [
            'inventory_account_id' => $inventoryAccount->id,
            'accounts_payable_account_id' => $payableAccount->id,
            'cash_account_id' => $cashAccount->id,
        ];
        $this->user->save();
        \Log::info('User accounting settings: ' . json_encode($this->user->accounting_settings));

        // Create a user without permissions
        $this->userWithoutPermissions = User::factory()->create();



        // Create related data needed for purchase creation
        $this->supplier = Supplier::factory()->create();
        $category = Categories::factory()->create();
        $unit = Unit::factory()->create();
        $warehouse = Warehouse::factory()->create(['is_main' => true]);
        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'units_id' => $unit->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $warehouse->id,
            'price' => 50, // Purchase price for product
        ]);
    }



    #[Test]
    public function test_unauthenticated_user_cannot_access_purchase_endpoints()
    {
        $response = $this->getJson('/api/v1/purchases');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/purchases', []);
        $response->assertStatus(401);

        $purchase = Purchase::factory()->create();
        $response = $this->putJson('/api/v1/purchases/' . $purchase->id, []);
        $response->assertStatus(401);

        $response = $this->deleteJson('/api/v1/purchases/' . $purchase->id);
        $response->assertStatus(401);
    }

    #[Test]
    public function test_unauthorized_user_cannot_view_purchases()
    {
        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->getJson('/api/v1/purchases');
        $response->assertStatus(403);
    }

    #[Test]
    public function test_unauthorized_user_cannot_create_purchases()
    {
        $purchaseData = $this->getValidPurchaseData(); // Get valid data for store
        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->postJson('/api/v1/purchases', $purchaseData);
        $response->assertStatus(403);
    }

    #[Test]
    public function test_unauthorized_user_cannot_update_purchases()
    {
        $purchase = Purchase::factory()->create();
        $updateData = ['invoice' => 'Updated Invoice'];
        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->putJson('/api/v1/purchases/' . $purchase->id, $updateData);
        $response->assertStatus(403);
    }

    #[Test]
    public function test_unauthorized_user_cannot_delete_purchases()
    {
        $purchase = Purchase::factory()->create();
        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->deleteJson('/api/v1/purchases/' . $purchase->id);
        $response->assertStatus(403);
    }

    #[Test]
    public function test_can_get_all_purchases()
    {
        Purchase::factory()->count(3)->create(['supplier_id' => $this->supplier->id]);
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/purchases');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'invoice',
                    'supplier',
                    'order_date',
                    'total',
                    'status',
                ]
            ]
        ]);
    }
    
    #[Test]
    public function test_can_get_a_purchase()
    {
        $purchase = Purchase::factory()->create(['supplier_id' => $this->supplier->id]);
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/purchases/' . $purchase->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'invoice',
                'supplier',
                'order_date',
                'due_date',
                'total',
                'status',
            ]
        ]);
        $response->assertJsonFragment(['id' => $purchase->id]);
    }

    #[Test]
    public function test_store_fails_with_invalid_data()
    {
        // Missing required fields
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/purchases', []); 
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['invoice', 'supplier_id', 'order_date', 'due_date', 'products']);
    }

    #[Test]
    public function test_can_create_a_purchase()
    {
        $purchaseData = $this->getValidPurchaseData();

        // Expect the service method to be called once
        $this->accountingServiceMock->shouldReceive('createJournalEntry')->once();

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/purchases', $purchaseData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('po', [
            'invoice' => $purchaseData['invoice'],
            'supplier_id' => $purchaseData['supplier_id'],
        ]);
    }

    #[Test]
    public function test_update_fails_with_invalid_data()
    {
        $purchase = Purchase::factory()->create(['supplier_id' => $this->supplier->id]);
        // Missing required fields that are also sometimes in the request
        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/purchases/' . $purchase->id, [
            'invoice' => '', // Required string
            'products' => 'invalid_json', // Must be valid JSON
        ]); 
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['invoice', 'products']);
    }

    #[Test]
    public function test_can_update_a_purchase()
    {
        $purchase = Purchase::factory()->create(['supplier_id' => $this->supplier->id]);
        $updateData = [
            'invoice' => 'UPDATED-INV-001',
            'supplier_id' => $this->supplier->id, // Must be present if others are
            'order_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(), // Added missing due_date
            'products' => json_encode([
                ['product_id' => $this->product->id, 'quantity' => 5, 'price' => 60],
            ]),
        ];
    
        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/purchases/' . $purchase->id, $updateData);
    
        $response->assertStatus(200);
        $this->assertDatabaseHas('po', [
            'id' => $purchase->id, 
            'invoice' => 'UPDATED-INV-001',
        ]);
    }

    #[Test]
    public function test_can_delete_a_purchase()
    {
        $purchase = Purchase::factory()->create(['supplier_id' => $this->supplier->id]);
        $response = $this->actingAs($this->user, 'sanctum')->deleteJson('/api/v1/purchases/' . $purchase->id);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('po', ['id' => $purchase->id]);
    }

    #[Test]
    public function test_unauthorized_user_cannot_bulk_delete_purchases()
    {
        $purchases = Purchase::factory()->count(3)->create(['supplier_id' => $this->supplier->id]);
        $purchaseIds = $purchases->pluck('id')->toArray();

        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->postJson('/api/v1/purchases/bulk-delete', ['ids' => $purchaseIds]);
        $response->assertStatus(403);
    }

    #[Test]
    public function test_can_bulk_delete_purchases()
    {
        $purchases = Purchase::factory()->count(3)->create(['supplier_id' => $this->supplier->id]);
        $purchaseIds = $purchases->pluck('id')->toArray();

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/purchases/bulk-delete', ['ids' => $purchaseIds]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        foreach ($purchaseIds as $id) {
            $this->assertDatabaseMissing('po', ['id' => $id]);
        }
    }

    #[Test]
    public function test_unauthorized_user_cannot_bulk_mark_purchases_as_paid()
    {
        $purchases = Purchase::factory()->count(3)->create(['supplier_id' => $this->supplier->id, 'status' => 'Unpaid', 'total' => 100]);
        $purchaseIds = $purchases->pluck('id')->toArray();

        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->putJson('/api/v1/purchases/bulk-mark-paid', ['ids' => $purchaseIds]);
        $response->assertStatus(403);
    }

    #[Test]
    public function test_can_bulk_mark_purchases_as_paid()
    {
        $purchases = Purchase::factory()->count(3)->create(['supplier_id' => $this->supplier->id, 'status' => 'Unpaid', 'total' => 100]);
        $purchaseIds = $purchases->pluck('id')->toArray();

        // Expect the service method to be called once for each purchase marked paid
        $this->accountingServiceMock->shouldReceive('createJournalEntry')->times($purchases->count());


        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/purchases/bulk-mark-paid', ['ids' => $purchaseIds]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'updated_count' => 3]);

        foreach ($purchaseIds as $id) {
            $purchase = Purchase::find($id);
            \Log::info("Before addPayment for PO {$purchase->id}: total={$purchase->total}, total_paid={$purchase->total_paid}, balance={$purchase->balance}");
            $this->assertDatabaseHas('po', ['id' => $id, 'status' => 'Paid']);
        }
    }

    #[Test]
    public function test_unauthorized_user_cannot_get_purchase_metrics()
    {
        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->getJson('/api/v1/purchases/metrics');
        $response->assertStatus(403);
    }

    #[Test]
    public function test_can_get_purchase_metrics()
    {
        Purchase::factory()->create(['supplier_id' => $this->supplier->id, 'total' => 100, 'status' => 'Paid']);
        Purchase::factory()->create(['supplier_id' => $this->supplier->id, 'total' => 200, 'status' => 'Unpaid']);
        Purchase::factory()->create(['supplier_id' => $this->supplier->id, 'total' => 300, 'status' => 'Paid']);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/purchases/metrics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'totalinvoice',
            'inCount',
            'inCountamount',
            'outCount',
            'outCountamount',
            'totalMonthly',
            'paymentMonthly',
        ]);

        $response->assertJson([
            'totalinvoice' => 3,
        ]);
    }

    #[Test]
    public function test_unauthorized_user_cannot_get_expiring_soon_purchases()
    {
        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->getJson('/api/v1/purchases/expiring-soon');
        $response->assertStatus(403);
    }

    #[Test]
    public function test_can_get_expiring_soon_purchases()
    {
        // Create a purchase expiring soon
        $expiringPurchase = Purchase::factory()->create([
            'supplier_id' => $this->supplier->id,
            'due_date' => now()->addDays(5),
            'status' => 'Unpaid',
        ]);

        // Create a purchase not expiring soon
        $notExpiringPurchase = Purchase::factory()->create([
            'supplier_id' => $this->supplier->id,
            'due_date' => now()->addDays(100),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/purchases/expiring-soon');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $expiringPurchase->id]);
        $response->assertJsonMissing(['id' => $notExpiringPurchase->id]);
    }

    #[Test]
    public function test_unauthorized_user_cannot_add_payment_to_purchase()
    {
        $purchase = Purchase::factory()->create(['supplier_id' => $this->supplier->id]);
        $paymentData = [
            'amount' => 50,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Cash',
        ];
        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->postJson('/api/v1/purchases/' . $purchase->id . '/payment', $paymentData);
        $response->assertStatus(403);
    }

    #[Test]
    public function test_add_payment_to_purchase_fails_with_invalid_data()
    {
        $purchase = Purchase::factory()->create(['supplier_id' => $this->supplier->id]);
        $paymentData = [
            'amount' => 'invalid',
            'payment_date' => 'not-a-date',
            'payment_method' => '',
        ];
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/purchases/' . $purchase->id . '/payment', $paymentData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount', 'payment_date', 'payment_method']);
    }

    #[Test]
    public function test_can_add_payment_to_purchase()
    {
        $purchase = Purchase::factory()->create(['supplier_id' => $this->supplier->id, 'total' => 100]);
        $paymentData = [
            'amount' => 50,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Cash',
            'notes' => 'Test payment',
        ];

        // Expect the service method to be called once
        $this->accountingServiceMock->shouldReceive('createJournalEntry')->once();

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/purchases/' . $purchase->id . '/payment', $paymentData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $purchase->refresh();
        $this->assertEquals(50, $purchase->total_paid);
    }

    private function getValidPurchaseData(): array
    {
        return [
            'invoice' => 'TEST-INV-001',
            'supplier_id' => $this->supplier->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(), // Added missing required due_date
            'products' => json_encode([
                ['product_id' => $this->product->id, 'quantity' => 10, 'price' => 50],
            ]),
            // Removed user_id, payment_type, total, status as they are not validated in StorePurchaseRequest
        ];
    }
}