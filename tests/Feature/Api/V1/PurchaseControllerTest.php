<?php

namespace Tests\Feature\Api\V1;

use App\Models\Purchase;
use App\Models\User;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PurchaseControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $userWithoutPermission;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('view-purchases');
        Permission::findOrCreate('create-purchases');

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'view-purchases',
            'create-purchases',
        ]);

        $this->userWithoutPermission = User::factory()->create();
        $this->supplier = Supplier::factory()->create();

        // Configure accounting settings for the user
        $cashAccount = \App\Models\Account::factory()->create(['name' => 'Cash Account for Purchase Test', 'code' => 'P1001']);
        $accountsReceivableAccount = \App\Models\Account::factory()->create(['name' => 'Accounts Receivable for Purchase Test', 'code' => 'P1002']);
        $salesRevenueAccount = \App\Models\Account::factory()->create(['name' => 'Sales Revenue for Purchase Test', 'code' => 'P1003']);
        $costOfGoodsSoldAccount = \App\Models\Account::factory()->create(['name' => 'Cost of Goods Sold for Purchase Test', 'code' => 'P1004']);
        $inventoryAccount = \App\Models\Account::factory()->create(['name' => 'Inventory for Purchase Test', 'code' => 'P1005']);
        $accountsPayableAccount = \App\Models\Account::factory()->create(['name' => 'Accounts Payable for Purchase Test', 'code' => 'P1006']); // New

        $this->user->accounting_settings = [
            'cash_account_id' => $cashAccount->id,
            'accounts_receivable_account_id' => $accountsReceivableAccount->id,
            'sales_revenue_account_id' => $salesRevenueAccount->id,
            'cost_of_goods_sold_account_id' => $costOfGoodsSoldAccount->id,
            'inventory_account_id' => $inventoryAccount->id,
            'accounts_payable_account_id' => $accountsPayableAccount->id, // New
        ];
        $this->user->save();
    }

    #[Test]
    public function unauthenticated_user_cannot_access_purchase_api()
    {
        $this->getJson('/api/v1/purchases')->assertStatus(401);
        $this->postJson('/api/v1/purchases', [])->assertStatus(401);
    }

    #[Test]
    public function user_without_permission_cannot_view_purchases()
    {
        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/purchases')
            ->assertStatus(403);
    }

    #[Test]
    public function authenticated_user_can_list_purchases()
    {
        Purchase::factory()->count(2)->create([
            'supplier_id' => $this->supplier->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/purchases');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'invoice',
                        'supplier',
                        'order_date',
                        'total',
                        'status',
                    ],
                ],
            ]);
    }

    #[Test]
    public function authenticated_user_can_create_purchase()
    {
        $product = \App\Models\Product::factory()->create();
        $payload = [
            'invoice' => 'API-INV-001',
            'supplier_id' => $this->supplier->id,
            'order_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'products' => json_encode([
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'price' => 100,
                    'discount' => 0,
                    'discount_type' => 'fixed',
                    'expiry_date' => now()->addYear()->toDateString(),
                ]
            ]),
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/purchases', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('po', [
            'invoice' => 'API-INV-001',
            'supplier_id' => $this->supplier->id,
        ]);
    }

    #[Test]
    public function purchase_api_returns_validation_errors()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/purchases', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'invoice',
                'supplier_id',
                'order_date',
                'due_date',
                'products',
            ]);
    }
}
