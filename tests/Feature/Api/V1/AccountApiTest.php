<?php

namespace Tests\Feature\Api\V1;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;
use Database\Seeders\AccountSeeder;
use Illuminate\Support\Facades\Auth;

class AccountApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected User $userWithoutPermission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant(); // Creates $this->tenant and $this->user, and calls actingAs
        $this->seed(RoleSeeder::class);
        $this->seed(AccountSeeder::class); // Ensure initial accounts are present
        $this->user->assignRole('superuser');

        $this->userWithoutPermission = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_accounts_api()
    {
        Auth::guard('web')->logout();
        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/accounts')
            ->assertStatus(401);
    }

    #[Test]
    public function user_without_permission_cannot_view_accounts()
    {
        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/accounts')
            ->assertStatus(403);
    }

    #[Test]
    public function authenticated_user_can_list_accounts()
    {
        Account::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/accounts')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'code', 'type'],
                ],
            ]);
    }

    #[Test]
    public function authenticated_user_can_create_account()
    {
        $payload = [
            'name' => 'Cash',
            'code' => '1001',
            'type' => 'asset',
            'is_active' => true,
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/accounts', $payload)
            ->assertStatus(201);

        $this->assertDatabaseHas('accounts', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Cash',
            'code' => '1001',
        ]);
    }

    #[Test]
    public function account_api_returns_validation_errors()
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/accounts', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'code',
                'type',
            ]);
    }
}
