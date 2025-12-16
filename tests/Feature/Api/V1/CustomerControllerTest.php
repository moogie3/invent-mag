<?php

namespace Tests\Feature\Api\V1;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $userWithoutPermissions;

    public function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $permissions = [
            'view-customers',
            'create-customers',
            'edit-customers',
            'delete-customers',
        ];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Create a user with permissions
        $this->user = User::factory()->create();
        $this->user->givePermissionTo($permissions);

        // Create a user without permissions
        $this->userWithoutPermissions = User::factory()->create();
    }

    #[Test]
    public function test_unauthenticated_user_cannot_access_customer_endpoints()
    {
        $response = $this->getJson('/api/v1/customers');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/customers', []);
        $response->assertStatus(401);

        $customer = Customer::factory()->create();
        $response = $this->putJson('/api/v1/customers/' . $customer->id, []);
        $response->assertStatus(401);

        $response = $this->deleteJson('/api/v1/customers/' . $customer->id);
        $response->assertStatus(401);
    }

    #[Test]
    public function test_unauthorized_user_cannot_view_customers()
    {
        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->getJson('/api/v1/customers');
        $response->assertStatus(403);
    }

    #[Test]
    public function test_unauthorized_user_cannot_create_customers()
    {
        $customerData = ['name' => 'New Customer'];
        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->postJson('/api/v1/customers', $customerData);
        $response->assertStatus(403);
    }

    #[Test]
    public function test_unauthorized_user_cannot_update_customers()
    {
        $customer = Customer::factory()->create();
        $updateData = ['name' => 'Updated Customer'];
        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->putJson('/api/v1/customers/' . $customer->id, $updateData);
        $response->assertStatus(403);
    }

    #[Test]
    public function test_unauthorized_user_cannot_delete_customers()
    {
        $customer = Customer::factory()->create();
        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->deleteJson('/api/v1/customers/' . $customer->id);
        $response->assertStatus(403);
    }

    #[Test]
    public function test_can_get_all_customers()
    {
        Customer::factory()->count(3)->create();
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/customers');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'phone_number',
                    // Note: 'image' is here because the ProductResource also has it and it's a common field.
                    // If CustomerResource returns 'image_url', this should be changed.
                    'image',
                ]
            ]
        ]);
    }
    
    #[Test]
    public function test_can_get_a_customer()
    {
        $customer = Customer::factory()->create();
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/customers/' . $customer->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'phone_number',
                'address',
                'image', // Assuming 'image' key from resource
                'payment_terms',
            ]
        ]);
        $response->assertJsonFragment(['id' => $customer->id]);
    }

    #[Test]
    public function test_store_fails_with_invalid_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/customers', ['name' => '']); // Missing required fields
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'address', 'phone_number', 'payment_terms']);
    }

    #[Test]
    public function test_store_fails_with_duplicate_email()
    {
        Customer::factory()->create(['email' => 'existing@example.com']);
        $customerData = [
            'name' => 'Test Customer',
            'address' => '123 Test St',
            'phone_number' => '123-456-7890',
            'email' => 'existing@example.com',
            'payment_terms' => 'Net 30',
        ];
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/customers', $customerData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function test_can_create_a_customer()
    {
        $customerData = [
            'name' => 'New API Customer',
            'address' => '123 Main St',
            'phone_number' => '123-456-7890',
            'email' => 'api.customer@example.com',
            'payment_terms' => 'Net 30',
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/customers', $customerData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('customers', [
            'name' => 'New API Customer',
            'email' => 'api.customer@example.com',
        ]);
    }

    #[Test]
    public function test_update_fails_with_invalid_data()
    {
        $customer = Customer::factory()->create();
        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/customers/' . $customer->id, [
            'name' => '',
            'address' => '',
            'phone_number' => '',
            'payment_terms' => '',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'address', 'phone_number', 'payment_terms']);
    }

    #[Test]
    public function test_update_fails_with_duplicate_email()
    {
        Customer::factory()->create(['email' => 'other@example.com']);
        $customer = Customer::factory()->create();
        $updateData = [
            'name' => 'Updated Customer',
            'address' => $customer->address, // Assuming address is required for update
            'phone_number' => $customer->phone_number,
            'email' => 'other@example.com', // Duplicate email
            'payment_terms' => $customer->payment_terms,
        ];
        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/customers/' . $customer->id, $updateData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function test_can_update_a_customer()
    {
        $customer = Customer::factory()->create();
        $updateData = [
            'name' => 'Updated API Customer',
            'address' => '456 Oak Ave',
            'phone_number' => '987-654-3210',
            'email' => 'updated.api.customer@example.com',
            'payment_terms' => 'Net 60',
        ];
    
        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/customers/' . $customer->id, $updateData);
    
        $response->assertStatus(200);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id, 
            'name' => 'Updated API Customer',
            'email' => 'updated.api.customer@example.com',
        ]);
    }

    #[Test]
    public function test_can_delete_a_customer()
    {
        $customer = Customer::factory()->create();
        $response = $this->actingAs($this->user, 'sanctum')->deleteJson('/api/v1/customers/' . $customer->id);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }
}