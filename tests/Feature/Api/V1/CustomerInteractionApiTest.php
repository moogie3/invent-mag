<?php

namespace Tests\Feature\Api\V1;

use App\Models\Customer;
use App\Models\CustomerInteraction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Auth;

class CustomerInteractionApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    private User $userWithoutPermission;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);

        $permissions = [
            'view-customer-interactions',
            'create-customer-interactions',
            'edit-customer-interactions',
            'delete-customer-interactions',
        ];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
            Permission::findOrCreate($permission, 'api');
        }

        $this->user->assignRole('superuser');
        $this->user->givePermissionTo($permissions);

        $this->userWithoutPermission = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_customer_interactions_api()
    {
        Auth::guard('web')->logout();
        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/customer-interactions')->assertStatus(401);
    }

    #[Test]
    public function user_without_permission_cannot_view_customer_interactions()
    {
        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/customer-interactions')
            ->assertStatus(403);
    }

    #[Test]
    public function authenticated_user_can_list_customer_interactions()
    {
        CustomerInteraction::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
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
            'user_id' => $this->user->id,
        ];

        $this->actingAs($this->user, 'sanctum')
            ->withoutExceptionHandling() // Add this line
            ->postJson('/api/v1/customer-interactions', $payload)
            ->assertStatus(201);

        $this->assertDatabaseHas('customer_interactions', [
            'tenant_id' => $this->tenant->id,
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