<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;
use Database\Seeders\AccountSeeder;
use Mockery;

class AccountServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected AccountService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(AccountSeeder::class);
        $this->service = new AccountService();
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_gets_accounts_with_children()
    {
        $result = $this->service->getAccountsWithChildren();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    public function test_it_gets_all_accounts_ordered()
    {
        $result = $this->service->getAllAccountsOrdered();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    public function test_it_gets_accounts_except_given_id()
    {
        $account = Account::first();
        
        $result = $this->service->getAllAccountsExcept($account->id);

        $this->assertTrue($result->every(fn ($a) => $a->id !== $account->id));
    }

    public function test_it_gets_accounts_grouped_by_type()
    {
        $result = $this->service->getAccountsGroupedByType();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    public function test_it_creates_an_account()
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('superuser');
        $this->actingAs($user);

        $tenantId = app('currentTenant')->id;
        
        $data = [
            'name' => 'Test Account',
            'code' => 'TEST-' . $tenantId . '-001',
            'type' => 'asset',
            'tenant_id' => $tenantId,
        ];

        $result = $this->service->createAccount($data);

        $this->assertInstanceOf(Account::class, $result);
        $this->assertDatabaseHas('accounts', ['name' => 'Test Account']);
    }

    public function test_it_updates_an_account()
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('superuser');
        $this->actingAs($user);

        $account = Account::factory()->create();

        $data = [
            'name' => 'Updated Account',
            'code' => (string) $account->code,
            'type' => $account->type,
        ];

        $result = $this->service->updateAccount($account, $data);

        $this->assertEquals('Updated Account', $result->name);
    }

    public function test_it_prevents_delete_of_account_with_transactions()
    {
        $account = Account::first();
        
        \App\Models\Transaction::factory()->create([
            'account_id' => $account->id,
        ]);

        $result = $this->service->deleteAccount($account);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('transactions', $result['message']);
    }

    public function test_it_prevents_delete_of_account_with_children()
    {
        $parent = Account::factory()->create();
        Account::factory()->create([
            'parent_id' => $parent->id,
        ]);

        $result = $this->service->deleteAccount($parent);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('child', $result['message']);
    }

    public function test_it_deletes_account_successfully()
    {
        $account = Account::factory()->create();

        $result = $this->service->deleteAccount($account);

        $this->assertTrue($result['success']);
        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }

    public function test_it_calculates_account_level()
    {
        $parent = Account::factory()->create(['level' => 0]);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateAccountLevel');
        $method->setAccessible(true);

        $level = $method->invoke($this->service, $parent->id);

        $this->assertEquals(1, $level);
    }

    public function test_it_finds_account_by_id()
    {
        $account = Account::factory()->create();

        $result = $this->service->findById($account->id);

        $this->assertEquals($account->id, $result->id);
    }

    public function test_it_returns_null_for_invalid_id()
    {
        $result = $this->service->findById(99999);

        $this->assertNull($result);
    }

    public function test_it_checks_if_account_can_be_deleted()
    {
        $account = Account::factory()->create();

        $result = $this->service->canDelete($account);

        $this->assertTrue($result);
    }

    public function test_it_returns_false_when_account_has_transactions()
    {
        $account = Account::first();
        
        \App\Models\Transaction::factory()->create([
            'account_id' => $account->id,
        ]);

        $result = $this->service->canDelete($account);

        $this->assertFalse($result);
    }

    public function test_it_returns_false_when_account_has_children()
    {
        $parent = Account::factory()->create();
        Account::factory()->create(['parent_id' => $parent->id]);

        $result = $this->service->canDelete($parent);

        $this->assertFalse($result);
    }
}
