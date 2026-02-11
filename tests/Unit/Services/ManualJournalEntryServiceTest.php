<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\ManualJournalEntryService;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\AccountSeeder;
use Database\Seeders\RoleSeeder;
use Carbon\Carbon;
use Mockery;

class ManualJournalEntryServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected ManualJournalEntryService $service;
    protected $accountingServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(AccountSeeder::class);
        
        $this->accountingServiceMock = Mockery::mock(AccountingService::class);
        $this->service = new ManualJournalEntryService($this->accountingServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_create_a_manual_journal_entry()
    {
        $cashAccount = Account::where('code', 'like', '1110-%')->first();
        $expenseAccount = Account::where('code', 'like', '5000-%')->first();

        $data = [
            'description' => 'Test Entry',
            'date' => Carbon::now()->toDateString(),
            'transactions' => [
                ['account_code' => $cashAccount->code, 'type' => 'credit', 'amount' => 1000],
                ['account_code' => $expenseAccount->code, 'type' => 'debit', 'amount' => 1000],
            ],
            'notes' => 'Test notes',
            'auto_post' => false,
        ];

        $this->accountingServiceMock
            ->shouldReceive('createManualJournalEntry')
            ->once()
            ->andReturn(new JournalEntry(['id' => 1, 'status' => 'draft']));

        $result = $this->service->create($data);

        $this->assertInstanceOf(JournalEntry::class, $result);
    }

    #[Test]
    public function it_validates_description_is_required()
    {
        $this->expectException(\Exception::class);

        $data = [
            'description' => '',
            'date' => Carbon::now()->toDateString(),
            'transactions' => [],
        ];

        $this->service->create($data);
    }

    #[Test]
    public function it_validates_date_is_required()
    {
        $this->expectException(\Exception::class);

        $data = [
            'description' => 'Test',
            'date' => '',
            'transactions' => [],
        ];

        $this->service->create($data);
    }

    #[Test]
    public function it_validates_transactions_is_array()
    {
        $this->expectException(\Exception::class);

        $data = [
            'description' => 'Test',
            'date' => Carbon::now()->toDateString(),
            'transactions' => 'not an array',
        ];

        $this->service->create($data);
    }

    #[Test]
    public function it_validates_minimum_two_transactions()
    {
        $cashAccount = Account::where('code', 'like', '1110-%')->first();

        $data = [
            'description' => 'Test',
            'date' => Carbon::now()->toDateString(),
            'transactions' => [
                ['account_code' => $cashAccount->code, 'type' => 'debit', 'amount' => 1000],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->service->create($data);
    }

    #[Test]
    public function it_validates_debits_equal_credits()
    {
        $cashAccount = Account::where('code', 'like', '1110-%')->first();
        $expenseAccount = Account::where('code', 'like', '5000-%')->first();

        $data = [
            'description' => 'Test',
            'date' => Carbon::now()->toDateString(),
            'transactions' => [
                ['account_code' => $cashAccount->code, 'type' => 'credit', 'amount' => 1000],
                ['account_code' => $expenseAccount->code, 'type' => 'debit', 'amount' => 500],
            ],
        ];

        // The mock should throw an exception when createManualJournalEntry is called with unbalanced transactions
        $this->accountingServiceMock
            ->shouldReceive('createManualJournalEntry')
            ->once()
            ->andThrow(new \Exception('Debits (500) do not equal credits (1000)'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Debits (500) do not equal credits (1000)');
        
        $this->service->create($data);
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
            'description' => 'Updated Entry',
            'date' => Carbon::now()->toDateString(),
            'transactions' => [
                ['account_code' => $cashAccount->code, 'type' => 'credit', 'amount' => 2000],
                ['account_code' => $expenseAccount->code, 'type' => 'debit', 'amount' => 2000],
            ],
        ];

        $this->accountingServiceMock
            ->shouldReceive('updateDraftJournalEntry')
            ->once()
            ->andReturn($entry);

        $result = $this->service->update($entry, $data);

        $this->assertInstanceOf(JournalEntry::class, $result);
    }

    #[Test]
    public function it_cannot_update_posted_entries()
    {
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'posted',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only draft entries can be updated');

        $this->service->update($entry, []);
    }

    #[Test]
    public function it_can_post_journal_entry()
    {
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
        ]);

        $this->accountingServiceMock
            ->shouldReceive('postJournalEntry')
            ->once()
            ->with($entry)
            ->andReturn($entry);

        $result = $this->service->post($entry);

        $this->assertInstanceOf(JournalEntry::class, $result);
    }

    #[Test]
    public function it_can_void_journal_entry()
    {
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'posted',
        ]);

        $this->accountingServiceMock
            ->shouldReceive('voidJournalEntry')
            ->once()
            ->with($entry, 'Test void reason')
            ->andReturn($entry);

        $result = $this->service->void($entry, 'Test void reason');

        $this->assertInstanceOf(JournalEntry::class, $result);
    }

    #[Test]
    public function it_can_reverse_journal_entry()
    {
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'posted',
        ]);

        $reversalEntry = new JournalEntry(['id' => 999]);

        $this->accountingServiceMock
            ->shouldReceive('reverseJournalEntry')
            ->once()
            ->with($entry, 'Reversal notes')
            ->andReturn($reversalEntry);

        $result = $this->service->reverse($entry, 'Reversal notes');

        $this->assertInstanceOf(JournalEntry::class, $result);
    }

    #[Test]
    public function it_can_delete_draft_entry()
    {
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
        ]);

        $this->accountingServiceMock
            ->shouldReceive('deleteDraftJournalEntry')
            ->once()
            ->with($entry)
            ->andReturn(true);

        $result = $this->service->delete($entry);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_can_get_draft_entries()
    {
        JournalEntry::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
            'sourceable_type' => null,
            'sourceable_id' => null,
        ]);

        JournalEntry::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'posted',
            'sourceable_type' => null,
            'sourceable_id' => null,
        ]);

        $filters = [];
        $result = $this->service->getDraftEntries($filters);

        $this->assertEquals(3, $result->total());
    }

    #[Test]
    public function it_can_get_posted_entries()
    {
        JournalEntry::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
            'sourceable_type' => null,
            'sourceable_id' => null,
        ]);

        JournalEntry::factory()->count(4)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'posted',
            'sourceable_type' => null,
            'sourceable_id' => null,
        ]);

        $filters = [];
        $result = $this->service->getPostedEntries($filters);

        $this->assertEquals(4, $result->total());
    }

    #[Test]
    public function it_can_filter_entries_by_date_range()
    {
        JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
            'sourceable_type' => null,
            'sourceable_id' => null,
            'date' => Carbon::now()->subDays(10),
        ]);

        JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
            'sourceable_type' => null,
            'sourceable_id' => null,
            'date' => Carbon::now(),
        ]);

        $filters = [
            'start_date' => Carbon::now()->subDays(5)->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
        ];

        $result = $this->service->getDraftEntries($filters);

        $this->assertEquals(1, $result->total());
    }

    #[Test]
    public function it_can_filter_entries_by_search_term()
    {
        JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
            'sourceable_type' => null,
            'sourceable_id' => null,
            'description' => 'Monthly rent payment',
        ]);

        JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
            'sourceable_type' => null,
            'sourceable_id' => null,
            'description' => 'Office supplies',
        ]);

        $filters = ['search' => 'rent'];
        $result = $this->service->getDraftEntries($filters);

        $this->assertEquals(1, $result->total());
    }

    #[Test]
    public function it_can_get_entry_details()
    {
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'posted',
        ]);

        $result = $this->service->getEntryDetails($entry);

        $this->assertInstanceOf(JournalEntry::class, $result);
        $this->assertTrue($result->relationLoaded('transactions'));
    }

    #[Test]
    public function it_can_duplicate_journal_entry()
    {
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'posted',
            'description' => 'Original Entry',
        ]);

        $duplicatedEntry = new JournalEntry(['id' => 999, 'description' => 'COPY - Original Entry']);

        $this->accountingServiceMock
            ->shouldReceive('createManualJournalEntry')
            ->once()
            ->andReturn($duplicatedEntry);

        $result = $this->service->duplicate($entry, false);

        $this->assertInstanceOf(JournalEntry::class, $result);
    }

    #[Test]
    public function it_can_get_available_accounts_for_manual_entry()
    {
        Account::where('tenant_id', $this->tenant->id)
            ->update(['allow_manual_entry' => true]);

        $result = $this->service->getAvailableAccounts();

        $this->assertGreaterThan(0, $result->count());
    }

    #[Test]
    public function it_can_search_accounts()
    {
        $account = Account::first();
        $searchTerm = substr($account->name, 0, 3);

        $result = $this->service->searchAccounts($searchTerm);

        $this->assertGreaterThan(0, $result->count());
    }

    #[Test]
    public function it_prepares_transactions_correctly()
    {
        $transactions = [
            ['account_code' => '1110-1', 'type' => 'debit', 'amount' => 1000],
            ['account_code' => '5000-1', 'type' => 'credit', 'amount' => 1000],
        ];

        // Use reflection to call protected method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('prepareTransactions');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, $transactions);

        $this->assertCount(2, $result);
        $this->assertEquals('1110-1', $result[0]['account_code']);
        $this->assertEquals('debit', $result[0]['type']);
        $this->assertEquals(1000, $result[0]['amount']);
    }
}
