<?php

namespace Tests\Feature\Api\V1;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $userWithoutPermission;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('view-accounts');
        Permission::findOrCreate('create-accounts');

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'view-accounts',
            'create-accounts',
        ]);

        $this->userWithoutPermission = User::factory()->create();
    }

    #[Test]
    public function unauthenticated_user_cannot_access_accounts_api()
    {
        $this->getJson('/api/v1/accounts')->assertStatus(401);
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
        Account::factory()->count(2)->create();

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