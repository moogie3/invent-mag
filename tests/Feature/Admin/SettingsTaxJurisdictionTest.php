<?php

namespace Tests\Feature\Admin;

use App\Models\Sales;
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

    /** Payload the main settings form posts (settings.js submits every field). */
    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'navigation_type' => 'sidebar',
            'theme_mode' => 'dark',
            'notification_duration' => 10,
            'auto_logout_time' => 120,
            'data_refresh_rate' => 60,
            'system_language' => 'en',
        ], $overrides);
    }

    private function putMainSettings(array $overrides = [])
    {
        return $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->put(route('admin.setting.update'), $this->settingsPayload($overrides));
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

    // ------------------------------------------------------------------
    // Main settings form (systemSettingsForm) — the hidden tax_jurisdiction
    // field mirrors the select so "Save Settings" persists it too. Before
    // this, the select's value was silently dropped by this path: the toast
    // claimed success while the tenant never changed.
    // ------------------------------------------------------------------

    public function test_main_form_save_persists_jurisdiction_via_hidden_field(): void
    {
        $response = $this->putMainSettings(['tax_jurisdiction' => 'ID']);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertStringContainsString('Tax jurisdiction updated successfully.', $response->json('message'));

        $this->assertTrue($this->tenant->fresh()->isIndonesian());
        // Same defaults as the AJAX endpoint
        $this->assertSame(1, Tax::where('is_ppn', true)->count());
        $this->assertSame(2, Tax::where('is_pph', true)->count());
        // tax_jurisdiction is a tenant attribute — never a per-user setting
        $this->assertArrayNotHasKey('tax_jurisdiction', $this->user->fresh()->system_settings ?? []);
    }

    public function test_main_form_save_without_jurisdiction_field_leaves_tenant_untouched(): void
    {
        // Starter plan: the section (and its hidden field) is not rendered,
        // so the request carries no tax_jurisdiction key at all.
        $response = $this->putMainSettings();

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertStringNotContainsString('Tax jurisdiction', $response->json('message'));
        $this->assertNull($this->tenant->fresh()->tax_jurisdiction);
    }

    public function test_main_form_save_is_idempotent_and_does_not_claim_a_change(): void
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);
        app(TaxService::class)->ensureIndonesianDefaults($this->tenant);

        $response = $this->putMainSettings(['tax_jurisdiction' => 'ID']);

        $response->assertOk();
        // Unchanged value → plain settings message, no false "jurisdiction saved"
        $this->assertSame('System settings updated successfully.', $response->json('message'));
        $this->assertTrue($this->tenant->fresh()->isIndonesian());
    }

    public function test_main_form_save_discloses_blocked_switch_but_still_saves_settings(): void
    {
        $this->tenant->update(['tax_jurisdiction' => 'ID']);
        app(TaxService::class)->ensureIndonesianDefaults($this->tenant);

        // An unissued PPN sale blocks the ID → None switch (safeguard)
        Sales::factory()->create(['ppn_enabled' => true, 'ppn_amount' => 22.0]);

        $response = $this->putMainSettings([
            'tax_jurisdiction' => '',
            'theme_mode' => 'light',
        ]);

        $response->assertOk();
        // Settings still save, but the message must disclose the refusal —
        // otherwise a success toast would claim a change that never happened.
        $this->assertTrue($this->tenant->fresh()->isIndonesian());
        $this->assertStringContainsString(
            'Cannot disable Indonesian tax jurisdiction',
            $response->json('message')
        );
        $this->assertSame('light', $this->user->fresh()->system_settings['theme_mode']);
    }
}
