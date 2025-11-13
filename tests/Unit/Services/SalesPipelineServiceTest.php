<?php

namespace Tests\Unit\Services;

use App\Services\SalesPipelineService;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SalesPipelineServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SalesPipelineService $salesPipelineService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salesPipelineService = new SalesPipelineService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(SalesPipelineService::class, $this->salesPipelineService);
    }
}
