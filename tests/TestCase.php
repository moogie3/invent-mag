<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Contracts\Console\Kernel;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Explicitly boot the application to ensure service providers are registered
        $app->boot();

        require __DIR__.'/../routes/web.php';

        $app['config']->set('app.key', 'base64:YWJjZGVmZ2hpamtsbW5vcHFyc3R1dnd4eXowMTIzNDU=');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['env'] = 'testing';

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }
}
