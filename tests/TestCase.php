<?php

namespace Tests;

use App\Helpers\CurrencyHelper;
use App\Models\Account;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected bool $seedDatabase = false;

    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $app['config']->set('app.key', 'base64:YWJjZGVmZ2hpamtsbW5vcHFyc3R1dnd4eXowMTIzNDU=');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['env'] = 'testing';

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        if ($this->seedDatabase) {
            $this->seed();
        }
    }

    protected function setupUser(array $permissions = [])
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $user = User::factory()->create();
        if (! empty($permissions)) {
            $user->givePermissionTo($permissions);
        }

        $inventoryAccount = Account::firstOrCreate(['name' => 'Inventory'], ['type' => 'asset']);
        $accountsPayableAccount = Account::firstOrCreate(['name' => 'Accounts Payable'], ['type' => 'liability']);
        $cashAccount = Account::firstOrCreate(['name' => 'Cash'], ['type' => 'asset']);

        // Accounting settings belong to the tenant, not the user.
        // If a current tenant is set in context, update it; otherwise skip (no-op).
        try {
            $tenant = app('currentTenant');
            $existing = $tenant->accounting_settings ?? [];
            $tenant->accounting_settings = array_merge($existing, [
                'inventory_account_id' => $inventoryAccount->id,
                'accounts_payable_account_id' => $accountsPayableAccount->id,
                'cash_account_id' => $cashAccount->id,
            ]);
            $tenant->save();
        } catch (\Throwable $e) {
            // No current tenant set — skip accounting settings setup
        }

        return $user;
    }

    protected function tearDown(): void
    {
        CurrencyHelper::clearSettingsCache();
        parent::tearDown();
    }
}
