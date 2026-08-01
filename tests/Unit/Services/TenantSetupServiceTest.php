<?php

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Services\PlanService;
use App\Services\TenantSetupService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class TenantSetupServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected TenantSetupService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->service = new TenantSetupService;
    }

    protected function tearDown(): void
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

        $this->assertInstanceOf(PlanService::class, $planService);
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
