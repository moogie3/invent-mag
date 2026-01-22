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

class AccountingControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');

        // Seed accounts within the tenant context
        $this->seed(AccountSeeder::class);
    }

    #[Test]
    public function it_can_display_the_chart_of_accounts()
    {
        $response = $this->get(route('admin.accounting.chart'));

        $response->assertRedirect(route('admin.accounting.accounts.index'));
    }

    #[Test]
    public function it_can_display_the_general_journal()
    {
        JournalEntry::factory()->count(5)->create();

        $response = $this->get(route('admin.accounting.journal'));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.journal');
        $response->assertViewHas('entries');
    }

    #[Test]
    public function it_can_display_the_general_ledger()
    {
        $response = $this->get(route('admin.accounting.ledger'));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.general-ledger');
        $response->assertViewHas('accounts');
    }

    #[Test]
    public function it_can_filter_the_general_ledger()
    {
        $tenantId = app('currentTenant')->id;
        $account = Account::where('code', '1110-' . $tenantId)->first();
        JournalEntry::factory()->hasTransactions(1, ['account_id' => $account->id])->create();

        $response = $this->get(route('admin.accounting.ledger', [
            'account_id' => $account->id,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.general-ledger');
        $response->assertViewHas('selectedAccount');
        $response->assertViewHas('transactions');
    }

    #[Test]
    public function it_can_display_the_trial_balance()
    {
        // Create some transactions to ensure balances exist
        $tenantId = app('currentTenant')->id;
        $cash = Account::where('code', '1110-' . $tenantId)->first();
        $salesRevenue = Account::where('code', '4100-' . $tenantId)->first();
        $ar = Account::where('code', '1130-' . $tenantId)->first();

        $sale = \App\Models\Sales::factory()->create();
        $payment = \App\Models\Payment::factory()->for($sale, 'paymentable')->create(['amount' => 100]);

        // Simulate a sale
        JournalEntry::factory()->create([
            'date' => now()->subDays(5),
            'description' => 'Sale Test',
            'sourceable_id' => $sale->id,
            'sourceable_type' => \App\Models\Sales::class,
        ])->transactions()->createMany([
            ['account_id' => $ar->id, 'type' => 'debit', 'amount' => 100],
            ['account_id' => $salesRevenue->id, 'type' => 'credit', 'amount' => 100],
        ]);

        // Simulate a payment
        JournalEntry::factory()->create([
            'date' => now()->subDays(3),
            'description' => 'Payment Test',
            'sourceable_id' => $payment->id,
            'sourceable_type' => \App\Models\Payment::class,
        ])->transactions()->createMany([
            ['account_id' => $cash->id, 'type' => 'debit', 'amount' => 100],
            ['account_id' => $ar->id, 'type' => 'credit', 'amount' => 100],
        ]);

        $response = $this->get(route('admin.accounting.trial_balance'));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.trial-balance');
        $response->assertViewHas('reportData');
        $response->assertViewHas('totalDebits');
        $response->assertViewHas('totalCredits');

        // Assert that total debits equal total credits
        $reportData = $response->original->getData()['reportData'];
        $totalDebits = $response->original->getData()['totalDebits'];
        $totalCredits = $response->original->getData()['totalCredits'];

        $this->assertEquals($totalDebits, $totalCredits);
        $this->assertGreaterThan(0, $totalDebits); // Ensure some transactions were processed
    }

    #[Test]
    public function it_can_display_the_accounts_index_page()
    {
        $response = $this->get(route('admin.accounting.accounts.index'));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.accounts.index');
        $response->assertViewHas('accounts');
    }

    #[Test]
    public function it_can_display_the_accounts_create_page()
    {
        $response = $this->get(route('admin.accounting.accounts.create'));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.accounts.create');
        $response->assertViewHas('accounts');
    }

    #[Test]
    public function it_can_store_a_new_account()
    {
        $tenantId = app('currentTenant')->id;
        $parentAccount = Account::where('code', '1000-' . $tenantId)->first(); // Assets
        $this->assertNotNull($parentAccount, 'Parent account with code 1000 not found.');

        $response = $this->post(route('admin.accounting.accounts.store'), [
            'name' => 'Test Account',
            'code' => '9000',
            'type' => 'asset',
            'parent_id' => $parentAccount->id,
            'description' => 'A test account',
        ]);

        $response->assertRedirect(route('admin.accounting.accounts.index'));
        $response->assertSessionHas('success', 'Account created successfully.');
        $this->assertDatabaseHas('accounts', [
            'name' => 'Test Account',
            'code' => '9000',
            'type' => 'asset',
            'parent_id' => $parentAccount->id,
            'level' => $parentAccount->level + 1,
        ]);
    }

    #[Test]
    public function it_validates_account_store_request()
    {
        $response = $this->post(route('admin.accounting.accounts.store'), [
            'name' => '',
            'code' => '',
            'type' => 'invalid_type',
        ]);

        $response->assertSessionHasErrors(['name', 'code', 'type']);
    }

    #[Test]
    public function it_can_display_the_accounts_edit_page()
    {
        $account = Account::factory()->create();

        $response = $this->get(route('admin.accounting.accounts.edit', $account));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.accounts.edit');
        $response->assertViewHas('account', $account);
        $response->assertViewHas('accounts');
    }

    #[Test]
    public function it_can_update_an_account()
    {
        $account = Account::factory()->create(['name' => 'Old Name', 'code' => 'OLD1', 'type' => 'asset']);
        $tenantId = app('currentTenant')->id;
        $parentAccount = Account::where('code', '1000-' . $tenantId)->first(); // Assets

        $response = $this->put(route('admin.accounting.accounts.update', $account), [
            'name' => 'Updated Name',
            'code' => 'UPD1',
            'type' => 'liability',
            'parent_id' => $parentAccount->id,
            'description' => 'Updated description',
        ]);

        $response->assertRedirect(route('admin.accounting.accounts.index'));
        $response->assertSessionHas('success', 'Account updated successfully.');
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'Updated Name',
            'code' => 'UPD1',
            'type' => 'liability',
            'parent_id' => $parentAccount->id,
            'level' => $parentAccount->level + 1,
        ]);
    }

    #[Test]
    public function it_validates_account_update_request()
    {
        $account = Account::factory()->create();

        $response = $this->put(route('admin.accounting.accounts.update', $account), [
            'name' => '',
            'code' => '',
            'type' => 'invalid_type',
        ]);

        $response->assertSessionHasErrors(['name', 'code', 'type']);
    }

    #[Test]
    public function it_can_delete_an_account()
    {
        $account = Account::factory()->create();

        $response = $this->delete(route('admin.accounting.accounts.destroy', $account));

        $response->assertRedirect(route('admin.accounting.accounts.index'));
        $response->assertSessionHas('success', 'Account deleted successfully.');
        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }

    #[Test]
    public function it_cannot_delete_account_with_transactions()
    {
        $tenantId = app('currentTenant')->id;
        $account = Account::where('code', '1110-' . $tenantId)->first();
        JournalEntry::factory()->hasTransactions(1, ['account_id' => $account->id])->create();

        $response = $this->delete(route('admin.accounting.accounts.destroy', $account));

        $response->assertRedirect(route('admin.accounting.accounts.index'));
        $response->assertSessionHas('error', 'Account cannot be deleted because it has transactions or child accounts.');
        $this->assertDatabaseHas('accounts', ['id' => $account->id]);
    }

    #[Test]
    public function it_cannot_delete_account_with_children()
    {
        $tenantId = app('currentTenant')->id;
        $parent = Account::where('code', '1000-' . $tenantId)->first(); // Assets
        $child = Account::factory()->create(['parent_id' => $parent->id]);

        $response = $this->delete(route('admin.accounting.accounts.destroy', $parent));

        $response->assertRedirect(route('admin.accounting.accounts.index'));
        $response->assertSessionHas('error', 'Account cannot be deleted because it has transactions or child accounts.');
        $this->assertDatabaseHas('accounts', ['id' => $parent->id]);
    }
}