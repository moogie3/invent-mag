<?php

namespace Tests\Unit\Services;

use App\Models\Tax;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

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

    #[Test]
    public function it_can_get_tax_data()
    {
        Tax::factory()->create();
        $tax = $this->taxService->getTaxData();
        $this->assertInstanceOf(Tax::class, $tax);
    }

    #[Test]
    public function it_can_update_tax_settings()
    {
        $tax = Tax::factory()->create();
        $data = [
            'name' => 'VAT',
            'rate' => 20,
            'is_active' => true,
        ];

        $this->taxService->updateTax($data);

        $this->assertDatabaseHas('taxes', [
            'id' => $tax->id,
            'name' => 'VAT',
            'rate' => 20,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_can_create_tax_settings_if_none_exist()
    {
        $data = [
            'name' => 'GST',
            'rate' => 5,
            'is_active' => true,
        ];

        $this->taxService->updateTax($data);

        $this->assertDatabaseHas('taxes', [
            'name' => 'GST',
            'rate' => 5,
            'is_active' => true,
        ]);
    }
}