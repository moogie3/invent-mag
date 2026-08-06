<?php

namespace Tests\Unit\Services;

use App\Models\FakturPajak;
use App\Models\Purchase;
use App\Models\Sales;
use App\Services\AccountingService;
use App\Services\EFakturService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class EFakturServiceSptMasukanTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected EFakturService $efakturService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);

        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        $this->efakturService = new EFakturService(Mockery::mock(AccountingService::class));
        Auth::setUser($this->user);
    }

    private function createFakturPajak(int $masa, int $tahun, float $dpp, float $ppn, string $status = 'approved', int $seq = 1): FakturPajak
    {
        $sale = Sales::factory()->create(['order_date' => "{$tahun}-{$masa}-15"]);

        return FakturPajak::create([
            'sales_id' => $sale->id,
            'nsfp' => sprintf('010.000-26.%08d', $seq),
            'tanggal_faktur' => "{$tahun}-{$masa}-15",
            'masa_pajak' => $masa,
            'tahun_pajak' => $tahun,
            'npwp_penjual' => '01.234.567.8-901.000',
            'nama_penjual' => 'Test Tenant',
            'alamat_penjual' => 'Jakarta',
            'npwp_pembeli' => '02.111.222.3-444.555',
            'nama_pembeli' => 'Customer A',
            'alamat_pembeli' => 'Bandung',
            'dpp' => $dpp,
            'ppn' => $ppn,
            'ppnbm' => 0,
            'ppn_rate' => 11.00,
            'status' => $status,
        ]);
    }

    private function createPurchase(string $invoice, string $date, bool $ppnEnabled, float $ppnAmount): Purchase
    {
        return Purchase::factory()->create([
            'invoice' => $invoice,
            'order_date' => $date,
            'ppn_enabled' => $ppnEnabled,
            'ppn_rate' => $ppnEnabled ? 11 : null,
            'ppn_amount' => $ppnAmount,
        ]);
    }

    #[Test]
    public function test_spt_summary_includes_ppn_masukan_from_purchases(): void
    {
        // PPN Keluaran: 1,100 + 550 = 1,650 (approved fakturs only)
        $this->createFakturPajak(5, 2026, 10000, 1100, 'approved', 1);
        $this->createFakturPajak(5, 2026, 5000, 550, 'approved', 2);
        // A cancelled faktur must NOT count towards Keluaran
        $this->createFakturPajak(5, 2026, 9000, 990, 'cancelled', 3);

        // PPN Masukan from purchases in May 2026: 330 + 220 = 550
        $this->createPurchase('PO-M1', '2026-05-01', true, 330);
        $this->createPurchase('PO-M2', '2026-05-20', true, 220);
        // Non-PPN purchase must be excluded
        $this->createPurchase('PO-M3', '2026-05-10', false, 999);
        // June purchase must be excluded by month
        $this->createPurchase('PO-JUN', '2026-06-01', true, 111);

        $summary = $this->efakturService->getSptMasaSummary(5, 2026);

        $this->assertEquals(5, $summary['masa_pajak']);
        $this->assertEquals(2026, $summary['tahun_pajak']);
        $this->assertEquals(3, $summary['total_faktur']);
        $this->assertEquals(15000, $summary['total_dpp']);
        $this->assertEquals(1650, $summary['total_ppn']);
        $this->assertEquals(550, $summary['total_ppn_masukan']);
        $this->assertEquals(1100, $summary['net_ppn_payable']);
        $this->assertEquals(0, $summary['ppn_credit']);
    }

    #[Test]
    public function test_spt_summary_reports_ppn_credit_when_masukan_exceeds_keluaran(): void
    {
        $this->createFakturPajak(6, 2026, 2000, 220, 'approved', 1);
        $this->createPurchase('PO-CREDIT', '2026-06-05', true, 500);

        $summary = $this->efakturService->getSptMasaSummary(6, 2026);

        $this->assertEquals(220, $summary['total_ppn']);
        $this->assertEquals(500, $summary['total_ppn_masukan']);
        $this->assertEquals(0, $summary['net_ppn_payable']);
        $this->assertEquals(280, $summary['ppn_credit']);
    }

    #[Test]
    public function test_spt_summary_returns_zero_totals_for_empty_period(): void
    {
        $summary = $this->efakturService->getSptMasaSummary(3, 2026);

        $this->assertEquals(0, $summary['total_faktur']);
        $this->assertEquals(0, $summary['total_ppn']);
        $this->assertEquals(0, $summary['total_ppn_masukan']);
        $this->assertEquals(0, $summary['net_ppn_payable']);
        $this->assertEquals(0, $summary['ppn_credit']);
    }
}
