<?php

namespace Tests\Unit\Services;

use App\Services\SalesService;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SalesServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SalesService $salesService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salesService = new SalesService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(SalesService::class, $this->salesService);
    }
}
