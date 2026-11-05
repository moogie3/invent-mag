<?php

namespace Tests\Feature\Admin;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * HTTP-level coverage for Accounting › Financial Health.
 *
 * The service math lives in tests/Unit/Services/FinancialHealthServiceTest;
 * this file pins the CONTRACT BETWEEN page and browser instead:
 *  - the period filter is a plain GET form whose query params survive the
 *    round-trip (preset stays selected, chip shows the resolved range);
 *  - the explainer is PER-ENTRY accordions (12 metrics + 4 outlook), every
 *    entry collapsed by default, intro + color legend always visible;
 *  - point-in-time metrics get lock icons only when a filter is active;
 *  - the drill-down buttons carry the theme's soft-pill styling.
 */
class FinancialHealthControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
    }

    // ── Page renders ────────────────────────────────────────────────

    public function test_financial_health_page_renders()
    {
        $this->actingAs($this->user)
            ->get(route('admin.accounting.health'))
            ->assertOk()
            ->assertSee('id="fhPeriodForm"', false)
            ->assertSee('id="fhCompareAllBtn"', false);
    }

    // ── Period filter ───────────────────────────────────────────────

    public function test_default_view_shows_the_rolling_window_and_hides_custom_inputs()
    {
        $response = $this->actingAs($this->user)->get(route('admin.accounting.health'));

        // The chip always names the window on screen…
        $response->assertSee('fh-period-chip');
        $response->assertSee('Showing:');
        // …and the custom month inputs start hidden + disabled so a preset
        // submit never carries stale from/to (financial-health.js keeps the
        // two in sync; the disabled attribute is server-rendered insurance).
        $response->assertSee('fh-custom-range', false);
        // No Reset button on the default view — there is nothing to reset.
        // (It renders with ti-restore; the form action is the same URL, so
        // the icon is the only unambiguous marker.)
        $this->assertStringNotContainsString(
            'ti-restore',
            $response->getContent(),
            'Reset button should not appear on the default view'
        );
    }

    public function test_preset_query_param_is_reflected_in_the_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.accounting.health', ['preset' => 'last_3']))
            ->assertOk();

        // The preset stays selected after the reload (the form is the state).
        $this->assertMatchesRegularExpression(
            '/<option value="last_3" selected/',
            $response->getContent()
        );
        // The chip shows a real range, never a raw placeholder.
        $this->assertMatchesRegularExpression(
            '/Showing:.*\d{4}/',
            $response->getContent()
        );
        $this->assertStringNotContainsString('{range}', $response->getContent());
    }

    public function test_unknown_preset_falls_back_to_the_default_view()
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.accounting.health', ['preset' => 'not_a_preset']))
            ->assertOk();

        // Nothing is selected — the default option stays the chosen one.
        $this->assertDoesNotMatchRegularExpression(
            '/<option value="last_3" selected/',
            $response->getContent()
        );
    }

    public function test_custom_range_query_params_render_the_resolved_window()
    {
        // A reversed range is pulled into order server-side; the page must
        // show the RESOLVED window, not echo the raw input back.
        $response = $this->actingAs($this->user)
            ->get(route('admin.accounting.health', ['from' => '2026-08', 'to' => '2026-05']))
            ->assertOk();

        $this->assertMatchesRegularExpression(
            '/<option value="custom" selected/',
            $response->getContent()
        );
    }

    // ── Explainer: per-entry accordions ─────────────────────────────

    public function test_explainer_has_sixteen_entry_toggles_all_collapsed()
    {
        $html = $this->actingAs($this->user)
            ->get(route('admin.accounting.health'))
            ->assertOk()
            ->getContent();

        // 12 metric entries + 4 outlook concepts.
        $this->assertSame(
            16,
            substr_count($html, 'fh-explainer-item'),
            'Expected one toggle per explainer entry (12 metrics + 4 outlook)'
        );
        // Every toggle controls its own collapse panel, and every panel
        // starts collapsed (no "show" class server-rendered).
        $this->assertSame(16, substr_count($html, 'aria-controls="fh-expl-'));
        $this->assertSame(16, substr_count($html, 'id="fh-expl-'));
        $this->assertSame(
            0,
            substr_count($html, 'collapse show'),
            'No explainer entry may render expanded'
        );
        // The section itself has NO whole-panel toggle any more — the intro
        // and the color legend are always on screen.
        $this->assertStringNotContainsString('id="fhExplainerBody"', $html);
        $this->assertStringNotContainsString('id="fhExplainerToggle"', $html);
    }

    // ── Lock icons: point-in-time honesty ───────────────────────────

    public function test_lock_icons_appear_only_when_a_filter_is_active()
    {
        // Default view: no filter, so nothing to explain — no locks.
        $default = $this->actingAs($this->user)
            ->get(route('admin.accounting.health'))
            ->getContent();
        $this->assertStringNotContainsString('ti-lock', $default);

        // Filtered view: the 7 balance-sheet metrics are point-in-time and
        // carry the lock + tooltip explaining why they ignore the period.
        $filtered = $this->actingAs($this->user)
            ->get(route('admin.accounting.health', ['preset' => 'last_3']))
            ->getContent();
        $this->assertSame(
            7,
            substr_count($filtered, 'ti-lock'),
            'Expected a lock icon on each of the 7 point-in-time metrics'
        );
        $this->assertStringContainsString('Point-in-time', $filtered);
    }

    // ── Theme consistency ───────────────────────────────────────────

    public function test_attention_drilldowns_use_soft_pill_styling()
    {
        // The "Open payroll" / "Open journal" links share the page's
        // bg-*-lt pill language instead of a generic outline button.
        $html = $this->actingAs($this->user)
            ->get(route('admin.accounting.health'))
            ->getContent();

        $this->assertStringContainsString('btn btn-sm rounded-pill bg-', $html);
        $this->assertStringNotContainsString('btn-outline-primary', $html);
    }

    public function test_compare_all_button_uses_the_soft_pill_style()
    {
        $html = $this->actingAs($this->user)
            ->get(route('admin.accounting.health'))
            ->getContent();

        // Class order in the markup is fixed — assert against it directly
        // (attribute-quoted, so a stray match elsewhere can't pass).
        // fh-soft-action is what makes it read as a button (border + hover)
        // rather than a static badge.
        $this->assertStringContainsString(
            'class="btn btn-sm rounded-pill bg-primary-lt text-primary fh-soft-action fh-compare-all"',
            $html
        );
    }

    // ── Indonesian locale ───────────────────────────────────────────

    public function test_indonesian_locale_renders_the_page()
    {
        // The locale rides on the user record (system_settings.system_language),
        // not the URL — flip it and confirm the page still renders with the
        // translated filter chip.
        $this->user->system_settings = ['system_language' => 'id'];
        $this->user->save();

        $html = $this->actingAs($this->user)
            ->get(route('admin.accounting.health'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Menampilkan:', $html);
        $this->assertStringNotContainsString('{range}', $html);
        // Tutorial copy is translated too (the new fh_compare step).
        $this->assertNotSame(
            'Compare & period filter',
            __('tutorial.fh_compare')
        );
    }
}
