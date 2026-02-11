<?php

namespace Tests\Unit\Services;

use App\Models\AccountingAuditLog;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Auth;

class AccountingAuditServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected AccountingAuditService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
        
        $this->service = new AccountingAuditService();
        Auth::login($this->user);
    }

    #[Test]
    public function it_can_log_journal_entry_creation()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'description' => 'Test Entry',
        ]);

        $transactions = [
            ['account_code' => '1110-1', 'type' => 'debit', 'amount' => 1000],
            ['account_code' => '5000-1', 'type' => 'credit', 'amount' => 1000],
        ];

        $log = $this->service->logCreate($journalEntry, $transactions);

        $this->assertInstanceOf(AccountingAuditLog::class, $log);
        $this->assertEquals(AccountingAuditLog::ACTION_CREATE, $log->action);
        $this->assertEquals($journalEntry->id, $log->entity_id);
        $this->assertEquals(JournalEntry::class, $log->entity_type);
        $this->assertEquals($this->user->id, $log->user_id);
        $this->assertNotNull($log->new_values);
    }

    #[Test]
    public function it_stores_transaction_details_in_log()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $transactions = [
            ['account_code' => '1110-1', 'type' => 'debit', 'amount' => 1000],
        ];

        $log = $this->service->logCreate($journalEntry, $transactions);

        $newValues = $log->new_values;
        $this->assertArrayHasKey('transactions', $newValues);
        $this->assertCount(1, $newValues['transactions']);
    }

    #[Test]
    public function it_can_log_journal_entry_update()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'description' => 'Original Description',
        ]);

        $oldValues = $journalEntry->toArray();
        
        $journalEntry->description = 'Updated Description';
        $journalEntry->save();

        $newTransactions = [
            ['account_code' => '1110-1', 'type' => 'debit', 'amount' => 2000],
        ];

        $log = $this->service->logUpdate($journalEntry, $oldValues, $newTransactions);

        $this->assertInstanceOf(AccountingAuditLog::class, $log);
        $this->assertEquals(AccountingAuditLog::ACTION_UPDATE, $log->action);
        $this->assertNotNull($log->old_values);
        $this->assertNotNull($log->new_values);
    }

    #[Test]
    public function it_stores_old_and_new_values_on_update()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'description' => 'Original',
        ]);

        $oldValues = ['description' => 'Original', 'amount' => 1000];

        $log = $this->service->logUpdate($journalEntry, $oldValues, []);

        $storedOldValues = $log->old_values;
        $storedNewValues = $log->new_values;

        $this->assertEquals('Original', $storedOldValues['description']);
        $this->assertNotNull($storedNewValues);
    }

    #[Test]
    public function it_can_log_post_action()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
        ]);

        $oldValues = $journalEntry->toArray();

        $log = $this->service->logAction(
            $journalEntry,
            AccountingAuditLog::ACTION_POST,
            $oldValues
        );

        $this->assertInstanceOf(AccountingAuditLog::class, $log);
        $this->assertEquals(AccountingAuditLog::ACTION_POST, $log->action);
        $this->assertStringContainsString('posted', strtolower($log->description));
    }

    #[Test]
    public function it_can_log_void_action()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'posted',
        ]);

        $oldValues = $journalEntry->toArray();
        $additionalData = ['reason' => 'Test void reason'];

        $log = $this->service->logAction(
            $journalEntry,
            AccountingAuditLog::ACTION_VOID,
            $oldValues,
            $additionalData
        );

        $this->assertEquals(AccountingAuditLog::ACTION_VOID, $log->action);
        $newValues = $log->new_values;
        $this->assertArrayHasKey('reason', $newValues);
    }

    #[Test]
    public function it_can_log_reverse_action()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $oldValues = $journalEntry->toArray();

        $log = $this->service->logAction(
            $journalEntry,
            AccountingAuditLog::ACTION_REVERSE,
            $oldValues
        );

        $this->assertEquals(AccountingAuditLog::ACTION_REVERSE, $log->action);
    }

    #[Test]
    public function it_can_log_delete_action()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $oldValues = $journalEntry->toArray();

        $log = $this->service->logAction(
            $journalEntry,
            AccountingAuditLog::ACTION_DELETE,
            $oldValues
        );

        $this->assertEquals(AccountingAuditLog::ACTION_DELETE, $log->action);
    }

    #[Test]
    public function it_can_log_approve_action()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $log = $this->service->logAction(
            $journalEntry,
            AccountingAuditLog::ACTION_APPROVE,
            null
        );

        $this->assertEquals(AccountingAuditLog::ACTION_APPROVE, $log->action);
    }

    #[Test]
    public function it_stores_user_information_in_log()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $log = $this->service->logCreate($journalEntry, []);

        $this->assertEquals($this->user->id, $log->user_id);
        $this->assertEquals($this->user->name, $log->user_name);
    }

    #[Test]
    public function it_stores_ip_address_and_user_agent()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $log = $this->service->logCreate($journalEntry, []);

        $this->assertNotNull($log->ip_address);
        $this->assertNotNull($log->user_agent);
    }

    #[Test]
    public function it_can_retrieve_audit_logs_for_specific_entry()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create multiple logs for this entry
        $this->service->logCreate($journalEntry, []);
        $this->service->logAction($journalEntry, AccountingAuditLog::ACTION_POST, null);
        $this->service->logAction($journalEntry, AccountingAuditLog::ACTION_VOID, null);

        $logs = $this->service->getEntryAuditLogs($journalEntry);

        $this->assertCount(3, $logs);
        $this->assertEquals($journalEntry->id, $logs->first()->entity_id);
    }

    #[Test]
    public function it_returns_logs_ordered_by_date_descending()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->service->logCreate($journalEntry, []);
        sleep(1);
        $this->service->logAction($journalEntry, AccountingAuditLog::ACTION_POST, null);

        $logs = $this->service->getEntryAuditLogs($journalEntry);

        $this->assertTrue($logs->first()->created_at >= $logs->last()->created_at);
    }

    #[Test]
    public function it_can_get_tenant_audit_logs_with_filters()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->service->logCreate($journalEntry, []);
        $this->service->logAction($journalEntry, AccountingAuditLog::ACTION_POST, null);
        $this->service->logAction($journalEntry, AccountingAuditLog::ACTION_VOID, null);

        $filters = ['action' => AccountingAuditLog::ACTION_CREATE];
        $logs = $this->service->getTenantAuditLogs($filters);

        $this->assertTrue($logs->items()[0]->action === AccountingAuditLog::ACTION_CREATE);
    }

    #[Test]
    public function it_can_filter_logs_by_date_range()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->service->logCreate($journalEntry, []);

        $filters = [
            'start_date' => now()->subDays(1)->toDateString(),
            'end_date' => now()->addDays(1)->toDateString(),
        ];

        $logs = $this->service->getTenantAuditLogs($filters);

        $this->assertGreaterThan(0, $logs->total());
    }

    #[Test]
    public function it_can_filter_logs_by_user()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->service->logCreate($journalEntry, []);

        $filters = ['user_id' => $this->user->id];
        $logs = $this->service->getTenantAuditLogs($filters);

        $this->assertTrue($logs->items()[0]->user_id === $this->user->id);
    }

    #[Test]
    public function it_can_get_audit_summary_for_dashboard()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->service->logCreate($journalEntry, []);
        $this->service->logAction($journalEntry, AccountingAuditLog::ACTION_POST, null);
        $this->service->logAction($journalEntry, AccountingAuditLog::ACTION_VOID, null);

        $summary = $this->service->getAuditSummary(30);

        $this->assertArrayHasKey('action_summary', $summary);
        $this->assertArrayHasKey('recent_logs', $summary);
        $this->assertArrayHasKey('period', $summary);
        $this->assertEquals(30, $summary['period']);
    }

    #[Test]
    public function it_summarizes_actions_correctly()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create 2 creates, 1 post, 1 void
        $this->service->logCreate($journalEntry, []);
        $this->service->logAction($journalEntry, AccountingAuditLog::ACTION_POST, null);
        $this->service->logAction($journalEntry, AccountingAuditLog::ACTION_VOID, null);

        $summary = $this->service->getAuditSummary(30);

        $this->assertArrayHasKey(AccountingAuditLog::ACTION_CREATE, $summary['action_summary']);
        $this->assertArrayHasKey(AccountingAuditLog::ACTION_POST, $summary['action_summary']);
        $this->assertArrayHasKey(AccountingAuditLog::ACTION_VOID, $summary['action_summary']);
    }

    #[Test]
    public function it_includes_description_in_log()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'description' => 'Test Journal Entry Description',
        ]);

        $log = $this->service->logCreate($journalEntry, []);

        $this->assertStringContainsString('Test Journal Entry Description', $log->description);
        $this->assertStringContainsString('Created journal entry', $log->description);
    }

    #[Test]
    public function it_can_extract_journal_entry_data_correctly()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'description' => 'Test Entry',
            'date' => now(),
        ]);

        $transactions = [
            ['account_code' => '1110-1', 'type' => 'debit', 'amount' => 1000],
        ];

        $log = $this->service->logCreate($journalEntry, $transactions);

        $newValues = $log->new_values;
        
        $this->assertArrayHasKey('id', $newValues);
        $this->assertArrayHasKey('date', $newValues);
        $this->assertArrayHasKey('description', $newValues);
        $this->assertArrayHasKey('status', $newValues);
        $this->assertArrayHasKey('total_debit', $newValues);
        $this->assertArrayHasKey('total_credit', $newValues);
        $this->assertArrayHasKey('transactions', $newValues);
    }

    #[Test]
    public function it_logs_reversal_with_reversal_note()
    {
        $journalEntry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $log = $this->service->logCreate($journalEntry, [], 'Reversed entry #123');

        $this->assertStringContainsString('Reversed entry #123', $log->description);
    }
}
