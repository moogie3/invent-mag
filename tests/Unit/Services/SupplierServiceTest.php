<?php

namespace Tests\Unit\Services;

use App\Services\SupplierService;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SupplierServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SupplierService $supplierService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supplierService = new SupplierService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(SupplierService::class, $this->supplierService);
    }
}
