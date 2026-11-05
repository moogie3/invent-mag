<?php

namespace Tests\Feature\Admin;

use App\Jobs\SubmitFakturPajakJob;
use App\Models\FakturPajak;
use App\Models\Plan;
use App\Models\Sales;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * e-Faktur Phase 2 — direct DJP submission via the Klikpajak PJAP
 * (docs/ROADMAP.md §Phase 2): Enterprise plan gate, queue dispatch,
 * and the settings form split (Klikpajak card posts on its own).
 */
class EfakturApiTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(PlanSeeder::class);
        $this->actingAs($this->user);
    }

    private function assignPlan(string $slug): void
    {
        $plan = Plan::findBySlug($slug);
        $this->assertNotNull($plan, "Plan '{$slug}' not found");

        $this->tenant->update([
            'plan_id' => $plan->id,
            'plan_status' => 'active',
            'plan_expires_at' => null,
            'trial_ends_at' => null,
            'billing_period_start' => null,
            'billing_period_end' => null,
        ]);

        $this->tenant->refresh();
        $this->tenant->makeCurrent();
    }

    private function makeFaktur(string $status = 'draft'): FakturPajak
    {
        $sale = Sales::factory()->create();

        return FakturPajak::create([
            'sales_id' => $sale->id,
            'nsfp' => '010.000-26.00000001',
            'tanggal_faktur' => '2026-11-04',
            'masa_pajak' => 10,
            'tahun_pajak' => 2026,
            'npwp_penjual' => '01.234.567.8-901.000',
            'nama_penjual' => 'Test Tenant',
            'alamat_penjual' => 'Jakarta',
            'npwp_pembeli' => '02.111.222.3-444.555',
            'nama_pembeli' => 'Customer A',
            'alamat_pembeli' => 'Bandung',
            'dpp' => 100000,
            'ppn' => 11000,
            'ppnbm' => 0,
            'ppn_rate' => 11.00,
            'status' => $status,
        ]);
    }

    #[Test]
    public function submit_endpoint_is_blocked_on_professional_plan(): void
    {
        $this->assignPlan('professional');
        $faktur = $this->makeFaktur();
        Queue::fake();

        $response = $this->post(route('admin.efaktur.submit', $faktur));

        $response->assertStatus(302);
        $this->assertStringContainsString('admin/settings/plan/upgrade', $response->headers->get('Location'));
        Queue::assertNotPushed(SubmitFakturPajakJob::class);
    }

    #[Test]
    public function submit_endpoint_queues_the_submission_on_enterprise_plan(): void
    {
        $this->assignPlan('enterprise');
        $this->tenant->update(['klikpajak_api_key' => 'key-123']);
        $faktur = $this->makeFaktur();
        Queue::fake();

        $response = $this->post(route('admin.efaktur.submit', $faktur));

        $response->assertStatus(302);
        $response->assertSessionHas('success', __('messages.efaktur_queued_for_djp'));
        Queue::assertPushedOn('tax-submissions', SubmitFakturPajakJob::class);
        Queue::assertPushed(
            SubmitFakturPajakJob::class,
            fn (SubmitFakturPajakJob $job) => $job->faktur->is($faktur)
        );
    }

    #[Test]
    public function submit_endpoint_rejects_faktur_that_is_not_draft(): void
    {
        $this->assignPlan('enterprise');
        $this->tenant->update(['klikpajak_api_key' => 'key-123']);
        $faktur = $this->makeFaktur('approved');
        Queue::fake();

        $response = $this->post(route('admin.efaktur.submit', $faktur));

        $response->assertSessionHas('error', __('messages.efaktur_not_submittable'));
        Queue::assertNotPushed(SubmitFakturPajakJob::class);
    }

    #[Test]
    public function submit_endpoint_requires_configured_api_credentials(): void
    {
        $this->assignPlan('enterprise');
        $faktur = $this->makeFaktur();
        Queue::fake();

        $response = $this->post(route('admin.efaktur.submit', $faktur));

        $response->assertSessionHas('error', __('messages.efaktur_klikpajak_not_configured'));
        Queue::assertNotPushed(SubmitFakturPajakJob::class);
    }

    #[Test]
    public function klikpajak_credentials_are_saved_from_their_own_form(): void
    {
        $response = $this->post(route('admin.efaktur.settings.save'), [
            'klikpajak_api_key' => 'key-abc',
            'klikpajak_secret' => 'sec-abc',
            'klikpajak_use_sandbox' => '0',
        ]);

        $response->assertSessionHas('success');

        $tenant = $this->tenant->refresh();
        $this->assertSame('key-abc', $tenant->klikpajak_api_key);
        $this->assertSame('sec-abc', $tenant->klikpajak_secret);
        $this->assertEquals(0, (int) $tenant->klikpajak_use_sandbox);
    }

    #[Test]
    public function klikpajak_only_save_never_touches_tax_jurisdiction(): void
    {
        $this->tenant->update([
            'tax_jurisdiction' => 'ID',
            'npwp' => '01.234.567.8-901.000',
            'pkp_status' => true,
            'pkp_date' => '2024-05-01',
            'ppn_rate' => 11.00,
        ]);

        // The Klikpajak card posts to the same endpoint WITHOUT jurisdiction fields
        $response = $this->post(route('admin.efaktur.settings.save'), [
            'klikpajak_api_key' => 'key-abc',
            'klikpajak_secret' => 'sec-abc',
        ]);

        $response->assertSessionHas('success');

        $tenant = $this->tenant->refresh();
        $this->assertSame('ID', $tenant->tax_jurisdiction);
        $this->assertSame('01.234.567.8-901.000', $tenant->npwp);
        $this->assertTrue((bool) $tenant->pkp_status);
        $this->assertSame('2024-05-01', $tenant->pkp_date->format('Y-m-d'));
        $this->assertEquals(11.00, (float) $tenant->ppn_rate);
        $this->assertSame('key-abc', $tenant->klikpajak_api_key);
    }

    #[Test]
    public function settings_page_shows_the_klikpajak_card_to_enterprise(): void
    {
        $this->assignPlan('enterprise');

        $this->get(route('admin.efaktur.settings'))
            ->assertOk()
            ->assertSee('klikpajak_api_key', false);
    }

    #[Test]
    public function settings_page_shows_the_enterprise_upsell_to_professional(): void
    {
        $this->assignPlan('professional');

        $this->get(route('admin.efaktur.settings'))
            ->assertOk()
            ->assertSee(__('messages.efaktur_klikpajak_enterprise_only'))
            ->assertDontSee('klikpajak_api_key', false);
    }

    #[Test]
    public function show_page_renders_submit_button_only_when_enabled(): void
    {
        $this->assignPlan('enterprise');
        $this->tenant->update(['klikpajak_api_key' => 'key-123']);
        $faktur = $this->makeFaktur();

        // Assert on the form action, not the button label: the tutorial modal
        // renders on every page and its DJP-status guide quotes the label.
        $submitUrl = route('admin.efaktur.submit', $faktur);

        $this->get(route('admin.efaktur.show', $faktur))
            ->assertOk()
            ->assertSee('action="'.$submitUrl.'"', false);

        $this->assignPlan('professional');

        $this->get(route('admin.efaktur.show', $faktur))
            ->assertOk()
            ->assertDontSee($submitUrl, false);
    }
}
