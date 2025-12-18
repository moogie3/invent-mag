<?php

namespace Tests\Feature\Api\V1;

use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class JournalEntryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $userWithoutPermission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->setupUser(['view-journal-entries', 'create-journal-entries']);
        $this->userWithoutPermission = $this->setupUser();
    }

    #[Test]
    public function unauthenticated_user_cannot_access_journal_entries_api()
    {
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
        JournalEntry::factory()->count(2)->create();

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
