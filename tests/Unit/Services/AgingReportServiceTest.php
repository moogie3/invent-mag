<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Sales;
use App\Services\AgingReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class AgingReportServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->service = app(AgingReportService::class);
    }

    public function test_generate_aged_receivables_returns_correct_structure()
    {
        $result = $this->service->generateAgedReceivables();

        $this->assertArrayHasKey('current', $result);
        $this->assertArrayHasKey('1-30', $result);
        $this->assertArrayHasKey('31-60', $result);
        $this->assertArrayHasKey('61-90', $result);
        $this->assertArrayHasKey('90+', $result);
    }

    public function test_generate_aged_receivables_categorizes_correctly()
    {
        $customer = Customer::factory()->create(['tenant_id' => app('currentTenant')->id]);

        // Current invoice (due today)
        Sales::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'Unpaid',
            'due_date' => Carbon::now(),
            'tenant_id' => app('currentTenant')->id,
        ]);

        // 15 days overdue
        Sales::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'Unpaid',
            'due_date' => Carbon::now()->subDays(15),
            'tenant_id' => app('currentTenant')->id,
        ]);

        // 45 days overdue
        Sales::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'Unpaid',
            'due_date' => Carbon::now()->subDays(45),
            'tenant_id' => app('currentTenant')->id,
        ]);

        // 100 days overdue
        Sales::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'Unpaid',
            'due_date' => Carbon::now()->subDays(100),
            'tenant_id' => app('currentTenant')->id,
        ]);

        $result = $this->service->generateAgedReceivables();

        $this->assertEquals(1, $result['current']->count());
        $this->assertEquals(1, $result['1-30']->count());
        $this->assertEquals(1, $result['31-60']->count());
        $this->assertEquals(0, $result['61-90']->count());
        $this->assertEquals(1, $result['90+']->count());
    }

    public function test_generate_aged_receivables_excludes_paid_invoices()
    {
        $customer = Customer::factory()->create(['tenant_id' => app('currentTenant')->id]);

        // Paid invoice - should be excluded
        Sales::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'Paid',
            'due_date' => Carbon::now()->subDays(15),
            'tenant_id' => app('currentTenant')->id,
        ]);

        // Unpaid invoice - should be included
        Sales::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'Unpaid',
            'due_date' => Carbon::now()->subDays(15),
            'tenant_id' => app('currentTenant')->id,
        ]);

        $result = $this->service->generateAgedReceivables();

        $this->assertEquals(1, $result['1-30']->count());
    }

    public function test_get_aging_bucket_labels_returns_correct_labels()
    {
        $labels = $this->service->getAgingBucketLabels();

        $this->assertEquals([
            'current' => 'Current',
            '1-30' => '1-30 Days Overdue',
            '31-60' => '31-60 Days Overdue',
            '61-90' => '61-90 Days Overdue',
            '90+' => 'Over 90 Days Overdue',
        ], $labels);
    }
}
