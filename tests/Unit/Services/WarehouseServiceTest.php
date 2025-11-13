<?php

namespace Tests\Unit\Services;

use App\Services\WarehouseService;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WarehouseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WarehouseService $warehouseService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->warehouseService = new WarehouseService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(WarehouseService::class, $this->warehouseService);
    }
}
