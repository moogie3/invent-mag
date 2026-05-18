<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Services\ChartOfAccountsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;
use Database\Seeders\AccountSeeder;
use Mockery;

class ChartOfAccountsServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected ChartOfAccountsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(AccountSeeder::class);
        $this->service = new ChartOfAccountsService();
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_gets_accounting_settings()
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('superuser');
        $this->actingAs($user);

        $result = $this->service->getAccountingSettings();

        $this->assertArrayHasKey('accounts', $result);
        $this->assertArrayHasKey('settings', $result);
    }

    public function test_it_updates_accounting_settings()
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('superuser');
        $this->actingAs($user);

        $tenantId = app('currentTenant')->id;
        $accounts = Account::where('code', 'like', '%-' . $tenantId)->take(6)->get();

        $data = [
            'sales_revenue_account_id' => $accounts[0]->id,
            'accounts_receivable_account_id' => $accounts[1]->id,
            'cost_of_goods_sold_account_id' => $accounts[2]->id,
            'inventory_account_id' => $accounts[3]->id,
            'accounts_payable_account_id' => $accounts[4]->id,
            'cash_account_id' => $accounts[5]->id,
        ];

        $result = $this->service->updateAccountingSettings($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('Accounting settings updated successfully.', $result['message']);
    }

    public function test_it_validates_settings_data()
    {
        $result = $this->service->validateSettingsData([]);

        $this->assertArrayHasKey('sales_revenue_account_id', $result);
        $this->assertArrayHasKey('cash_account_id', $result);
    }

    public function test_settings_persists_after_update()
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('superuser');
        $this->actingAs($user);

        $tenantId = app('currentTenant')->id;
        $accounts = Account::where('code', 'like', '%-' . $tenantId)->take(6)->get();

        $data = [
            'sales_revenue_account_id' => $accounts[0]->id,
            'accounts_receivable_account_id' => $accounts[1]->id,
            'cost_of_goods_sold_account_id' => $accounts[2]->id,
            'inventory_account_id' => $accounts[3]->id,
            'accounts_payable_account_id' => $accounts[4]->id,
            'cash_account_id' => $accounts[5]->id,
        ];

        $this->service->updateAccountingSettings($data);

        $tenant = app('currentTenant')->fresh();
        
        $this->assertEquals($data['sales_revenue_account_id'], $tenant->accounting_settings['sales_revenue_account_id']);
    }
}
