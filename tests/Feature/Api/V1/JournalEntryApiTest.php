<?php

namespace Tests\Feature\Api\V1;

use App\Models\JournalEntry;
use App\Models\User;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;

class JournalEntryApiTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    private User $userWithoutPermission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);

        Account::factory()->create(['name' => 'Cash', 'tenant_id' => $this->tenant->id]);
        Account::factory()->create(['name' => 'Inventory', 'tenant_id' => $this->tenant->id]);

        $permissions = ['view-journal-entries', 'create-journal-entries'];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
            Permission::findOrCreate($permission, 'api');
        }
        $this->user->givePermissionTo($permissions);
        
        $this->userWithoutPermission = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_journal_entries_api()
    {
        Auth::guard('web')->logout();
        $this->getJson('/api/v1/journal-entries')->assertStatus(401);
    }

    #[Test]
    public function user_without_permission_cannot_view_journal_entries()
    {
        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/journal-entries')
            ->assertStatus(403);
    }

    #[Test]
    public function authenticated_user_can_list_journal_entries()
    {
        JournalEntry::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/journal-entries')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'date', 'description', 'total_debit', 'total_credit'],
                ],
            ]);
    }

    #[Test]
    public function authenticated_user_can_create_journal_entry()
    {
        $payload = [
            'date' => now()->toDateString(),
            'description' => 'Test journal entry',
            'transactions' => json_encode([
                ['account_name' => 'Cash', 'type' => 'debit', 'amount' => 100],
                ['account_name' => 'Inventory', 'type' => 'credit', 'amount' => 100],
            ]),
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/journal-entries', $payload)
            ->assertStatus(201);

        $this->assertDatabaseHas('journal_entries', [
            'tenant_id' => $this->tenant->id,
            'description' => 'Test journal entry',
        ]);
    }


    #[Test]
    public function journal_entry_api_returns_validation_errors()
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/journal-entries', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'date',
                'description',
                'transactions',
            ]);
    }
}