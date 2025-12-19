<?php

namespace Tests\Feature\Api\V1;

use App\Models\Customer;
use App\Models\CustomerInteraction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CustomerInteractionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $userWithoutPermission;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->setupUser(['view-customer-interactions', 'create-customer-interactions']);
        $this->userWithoutPermission = $this->setupUser();
        $this->customer = Customer::factory()->create();
    }

    #[Test]
    public function unauthenticated_user_cannot_access_customer_interactions_api()
    {
        $this->getJson('/api/v1/customer-interactions')->assertStatus(401);
    }

    #[Test]
    public function user_without_permission_cannot_view_customer_interactions()
    {
        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/customer-interactions')
            ->assertStatus(403);
    }

    #[Test]
    public function authenticated_user_can_list_customer_interactions()
    {
        CustomerInteraction::factory()->count(2)->create([
            'customer_id' => $this->customer->id,
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/customer-interactions')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'interaction_date', 'notes'],
                ],
            ]);
    }

    #[Test]
    public function authenticated_user_can_create_customer_interaction()
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'type' => 'call',
            'interaction_date' => now()->toDateString(),
            'notes' => 'Test interaction',
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/customer-interactions', $payload)
            ->assertStatus(201);

        $this->assertDatabaseHas('customer_interactions', [
            'notes' => 'Test interaction',
        ]);
    }

    #[Test]
    public function customer_interaction_api_returns_validation_errors()
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/customer-interactions', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'customer_id',
                'type',
                'interaction_date',
            ]);
    }
}