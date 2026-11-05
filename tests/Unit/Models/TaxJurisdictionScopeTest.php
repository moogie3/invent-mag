<?php

namespace Tests\Unit\Models;

use App\Models\Tax;
use App\Services\AccountingService;
use App\Services\PurchaseService;
use App\Services\SalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * PPN/PPh records only exist for the Indonesian jurisdiction. Tenants on
 * any other jurisdiction must not see them in dropdowns nor fall back to
 * them as a default tax — selection has to agree with the journal side,
 * which already gates on isIndonesian().
 */
class TaxJurisdictionScopeTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
    }

    #[Test]
    public function non_indonesian_tenant_excludes_ppn_and_pph_from_active_list(): void
    {
        Tax::create(['name' => 'Regular 10%', 'rate' => 10, 'type' => 'exclusive', 'is_active' => true]);
        Tax::create(['name' => 'PPN 11%', 'rate' => 11, 'type' => 'exclusive', 'is_active' => true, 'is_ppn' => true]);
        Tax::create(['name' => 'PPh 23', 'rate' => 2, 'type' => 'exclusive', 'is_active' => true, 'is_pph' => true]);

        $this->assertFalse($this->tenant->fresh()->isIndonesian());

        $names = Tax::active()->orderBy('name')->pluck('name')->all();

        $this->assertSame(['Regular 10%'], $names);
    }

    #[Test]
    public function indonesian_tenant_sees_ppn_and_pph_taxes(): void
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);

        Tax::create(['name' => 'Regular 10%', 'rate' => 10, 'type' => 'exclusive', 'is_active' => true]);
        Tax::create(['name' => 'PPN 11%', 'rate' => 11, 'type' => 'exclusive', 'is_active' => true, 'is_ppn' => true]);
        Tax::create(['name' => 'PPh 23', 'rate' => 2, 'type' => 'exclusive', 'is_active' => true, 'is_pph' => true]);

        $this->assertCount(3, Tax::active()->get());
    }

    #[Test]
    public function default_fallback_never_resolves_to_a_jurisdiction_tax_for_non_indonesian_tenant(): void
    {
        // The only/default tax is PPN — the fallback must come back null
        // rather than silently applying 11% to a non-ID sale.
        Tax::create([
            'name' => 'PPN 11%',
            'rate' => 11,
            'type' => 'exclusive',
            'is_active' => true,
            'is_default' => true,
            'is_ppn' => true,
        ]);

        $this->assertNull(Tax::default()->first());
        $this->assertNull(Tax::active()->first());
    }

    #[Test]
    public function sales_create_dropdown_excludes_jurisdiction_taxes_for_non_indonesian_tenant(): void
    {
        Tax::create(['name' => 'Regular 10%', 'rate' => 10, 'type' => 'exclusive', 'is_active' => true]);
        Tax::create([
            'name' => 'PPN 11%',
            'rate' => 11,
            'type' => 'exclusive',
            'is_active' => true,
            'is_default' => true,
            'is_ppn' => true,
        ]);

        $salesService = new SalesService(Mockery::mock(AccountingService::class));
        $data = $salesService->getSalesCreateData();

        $this->assertSame(['Regular 10%'], $data['taxes']->pluck('name')->all());
        // Default must resolve to the regular tax, not the PPN one
        $this->assertSame('Regular 10%', $data['defaultTax']->name);
    }

    #[Test]
    public function purchase_create_dropdown_excludes_jurisdiction_taxes_for_non_indonesian_tenant(): void
    {
        Tax::create(['name' => 'Regular 10%', 'rate' => 10, 'type' => 'exclusive', 'is_active' => true]);
        Tax::create([
            'name' => 'PPN 11%',
            'rate' => 11,
            'type' => 'exclusive',
            'is_active' => true,
            'is_default' => true,
            'is_ppn' => true,
        ]);

        $purchaseService = new PurchaseService(Mockery::mock(AccountingService::class));
        $data = $purchaseService->getPurchaseCreateData();

        $this->assertSame(['Regular 10%'], $data['taxes']->pluck('name')->all());
        $this->assertSame('Regular 10%', $data['defaultTax']->name);
    }

    #[Test]
    public function sales_create_dropdown_handles_no_eligible_tax_without_erroring(): void
    {
        // Only jurisdiction taxes exist → empty list, null default. Blades
        // read $defaultTax->type ?? '...' so null must not fatal.
        Tax::create([
            'name' => 'PPN 11%',
            'rate' => 11,
            'type' => 'exclusive',
            'is_active' => true,
            'is_default' => true,
            'is_ppn' => true,
        ]);

        $salesService = new SalesService(Mockery::mock(AccountingService::class));
        $data = $salesService->getSalesCreateData();

        $this->assertCount(0, $data['taxes']);
        $this->assertNull($data['defaultTax']);
        $this->assertNull($data['tax']);
    }
}
