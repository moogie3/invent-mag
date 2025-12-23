<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Contracts\Console\Kernel;
use App\Models\CurrencySetting;
use App\Helpers\CurrencyHelper;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected bool $seedDatabase = false;

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
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
            \Spatie\Permission\Models\Permission::findOrCreate($permission);
        }

        $user = \App\Models\User::factory()->create();
        if (!empty($permissions)) {
            $user->givePermissionTo($permissions);
        }

        $inventoryAccount = \App\Models\Account::firstOrCreate(['name' => 'Inventory'], ['type' => 'asset']);
        $accountsPayableAccount = \App\Models\Account::firstOrCreate(['name' => 'Accounts Payable'], ['type' => 'liability']);
        $cashAccount = \App\Models\Account::firstOrCreate(['name' => 'Cash'], ['type' => 'asset']);


        $user->accounting_settings = [
            'inventory_account_id' => $inventoryAccount->id,
            'accounts_payable_account_id' => $accountsPayableAccount->id,
            'cash_account_id' => $cashAccount->id,
        ];
        $user->save();

        return $user;
    }

    protected function tearDown(): void
    {
        CurrencyHelper::clearSettingsCache();
        parent::tearDown();
    }
}