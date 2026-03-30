<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\OpeningBalance;
use App\Services\OpeningBalanceService;
use App\Services\AccountingService;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\AccountSeeder;
use Mockery;

class OpeningBalanceServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected OpeningBalanceService $openingBalanceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(AccountSeeder::class);
        
        $mockAccountingService = Mockery::mock(AccountingService::class);
        $this->openingBalanceService = new OpeningBalanceService($mockAccountingService);
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(OpeningBalanceService::class, $this->openingBalanceService);
    }

    #[Test]
    public function it_gets_accounts_for_setup()
    {
        $accounts = $this->openingBalanceService->getAccountsForSetup();
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $accounts);
        $this->assertTrue($accounts->count() > 0);
    }

    #[Test]
    public function it_validates_balanced_entries()
    {
        $balances = [
            1 => ['debit' => 100, 'type' => 'debit'],
            2 => ['credit' => 100, 'type' => 'credit'],
        ];

        $result = $this->openingBalanceService->validateBalances($balances);

        $this->assertTrue($result['valid']);
        $this->assertEquals(100, $result['totalDebit']);
        $this->assertEquals(100, $result['totalCredit']);
        $this->assertEquals(0, $result['difference']);
    }

    #[Test]
    public function it_validates_unbalanced_entries()
    {
        $balances = [
            1 => ['debit' => 100, 'type' => 'debit'],
            2 => ['credit' => 50, 'type' => 'credit'],
        ];

        $result = $this->openingBalanceService->validateBalances($balances);

        $this->assertFalse($result['valid']);
        $this->assertEquals(50, $result['difference']);
    }

    #[Test]
    public function it_validates_negative_amounts()
    {
        $balances = [
            1 => ['debit' => -100, 'type' => 'debit'],
        ];

        $result = $this->openingBalanceService->validateBalances($balances);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    #[Test]
    public function it_gets_opening_balance_for_account()
    {
        $tenantId = app('currentTenant')->id;
        $account = Account::where('code', '1140-' . $tenantId)->first();
        
        $openingBalance = OpeningBalance::create([
            'tenant_id' => $tenantId,
            'account_id' => $account->id,
            'debit_amount' => 1000,
            'credit_amount' => 0,
            'balance' => 1000,
            'balance_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $result = $this->openingBalanceService->getOpeningBalanceForAccount($account->id);

        $this->assertNotNull($result);
        $this->assertEquals(1000, $result->debit_amount);
    }

    #[Test]
    public function it_gets_all_opening_balances()
    {
        $tenantId = app('currentTenant')->id;
        $account = Account::where('code', '1140-' . $tenantId)->first();
        
        OpeningBalance::create([
            'tenant_id' => $tenantId,
            'account_id' => $account->id,
            'debit_amount' => 1000,
            'credit_amount' => 0,
            'balance' => 1000,
            'balance_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $balances = $this->openingBalanceService->getAllOpeningBalances();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $balances);
        $this->assertTrue($balances->count() > 0);
    }

    #[Test]
    public function it_gets_summary()
    {
        $tenantId = app('currentTenant')->id;
        $account = Account::where('code', '1140-' . $tenantId)->first();

        if (!$account) {
            $this->markTestSkipped('Required account not found');
        }

        // Get the summary without any opening balances
        $summary = $this->openingBalanceService->getSummary(now()->toDateString());

        $this->assertArrayHasKey('totalDebit', $summary);
        $this->assertArrayHasKey('totalCredit', $summary);
        $this->assertArrayHasKey('difference', $summary);
        $this->assertArrayHasKey('isBalanced', $summary);
        $this->assertEquals(0, $summary['totalDebit']);
    }
}
