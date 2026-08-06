<?php

namespace Tests\Unit\Services;

use App\Models\Tax;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class TaxServiceEnsureIndonesianDefaultsTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected TaxService $taxService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->taxService = app(TaxService::class);
    }

    #[Test]
    public function it_creates_ppn_and_pph_records_when_none_exist(): void
    {
        $created = $this->taxService->ensureIndonesianDefaults($this->tenant);

        $this->assertSame(['PPN 11%', 'PPH 23', 'PPh Final 0.5%'], $created);

        $ppn = Tax::where('is_ppn', true)->first();
        $this->assertNotNull($ppn);
        $this->assertSame(11.0, $ppn->rate);
        $this->assertSame(Tax::TYPE_EXCLUSIVE, $ppn->type);
        $this->assertTrue($ppn->is_active);
        $this->assertTrue($ppn->is_default);

        $pph23 = Tax::where('name', 'PPH 23')->first();
        $this->assertNotNull($pph23);
        $this->assertSame(2.0, $pph23->rate);
        $this->assertSame(Tax::TYPE_EXCLUSIVE, $pph23->type);
        $this->assertTrue($pph23->is_active);
        $this->assertFalse($pph23->is_default);
        $this->assertTrue($pph23->is_pph);

        $pphFinal = Tax::where('name', 'PPh Final 0.5%')->first();
        $this->assertNotNull($pphFinal);
        $this->assertSame(0.5, $pphFinal->rate);
        $this->assertSame(Tax::TYPE_EXCLUSIVE, $pphFinal->type);
        $this->assertTrue($pphFinal->is_active);
        $this->assertFalse($pphFinal->is_default);
        $this->assertTrue($pphFinal->is_pph);

        $this->assertSame(2, Tax::where('is_pph', true)->count());
    }

    #[Test]
    public function it_is_idempotent(): void
    {
        $this->taxService->ensureIndonesianDefaults($this->tenant);
        $second = $this->taxService->ensureIndonesianDefaults($this->tenant);

        $this->assertSame([], $second);
        $this->assertSame(1, Tax::where('is_ppn', true)->count());
        $this->assertSame(2, Tax::where('is_pph', true)->count());
    }

    #[Test]
    public function it_skips_ppn_when_an_is_ppn_tax_already_exists(): void
    {
        Tax::create(['name' => 'PPN 12%', 'rate' => 12, 'type' => 'exclusive', 'is_ppn' => true]);

        $created = $this->taxService->ensureIndonesianDefaults($this->tenant);

        $this->assertSame(['PPH 23', 'PPh Final 0.5%'], $created);
        $this->assertSame(1, Tax::where('is_ppn', true)->count());
        $this->assertSame(2, Tax::where('is_pph', true)->count());
    }

    #[Test]
    public function it_skips_duplicate_pph_23_when_an_is_pph_tax_already_exists(): void
    {
        Tax::create(['name' => 'PPh Final 0.5%', 'rate' => 0.5, 'type' => 'exclusive', 'is_pph' => true]);

        $created = $this->taxService->ensureIndonesianDefaults($this->tenant);

        $this->assertSame(['PPN 11%'], $created);
        $this->assertSame(0, Tax::where('name', 'PPH 23')->count());
        $this->assertSame(1, Tax::where('is_pph', true)->count());
        $this->assertSame(1, Tax::where('is_ppn', true)->count());
    }

    #[Test]
    public function it_creates_pph_final_when_pph_23_already_exists(): void
    {
        Tax::create(['name' => 'PPH 23', 'rate' => 2, 'type' => 'exclusive', 'is_pph' => true]);

        $created = $this->taxService->ensureIndonesianDefaults($this->tenant);

        $this->assertSame(['PPN 11%', 'PPh Final 0.5%'], $created);
        $this->assertSame(1, Tax::where('is_ppn', true)->count());
        $this->assertSame(2, Tax::where('is_pph', true)->count());
        $this->assertSame(0.5, (float) Tax::where('name', 'PPh Final 0.5%')->value('rate'));
        $this->assertSame(1, Tax::where('name', 'PPH 23')->count());
    }

    #[Test]
    public function it_does_not_override_an_existing_default_tax(): void
    {
        $default = Tax::create(['name' => 'VAT 10%', 'rate' => 10, 'type' => 'exclusive', 'is_default' => true]);

        $this->taxService->ensureIndonesianDefaults($this->tenant);

        $ppn = Tax::where('is_ppn', true)->first();
        $this->assertNotNull($ppn);
        $this->assertFalse($ppn->is_default);
        $this->assertTrue($default->fresh()->is_default);
    }
}
