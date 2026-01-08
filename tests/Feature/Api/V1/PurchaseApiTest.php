<?php

namespace Tests\Feature\Api\V1;

use App\Models\Purchase;
use App\Models\User;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Illuminate\Support\Facades\Auth;
use App\Models\Account;

class PurchaseApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    private User $userWithoutPermission;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();

        $permissions = ['view-purchases', 'create-purchases'];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
            Permission::findOrCreate($permission, 'api');
        }

        $this->user->givePermissionTo($permissions);
        $this->userWithoutPermission = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->id]);

        $cashAccount = Account::factory()->create(['name' => 'Cash Account for Purchase Test', 'code' => 'P1001', 'tenant_id' => $this->tenant->id]);
        $accountsReceivableAccount = Account::factory()->create(['name' => 'Accounts Receivable for Purchase Test', 'code' => 'P1002', 'tenant_id' => $this->tenant->id]);
        $salesRevenueAccount = Account::factory()->create(['name' => 'Sales Revenue for Purchase Test', 'code' => 'P1003', 'tenant_id' => $this->tenant->id]);
        $costOfGoodsSoldAccount = Account::factory()->create(['name' => 'Cost of Goods Sold for Purchase Test', 'code' => 'P1004', 'tenant_id' => $this->tenant->id]);
        $inventoryAccount = Account::factory()->create(['name' => 'Inventory for Purchase Test', 'code' => 'P1005', 'tenant_id' => $this->tenant->id]);
        $accountsPayableAccount = Account::factory()->create(['name' => 'Accounts Payable for Purchase Test', 'code' => 'P1006', 'tenant_id' => $this->tenant->id]);

        $this->user->accounting_settings = [
            'cash_account_id' => $cashAccount->id,
            'accounts_receivable_account_id' => $accountsReceivableAccount->id,
            'sales_revenue_account_id' => $salesRevenueAccount->id,
            'cost_of_goods_sold_account_id' => $costOfGoodsSoldAccount->id,
            'inventory_account_id' => $inventoryAccount->id,
            'accounts_payable_account_id' => $accountsPayableAccount->id,
        ];
        $this->user->save();
    }

    #[Test]
    public function unauthenticated_user_cannot_access_purchase_api()
    {
        Auth::guard('web')->logout();
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
            'tenant_id' => $this->tenant->id,
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
        $product = \App\Models\Product::factory()->create(['tenant_id' => $this->tenant->id]);
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
            'tenant_id' => $this->tenant->id,
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
