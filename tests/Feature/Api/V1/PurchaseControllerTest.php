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
        $this->user->accounting_settings = [
            'inventory_account_id' => $inventoryAccount->id,
            'accounts_payable_account_id' => $payableAccount->id,
        ];
        $this->user->save();

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