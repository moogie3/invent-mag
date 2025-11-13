<?php

namespace Tests\Unit\Services;

use App\Services\PurchaseService;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PurchaseService $purchaseService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->purchaseService = new PurchaseService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(PurchaseService::class, $this->purchaseService);
    }
}
