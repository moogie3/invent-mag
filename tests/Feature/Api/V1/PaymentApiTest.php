<?php

namespace Tests\Feature\Api\V1;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $userWithoutPermission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->setupUser(['view-payments', 'create-payments']);
        $this->userWithoutPermission = $this->setupUser();
    }

    #[Test]
    public function unauthenticated_user_cannot_access_payments_api()
    {
        $this->getJson('/api/v1/payments')->assertStatus(401);
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
        Payment::factory()->count(2)->create();

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
        $purchase = \App\Models\Purchase::factory()->create();

        $payload = [
            'amount' => 100,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Cash',
            'notes' => 'Test payment',
            'paymentable_id' => $purchase->id,
            'paymentable_type' => \App\Models\Purchase::class,
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payments', $payload)
            ->assertStatus(201);

        $this->assertDatabaseHas('payments', [
            'amount' => 100,
            'payment_method' => 'Cash',
            'paymentable_id' => $purchase->id,
            'paymentable_type' => \App\Models\Purchase::class,
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