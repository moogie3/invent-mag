<?php

namespace Tests\Feature\Api\V1;

use App\Models\Purchase;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Auth;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    private User $userWithoutPermission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->user->assignRole('superuser');
        $this->user->givePermissionTo(['view-payments', 'create-payments']);

        $this->userWithoutPermission = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_payments_api()
    {
        Auth::guard('web')->logout(); // Ensure no user is authenticated
        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/payments')
            ->assertStatus(401);
    }

    #[Test]
    public function user_without_permission_cannot_view_payments()
    {
        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/payments')
            ->assertStatus(403);
    }

    #[Test]
    public function authenticated_user_can_list_payments()
    {
        Payment::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/payments')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'amount', 'payment_date', 'payment_method'],
                ],
            ]);
    }

    #[Test]
    public function authenticated_user_can_create_payment()
    {
        $purchase = Purchase::factory()->create(['tenant_id' => $this->tenant->id]);

        $payload = [
            'amount' => 100,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Cash',
            'notes' => 'Test payment',
            'paymentable_id' => $purchase->id,
            'paymentable_type' => Purchase::class,
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payments', $payload)
            ->assertStatus(201);

        $this->assertDatabaseHas('payments', [
            'tenant_id' => $this->tenant->id,
            'amount' => 100,
            'payment_method' => 'Cash',
            'paymentable_id' => $purchase->id,
            'paymentable_type' => Purchase::class,
        ]);
    }

    #[Test]
    public function payment_api_returns_validation_errors()
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payments', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'amount',
                'payment_date',
                'payment_method',
                'paymentable_id',
                'paymentable_type',
            ]);
    }
}
