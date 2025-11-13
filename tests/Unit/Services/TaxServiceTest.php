<?php

namespace Tests\Unit\Services;

use App\Services\TaxService;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaxServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TaxService $taxService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taxService = new TaxService();
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(TaxService::class, $this->taxService);
    }
}
