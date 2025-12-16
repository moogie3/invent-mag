<?php

namespace Tests\Feature\Api\V1;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Permission;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $permission = Permission::create(['name' => 'view-accounts']);
        $this->user->givePermissionTo($permission);
    }

    #[Test]
    public function test_unauthenticated_user_cannot_get_accounts()
    {
        $response = $this->getJson('/api/v1/accounts');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_can_get_all_accounts()
    {
        Account::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/accounts');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    #[Test]
    public function test_can_create_an_account()
    {
        $accountData = [
            'name' => 'Test Account',
            'code' => '1234',
            'type' => 'asset',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/accounts', $accountData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('accounts', $accountData);
    }

    #[Test]
    public function test_store_fails_with_invalid_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/accounts', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'code', 'type']);
    }

    #[Test]
    public function test_can_get_an_account()
    {
        $account = Account::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/accounts/' . $account->id);

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => $account->name]);
    }

    #[Test]
    public function test_can_update_an_account()
    {
        $account = Account::factory()->create();

        $updateData = [
            'name' => 'Updated Account Name',
            'type' => 'asset',
            'code' => '1234'
        ];

        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/accounts/' . $account->id, $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'name' => 'Updated Account Name']);
    }

    #[Test]
    public function test_can_delete_an_account()
    {
        $account = Account::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->deleteJson('/api/v1/accounts/' . $account->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }
}