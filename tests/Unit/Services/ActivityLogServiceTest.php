<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\User;
use App\Services\ActivityLogService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ActivityLogServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected ActivityLogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->service = new ActivityLogService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_guess_label_returns_name_from_model()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('guessLabel');
        $method->setAccessible(true);

        $product = new Product(['name' => 'Test Product']);
        $result = $method->invoke($this->service, $product);

        $this->assertEquals('Test Product', $result);
    }

    public function test_guess_label_returns_title_from_model()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('guessLabel');
        $method->setAccessible(true);

        $model = new class
        {
            public $title = 'Test Title';
        };
        $result = $method->invoke($this->service, $model);

        $this->assertEquals('Test Title', $result);
    }

    public function test_guess_label_returns_invoice_from_model()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('guessLabel');
        $method->setAccessible(true);

        $model = new class
        {
            public $invoice = 'INV-001';
        };
        $result = $method->invoke($this->service, $model);

        $this->assertEquals('INV-001', $result);
    }

    public function test_guess_label_returns_null_for_empty_model()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('guessLabel');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, null);

        $this->assertNull($result);
    }

    public function test_guess_label_returns_null_when_no_matching_column()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('guessLabel');
        $method->setAccessible(true);

        $model = new \stdClass;
        $model->unknown_field = 'test';
        $result = $method->invoke($this->service, $model);

        $this->assertNull($result);
    }

    public function test_log_export_creates_export_activity()
    {
        $user = User::factory()->create();
        $user->assignRole('superuser');
        $this->actingAs($user);

        $result = $this->service->logExport(
            'Sales',
            'Exported sales report'
        );

        $this->assertNotNull($result);
        $this->assertEquals('export', $result->action);
    }
}
