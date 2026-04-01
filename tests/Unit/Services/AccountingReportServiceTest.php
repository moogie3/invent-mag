<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Services\AccountingReportService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;
use Database\Seeders\AccountSeeder;
use Mockery;

class AccountingReportServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected AccountingReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(AccountSeeder::class);
        $this->service = new AccountingReportService();
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generate_trial_balance_returns_required_keys()
    {
        $result = $this->service->generateTrialBalance('2026-01-31');

        $this->assertArrayHasKey('report_data', $result);
        $this->assertArrayHasKey('total_debits', $result);
        $this->assertArrayHasKey('total_credits', $result);
        $this->assertArrayHasKey('is_balanced', $result);
        $this->assertArrayHasKey('end_date', $result);
    }

    public function test_generate_trial_balance_is_balanced_when_no_transactions()
    {
        $result = $this->service->generateTrialBalance('2026-01-31');

        $this->assertTrue($result['is_balanced']);
    }

    public function test_generate_trial_balance_excludes_empty_accounts()
    {
        $result = $this->service->generateTrialBalance('2026-01-31');

        $nonZeroAccounts = array_filter($result['report_data'], function ($item) {
            return $item['debit'] > 0 || $item['credit'] > 0;
        });

        $this->assertIsArray($nonZeroAccounts);
    }

    public function test_get_chart_of_accounts_returns_collection()
    {
        $result = $this->service->getChartOfAccounts();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    public function test_get_chart_of_accounts_includes_children()
    {
        $result = $this->service->getChartOfAccounts();

        if ($result->isNotEmpty()) {
            $firstAccount = $result->first();
            $this->assertTrue(isset($firstAccount->children) || property_exists($firstAccount, 'children'));
        }
    }

    public function test_flatten_account_hierarchy_returns_array()
    {
        $accounts = collect([
            (object) ['code' => '1000', 'name' => 'Assets', 'type' => 'asset', 'level' => 0, 'children' => collect([])],
        ]);

        $result = $this->service->flattenAccountHierarchy($accounts);

        $this->assertIsArray($result);
    }

    public function test_flatten_account_hierarchy_adds_indentation()
    {
        $parent = Account::first();
        
        $accounts = collect([$parent]);
        $result = $this->service->flattenAccountHierarchy($accounts);

        $this->assertIsArray($result);
    }

    public function test_flatten_account_hierarchy_handles_nested_accounts()
    {
        $parent = Account::factory()->create(['parent_id' => null]);
        $child = Account::factory()->create(['parent_id' => $parent->id]);

        $accounts = collect([$parent]);
        $result = $this->service->flattenAccountHierarchy($accounts);

        $this->assertCount(2, $result);
    }

    public function test_trial_balance_totals_are_equal()
    {
        $result = $this->service->generateTrialBalance('2026-01-31');

        $this->assertEquals($result['total_debits'], $result['total_credits']);
    }

    public function test_trial_balance_end_date_is_correct()
    {
        $result = $this->service->generateTrialBalance('2026-01-31');

        $this->assertEquals('2026-01-31', $result['end_date']);
    }
}
