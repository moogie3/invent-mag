<?php

namespace Tests\Feature\Api\V1;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $userWithoutPermission;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'view-customers',
            'create-customers',
            'edit-customers',
            'delete-customers',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $this->user = User::factory()->create();
        $this->user->givePermissionTo($permissions);

        $this->userWithoutPermission = User::factory()->create();
    }

    #[Test]
    public function unauthenticated_user_cannot_access_customer_api()
    {
        $this->getJson('/api/v1/customers')->assertStatus(401);
        $this->postJson('/api/v1/customers')->assertStatus(401);

        $customer = Customer::factory()->create();

        $this->putJson("/api/v1/customers/{$customer->id}")->assertStatus(401);
        $this->deleteJson("/api/v1/customers/{$customer->id}")->assertStatus(401);
    }

    #[Test]
    public function user_without_permission_is_forbidden_from_customer_api()
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/customers')
            ->assertStatus(403);

        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->postJson('/api/v1/customers', [])
            ->assertStatus(403);

        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->putJson("/api/v1/customers/{$customer->id}", [])
            ->assertStatus(403);

        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->deleteJson("/api/v1/customers/{$customer->id}")
            ->assertStatus(403);
    }

    #[Test]
    public function authenticated_user_can_list_customers()
    {
        Customer::factory()->count(3)->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/customers')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function authenticated_user_can_view_a_customer()
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/customers/{$customer->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $customer->id]);
    }

    #[Test]
    public function authenticated_user_can_create_a_customer()
    {
        $payload = [
            'name' => 'API Customer',
            'address' => '123 Main St',
            'phone_number' => '123456789',
            'email' => 'customer@example.com',
            'payment_terms' => 'Net 30',
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/customers', $payload)
            ->assertStatus(201);

        $this->assertDatabaseHas('customers', [
            'email' => 'customer@example.com',
        ]);
    }

    #[Test]
    public function authenticated_user_can_update_a_customer()
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/customers/{$customer->id}", [
                'name' => 'Updated Customer',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Customer',
        ]);
    }

    #[Test]
    public function authenticated_user_can_delete_a_customer()
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/customers/{$customer->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);
    }
}