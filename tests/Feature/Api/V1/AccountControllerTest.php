<?php

namespace Tests\Feature\Api\V1;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_get_all_accounts()
    {
        Account::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/accounts');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

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

    public function test_can_get_an_account()
    {
        $account = Account::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/accounts/' . $account->id);

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => $account->name]);
    }

    public function test_can_update_an_account()
    {
        $account = Account::factory()->create();

        $updateData = [
            'name' => 'Updated Account Name',
        ];

        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/accounts/' . $account->id, $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'name' => 'Updated Account Name']);
    }

    public function test_can_delete_an_account()
    {
        $account = Account::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->deleteJson('/api/v1/accounts/' . $account->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }
}