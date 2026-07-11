<?php

namespace Tests\Unit\Services;

use App\Models\Tax;
use App\Services\TaxService;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;

class TaxServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected TaxService $taxService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant(); // Creates $this->tenant and $this->user, and calls actingAs
        $this->user->assignRole('superuser'); // Ensure the user has permissions for services
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
        Tax::factory()->create(['is_active' => true]);
        $taxes = $this->taxService->getAllActive();
        $this->assertNotEmpty($taxes);
        $this->assertInstanceOf(Tax::class, $taxes->first());
    }

    #[Test]
    public function it_can_update_tax_settings()
    {
        $tax = Tax::factory()->create();
        $data = [
            'name'        => 'VAT',
            'rate'        => 20,
            'type'        => 'exclusive',
            'description' => null,
            'is_active'   => true,
            'is_default'  => false,
        ];

        $updated = $this->taxService->update($tax, $data);

        $this->assertDatabaseHas('taxes', [
            'id'        => $tax->id,
            'name'      => 'VAT',
            'rate'      => 20,
            'is_active' => true,
        ]);
        $this->assertInstanceOf(Tax::class, $updated);
    }

    #[Test]
    public function it_can_create_tax_settings_if_none_exist()
    {
        $data = [
            'name'        => 'GST',
            'rate'        => 5,
            'type'        => 'exclusive',
            'description' => null,
            'is_active'   => true,
            'is_default'  => false,
        ];

        $tax = $this->taxService->create($data);

        $this->assertDatabaseHas('taxes', [
            'name'      => 'GST',
            'rate'      => 5,
            'is_active' => true,
        ]);
        $this->assertInstanceOf(Tax::class, $tax);
    }
}
