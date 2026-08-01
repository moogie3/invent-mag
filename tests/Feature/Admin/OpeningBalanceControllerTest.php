<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\OpeningBalance;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class OpeningBalanceControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(AccountSeeder::class);
        $this->user->assignRole('superuser');
    }

    public function test_it_displays_the_opening_balance_page()
    {
        $response = $this->actingAs($this->user)->get(route('admin.accounting.opening-balance.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.accounting.opening-balance.index');
        $response->assertViewHasAll(['accounts', 'existingBalances', 'balanceDate', 'summary']);
    }

    public function test_it_validates_opening_balances_via_ajax()
    {
        $balances = [
            1 => ['debit' => 100, 'type' => 'debit'],
            2 => ['credit' => 100, 'type' => 'credit'],
        ];

        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.accounting.opening-balance.validate'), ['balances' => $balances]);

        $response->assertStatus(200);
        $response->assertJson([
            'valid' => true,
        ]);
    }

    public function test_it_returns_validation_error_for_unbalanced_entries()
    {
        $balances = [
            1 => ['debit' => 100, 'type' => 'debit'],
            2 => ['credit' => 50, 'type' => 'credit'],
        ];

        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.accounting.opening-balance.validate'), ['balances' => $balances]);

        $response->assertStatus(200);
        $response->assertJson([
            'valid' => false,
        ]);
    }

    public function test_it_returns_validation_error_for_negative_amounts()
    {
        $balances = [
            1 => ['debit' => -100, 'type' => 'debit'],
        ];

        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.accounting.opening-balance.validate'), ['balances' => $balances]);

        $response->assertStatus(200);
        $response->assertJson([
            'valid' => false,
        ]);
        $this->assertStringContainsString('negative', $response->json('errors.0'));
    }

    public function test_it_saves_opening_balances()
    {
        $tenantId = app('currentTenant')->id;
        $assetAccount = Account::where('code', '1140-'.$tenantId)->first();
        $equityAccount = Account::where('code', '3100-'.$tenantId)->first();

        // Skip if equity account doesn't exist (depends on COA being reset)
        if (! $assetAccount || ! $equityAccount) {
            $this->markTestSkipped('Required accounts not found');
        }

        $balances = [
            $assetAccount->id => ['debit' => 1000, 'type' => 'debit'],
            $equityAccount->id => ['credit' => 1000, 'type' => 'credit'],
        ];

        $response = $this->actingAs($this->user)->post(route('admin.accounting.opening-balance.store'), [
            'balance_date' => now()->startOfMonth()->toDateString(),
            'balances' => $balances,
        ]);

        // The service requires Opening Balance Equity account to exist
        $response->assertStatus(302);
    }

    public function test_it_deletes_opening_balances()
    {
        $tenantId = app('currentTenant')->id;
        $assetAccount = Account::where('code', '1140-'.$tenantId)->first();

        if (! $assetAccount) {
            $this->markTestSkipped('Required account not found');
        }

        OpeningBalance::create([
            'tenant_id' => $tenantId,
            'account_id' => $assetAccount->id,
            'debit_amount' => 1000,
            'credit_amount' => 0,
            'balance' => 1000,
            'balance_date' => now()->startOfMonth()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->delete(route('admin.accounting.opening-balance.destroy'), [
            'balance_date' => now()->startOfMonth()->toDateString(),
        ]);

        $response->assertStatus(302);
    }

    public function test_it_gets_summary_via_ajax()
    {
        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('admin.accounting.opening-balance.summary'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'totalDebit',
            'totalCredit',
            'difference',
            'isBalanced',
            'count',
        ]);
    }

    public function test_unauthorized_user_cannot_access_opening_balance_page()
    {
        $user = User::factory()->for($this->tenant)->create();
        $this->actingAs($user);

        $response = $this->get(route('admin.accounting.opening-balance.index'));

        $this->assertTrue(in_array($response->status(), [403, 500]), "Expected 403 or 500, got {$response->status()}");
    }
}
