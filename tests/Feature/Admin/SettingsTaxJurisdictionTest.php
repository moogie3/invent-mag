<?php

namespace Tests\Feature\Admin;

use App\Models\Tax;
use App\Services\TaxService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class SettingsTaxJurisdictionTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
        $this->actingAs($this->user);
    }

    public function test_switching_to_indonesia_creates_default_tax_records(): void
    {
        $response = $this->put(route('admin.setting.update-tax-jurisdiction'), [
            'tax_jurisdiction' => 'ID',
        ]);

        $response->assertRedirect(route('admin.setting.index'));
        $this->assertTrue($this->tenant->fresh()->isIndonesian());
        $this->assertSame(1, Tax::where('is_ppn', true)->count());
        $this->assertSame(2, Tax::where('is_pph', true)->count());
        $this->assertSame('PPN 11%', Tax::where('is_ppn', true)->value('name'));
        $this->assertTrue(Tax::where('is_pph', true)->where('name', 'PPH 23')->exists());
        $this->assertTrue(Tax::where('is_pph', true)->where('name', 'PPh Final 0.5%')->exists());
    }

    public function test_switching_to_indonesia_via_ajax_returns_success_and_creates_records(): void
    {
        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('PUT', route('admin.setting.update-tax-jurisdiction'), [
                'tax_jurisdiction' => 'ID',
            ]);

        $response->assertJson(['success' => true]);
        $this->assertSame(1, Tax::where('is_ppn', true)->count());
        $this->assertSame(2, Tax::where('is_pph', true)->count());
    }

    public function test_switching_to_indonesia_is_idempotent(): void
    {
        $this->put(route('admin.setting.update-tax-jurisdiction'), ['tax_jurisdiction' => 'ID']);
        $this->put(route('admin.setting.update-tax-jurisdiction'), ['tax_jurisdiction' => 'ID']);

        $this->assertSame(1, Tax::where('is_ppn', true)->count());
        $this->assertSame(2, Tax::where('is_pph', true)->count());
    }

    public function test_switching_away_from_indonesia_leaves_tax_records_intact(): void
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);
        app(TaxService::class)->ensureIndonesianDefaults($this->tenant);
        $this->assertSame(1, Tax::where('is_ppn', true)->count());

        $response = $this->put(route('admin.setting.update-tax-jurisdiction'), [
            'tax_jurisdiction' => '',
        ]);

        $response->assertRedirect(route('admin.setting.index'));
        $this->assertFalse($this->tenant->fresh()->isIndonesian());
        $this->assertSame(1, Tax::where('is_ppn', true)->count());
        $this->assertSame(2, Tax::where('is_pph', true)->count());
    }
}
