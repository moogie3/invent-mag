<?php

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Services\TenantSetupService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\RoleSeeder;
use Mockery;

class TenantSetupServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected TenantSetupService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->service = new TenantSetupService();
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_constructor_creates_plan_service()
    {
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('planService');
        $property->setAccessible(true);

        $planService = $property->getValue($this->service);

        $this->assertInstanceOf(\App\Services\PlanService::class, $planService);
    }

    public function test_setup_handles_exception_gracefully()
    {
        $tenant = Tenant::create([
            'name' => 'Test Exception Tenant',
            'domain' => 'test-exception.localhost',
        ]);

        $result = $this->service->setup($tenant);

        $this->assertNull($result);
    }
}
