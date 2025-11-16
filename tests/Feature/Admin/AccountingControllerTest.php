<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountSeeder::class);
        $permission = Permission::create(['name' => 'view-accounting']);
        $role = Role::create(['name' => 'accountant'])->givePermissionTo($permission);
        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_display_the_chart_of_accounts()
    {
        $response = $this->get(route('admin.accounting.chart'));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.chart-of-accounts');
        $response->assertViewHas('accounts');
    }

    /** @test */
    public function it_can_display_the_general_journal()
    {
        JournalEntry::factory()->count(5)->create();

        $response = $this->get(route('admin.accounting.journal'));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.journal');
        $response->assertViewHas('entries');
    }

    /** @test */
    public function it_can_display_the_general_ledger()
    {
        $response = $this->get(route('admin.accounting.ledger'));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.general-ledger');
        $response->assertViewHas('accounts');
    }

    /** @test */
    public function it_can_filter_the_general_ledger()
    {
        $account = Account::where('name', 'Cash')->first();
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

    /** @test */
    public function it_can_display_the_trial_balance()
    {
        // Create some transactions to ensure balances exist
        $cash = Account::where('name', 'Cash')->first();
        $salesRevenue = Account::where('name', 'Sales Revenue')->first();
        $ar = Account::where('name', 'Accounts Receivable')->first();

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
}
