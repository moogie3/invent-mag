<?php

namespace Tests\Unit\Models;

use App\Models\FakturPajak;
use App\Models\Sales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class TenantCanDisableIndonesianJurisdictionTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
    }

    #[Test]
    public function it_returns_safe_when_no_ppn_sales_or_faktur_exist(): void
    {
        $result = $this->tenant->canDisableIndonesianJurisdiction();

        $this->assertTrue($result['safe']);
        $this->assertSame(0, $result['count']);
    }

    #[Test]
    public function it_blocks_when_draft_faktur_pajak_exists(): void
    {
        $sale = Sales::factory()->create(['ppn_enabled' => true, 'ppn_amount' => 11000]);
        $this->createFaktur($sale, 'draft');

        $result = $this->tenant->canDisableIndonesianJurisdiction();

        $this->assertFalse($result['safe']);
        $this->assertSame(1, $result['count']);
    }

    #[Test]
    public function it_blocks_when_unissued_ppn_sale_exists(): void
    {
        Sales::factory()->create(['ppn_enabled' => true, 'ppn_amount' => 11000]);

        $result = $this->tenant->canDisableIndonesianJurisdiction();

        $this->assertFalse($result['safe']);
        $this->assertSame(1, $result['count']);
    }

    #[Test]
    public function it_is_safe_when_ppn_sale_already_has_faktur_pajak(): void
    {
        $sale = Sales::factory()->create(['ppn_enabled' => true, 'ppn_amount' => 11000]);
        $this->createFaktur($sale, 'submitted');

        $result = $this->tenant->canDisableIndonesianJurisdiction();

        $this->assertTrue($result['safe']);
        $this->assertSame(0, $result['count']);
    }

    #[Test]
    public function it_ignores_non_ppn_sales_without_faktur(): void
    {
        Sales::factory()->create(['ppn_enabled' => false, 'ppn_amount' => 0]);

        $result = $this->tenant->canDisableIndonesianJurisdiction();

        $this->assertTrue($result['safe']);
        $this->assertSame(0, $result['count']);
    }

    private function createFaktur(Sales $sale, string $status): FakturPajak
    {
        return FakturPajak::create([
            'tenant_id' => $this->tenant->id,
            'sales_id' => $sale->id,
            'nsfp' => '010.000-26.00000001',
            'tanggal_faktur' => now()->toDateString(),
            'masa_pajak' => now()->month,
            'tahun_pajak' => now()->year,
            'npwp_penjual' => '01.000.000.0-000.000',
            'nama_penjual' => 'Test Vendor',
            'alamat_penjual' => 'Test Address',
            'npwp_pembeli' => null,
            'nama_pembeli' => 'Test Buyer',
            'alamat_pembeli' => 'Test Address',
            'dpp' => 100000,
            'ppn' => 11000,
            'ppnbm' => 0,
            'ppn_rate' => 11.00,
            'status' => $status,
        ]);
    }
}
