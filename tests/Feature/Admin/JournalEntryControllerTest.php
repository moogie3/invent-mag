<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\AccountSeeder;
use Database\Seeders\RoleSeeder;

class JournalEntryControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
        $this->seed(AccountSeeder::class);
    }

    #[Test]
    public function it_can_display_journal_entries_index()
    {
        $response = $this->get(route('admin.accounting.journal-entries.index'));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.journal-entries.index');
        $response->assertViewHas('draftEntries');
        $response->assertViewHas('postedEntries');
        $response->assertViewHas('tab', 'draft');
    }

    #[Test]
    public function it_can_display_journal_entries_with_posted_tab()
    {
        $response = $this->get(route('admin.accounting.journal-entries.index', ['tab' => 'posted']));

        $response->assertOk();
        $response->assertViewHas('draftEntries');
        $response->assertViewHas('postedEntries');
        $response->assertViewHas('tab', 'posted');
    }

    #[Test]
    public function it_can_display_create_form()
    {
        $response = $this->get(route('admin.accounting.journal-entries.create'));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.journal-entries.create');
        $response->assertViewHas('accounts');
    }

    #[Test]
    public function it_can_store_a_new_journal_entry()
    {
        $cashAccount = Account::where('code', 'like', '1110-%')->first();
        $expenseAccount = Account::where('code', 'like', '5000-%')->first();

        $data = [
            'description' => 'Test Journal Entry',
            'date' => now()->toDateString(),
            'transactions' => json_encode([
                ['account_code' => $cashAccount->code, 'type' => 'credit', 'amount' => 1000],
                ['account_code' => $expenseAccount->code, 'type' => 'debit', 'amount' => 1000],
            ]),
            'notes' => 'Test notes',
        ];

        $response = $this->post(route('admin.accounting.journal-entries.store'), $data);

        $response->assertRedirect(route('admin.accounting.journal-entries.index'));
        $this->assertDatabaseHas('journal_entries', [
            'description' => 'Test Journal Entry',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    #[Test]
    public function it_validates_required_fields_on_store()
    {
        $response = $this->post(route('admin.accounting.journal-entries.store'), []);

        $response->assertSessionHasErrors(['description', 'date', 'transactions']);
    }

    #[Test]
    public function it_requires_balanced_transactions()
    {
        $cashAccount = Account::where('code', 'like', '1110-%')->first();
        $expenseAccount = Account::where('code', 'like', '5000-%')->first();

        $data = [
            'description' => 'Unbalanced Entry',
            'date' => now()->toDateString(),
            'transactions' => json_encode([
                ['account_code' => $cashAccount->code, 'type' => 'credit', 'amount' => 1000],
                ['account_code' => $expenseAccount->code, 'type' => 'debit', 'amount' => 500], // Not balanced!
            ]),
        ];

        $response = $this->post(route('admin.accounting.journal-entries.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function it_can_display_journal_entry_details()
    {
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
        ]);

        $response = $this->get(route('admin.accounting.journal-entries.show', $entry));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.journal-entries.show');
        $response->assertViewHas('entry');
        $response->assertViewHas('auditLogs');
    }

    #[Test]
    public function it_can_display_edit_form_for_draft_entries()
    {
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
        ]);

        $response = $this->get(route('admin.accounting.journal-entries.edit', $entry));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.journal-entries.edit');
        $response->assertViewHas('entry');
        $response->assertViewHas('accounts');
    }

    #[Test]
    public function it_cannot_edit_posted_entries()
    {
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'posted',
        ]);

        $response = $this->get(route('admin.accounting.journal-entries.edit', $entry));

        $response->assertRedirect(route('admin.accounting.journal-entries.show', $entry));
        $response->assertSessionHas('error');
    }

    #[Test]
    public function it_can_update_draft_journal_entry()
    {
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
        ]);

        $cashAccount = Account::where('code', 'like', '1110-%')->first();
        $expenseAccount = Account::where('code', 'like', '5000-%')->first();

        $data = [
            'description' => 'Updated Description',
            'date' => now()->toDateString(),
            'transactions' => json_encode([
                ['account_code' => $cashAccount->code, 'type' => 'credit', 'amount' => 2000],
                ['account_code' => $expenseAccount->code, 'type' => 'debit', 'amount' => 2000],
            ]),
        ];

        $response = $this->put(route('admin.accounting.journal-entries.update', $entry), $data);

        $response->assertRedirect(route('admin.accounting.journal-entries.show', $entry));
        $this->assertDatabaseHas('journal_entries', [
            'id' => $entry->id,
            'description' => 'Updated Description',
        ]);
    }

    #[Test]
    public function it_can_post_draft_journal_entry()
    {
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
        ]);

        $response = $this->post(route('admin.accounting.journal-entries.post', $entry));

        $response->assertRedirect();
        $this->assertDatabaseHas('journal_entries', [
            'id' => $entry->id,
            'status' => 'posted',
        ]);
    }

    #[Test]
    public function it_cannot_post_already_posted_entries()
    {
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'posted',
        ]);

        $response = $this->post(route('admin.accounting.journal-entries.post', $entry));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function it_can_void_posted_journal_entry()
    {
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'posted',
        ]);

        $response = $this->post(route('admin.accounting.journal-entries.void', $entry), [
            'reason' => 'Test void reason',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('journal_entries', [
            'id' => $entry->id,
            'status' => 'void',
        ]);
    }

    #[Test]
    public function it_requires_reason_to_void_entry()
    {
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'posted',
        ]);

        $response = $this->post(route('admin.accounting.journal-entries.void', $entry), []);

        $response->assertSessionHasErrors('reason');
    }

    #[Test]
    public function it_can_reverse_journal_entry()
    {
        $cashAccount = Account::where('code', 'like', '1110-%')->first();
        $expenseAccount = Account::where('code', 'like', '5000-%')->first();
        
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'posted',
            'sourceable_type' => null,
            'sourceable_id' => null,
        ]);
        
        // Add transactions to the entry
        $entry->transactions()->create([
            'account_id' => $cashAccount->id,
            'type' => 'debit',
            'amount' => 1000,
        ]);
        $entry->transactions()->create([
            'account_id' => $expenseAccount->id,
            'type' => 'credit',
            'amount' => 1000,
        ]);

        $response = $this->post(route('admin.accounting.journal-entries.reverse', $entry), [
            'notes' => 'Reversal test',
        ]);

        $response->assertRedirect();
        
        // Check that reversal entry was created with reversed_entry_id pointing to original
        $this->assertDatabaseHas('journal_entries', [
            'reversed_entry_id' => $entry->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    #[Test]
    public function it_can_delete_draft_journal_entry()
    {
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
        ]);

        $response = $this->delete(route('admin.accounting.journal-entries.destroy', $entry));

        $response->assertRedirect(route('admin.accounting.journal-entries.index'));
        $this->assertDatabaseMissing('journal_entries', [
            'id' => $entry->id,
        ]);
    }

    #[Test]
    public function it_cannot_delete_posted_entries()
    {
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'posted',
        ]);

        $response = $this->delete(route('admin.accounting.journal-entries.destroy', $entry));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('journal_entries', [
            'id' => $entry->id,
        ]);
    }

    #[Test]
    public function it_can_duplicate_journal_entry()
    {
        $cashAccount = Account::where('code', 'like', '1110-%')->first();
        $expenseAccount = Account::where('code', 'like', '5000-%')->first();
        
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'posted',
            'sourceable_type' => null,
            'sourceable_id' => null,
        ]);
        
        // Add transactions to the entry
        $entry->transactions()->create([
            'account_id' => $cashAccount->id,
            'type' => 'debit',
            'amount' => 1000,
        ]);
        $entry->transactions()->create([
            'account_id' => $expenseAccount->id,
            'type' => 'credit',
            'amount' => 1000,
        ]);

        $response = $this->post(route('admin.accounting.journal-entries.duplicate', $entry));

        $response->assertRedirect();
        
        // Check new entry was created
        $this->assertDatabaseHas('journal_entries', [
            'description' => 'COPY - ' . $entry->description,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    #[Test]
    public function it_can_search_accounts_for_autocomplete()
    {
        $account = Account::first();

        $response = $this->get(route('admin.accounting.journal-entries.search-accounts', [
            'query' => substr($account->name, 0, 3),
        ]));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'accounts' => [
                '*' => ['id', 'code', 'name', 'type'],
            ],
        ]);
    }

    #[Test]
    public function it_can_validate_journal_entry_via_ajax()
    {
        $cashAccount = Account::where('code', 'like', '1110-%')->first();
        $expenseAccount = Account::where('code', 'like', '5000-%')->first();

        $response = $this->post(route('admin.accounting.journal-entries.validate'), [
            'transactions' => json_encode([
                ['account_code' => $cashAccount->code, 'type' => 'credit', 'amount' => 1000],
                ['account_code' => $expenseAccount->code, 'type' => 'debit', 'amount' => 1000],
            ]),
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    #[Test]
    public function it_returns_error_for_unbalanced_transactions_via_ajax()
    {
        $cashAccount = Account::where('code', 'like', '1110-%')->first();
        $expenseAccount = Account::where('code', 'like', '5000-%')->first();

        $response = $this->post(route('admin.accounting.journal-entries.validate'), [
            'transactions' => json_encode([
                ['account_code' => $cashAccount->code, 'type' => 'credit', 'amount' => 1000],
                ['account_code' => $expenseAccount->code, 'type' => 'debit', 'amount' => 500],
            ]),
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }
}
