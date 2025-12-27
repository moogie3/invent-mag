<?php

namespace Tests\Feature\Api\V1;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $userWithoutPermissions;

    public function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'view-transactions',
            'create-transactions',
            'edit-transactions',
            'delete-transactions',
        ];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->user = User::factory()->create();
        $this->user->givePermissionTo($permissions);

        $this->userWithoutPermissions = User::factory()->create();
    }

    #[Test]
    public function test_unauthenticated_user_cannot_access_transaction_endpoints()
    {
        $response = $this->getJson('/api/v1/transactions');
        $response->assertStatus(401);

        $transaction = Transaction::factory()->create();
        $response = $this->getJson('/api/v1/transactions/' . $transaction->id);
        $response->assertStatus(401);
    }

    #[Test]
    public function test_unauthorized_user_cannot_access_transaction_endpoints()
    {
        $transaction = Transaction::factory()->create();

        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->getJson('/api/v1/transactions');
        $response->assertStatus(403);

        $response = $this->actingAs($this->userWithoutPermissions, 'sanctum')->getJson('/api/v1/transactions/' . $transaction->id);
        $response->assertStatus(403);
    }

    #[Test]
    public function test_can_get_all_transactions()
    {
        Transaction::factory()->count(3)->create();
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/transactions');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'journal_entry_id',
                    'account_id',
                    'type',
                    'amount',
                    'created_at',
                    'updated_at',
                ]
            ]
        ]);
    }

    #[Test]
    public function test_can_get_a_transaction()
    {
        $transaction = Transaction::factory()->create();
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/transactions/' . $transaction->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'journal_entry_id',
                'account_id',
                'type',
                'amount',
                'created_at',
                'updated_at',
            ]
        ]);
        $response->assertJsonFragment(['id' => $transaction->id]);
    }
}