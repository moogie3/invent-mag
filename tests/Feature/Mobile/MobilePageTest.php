<?php

namespace Tests\Feature\Mobile;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * The /m shells are public by design: they carry no data, so the service
 * worker can cache them for offline startup. Everything they show is
 * fetched client-side from the bearer-protected /api/v1/mobile/* routes.
 */
class MobilePageTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
    }

    public function test_dashboard_shell_renders(): void
    {
        $response = $this->actingAsGuest()->get('/m');

        $response->assertStatus(200);
        $response->assertViewIs('mobile.dashboard');
        // Range picker is a segmented control, not a <select>.
        $response->assertSee('class="seg"', false);
        $response->assertSee('data-range="this_month"', false);
        // Loading state is a skeleton that mirrors the KPI grid.
        $response->assertSee('id="dashboard-loading"', false);
        $response->assertSee('class="skeleton sk-cell"', false);
        $response->assertSee('data-page="dashboard"', false);
    }

    public function test_login_shell_renders_without_chrome(): void
    {
        $response = $this->actingAsGuest()->get('/m/login');

        $response->assertStatus(200);
        $response->assertViewIs('mobile.login');
        $response->assertSee('id="login-form"', false);
        $response->assertSee('data-page="login"', false);
        // The login shell must not advertise the app's tabs — there is no
        // session yet to know which of them the user may open.
        $response->assertDontSee('class="tabbar"', false);
    }

    public function test_pos_shell_renders(): void
    {
        $response = $this->actingAsGuest()->get('/m/pos');

        $response->assertStatus(200);
        $response->assertViewIs('mobile.pos');
        $response->assertSee('id="pos-search"', false);
        $response->assertSee('id="cart-sheet"', false);
        $response->assertSee('id="pay-sheet"', false);
        $response->assertSee('data-page="pos"', false);
    }

    public function test_products_shell_renders(): void
    {
        $response = $this->actingAsGuest()->get('/m/products');

        $response->assertStatus(200);
        $response->assertViewIs('mobile.products');
        $response->assertSee('id="product-search"', false);
        $response->assertSee('id="product-barcode-input"', false);
        $response->assertSee('data-page="products"', false);
    }

    public function test_shells_link_the_manifest_and_service_worker(): void
    {
        $response = $this->actingAsGuest()->get('/m');

        $response->assertStatus(200);
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('/manifest.json', false);
        $response->assertSee('theme-color', false);
    }

    public function test_shells_are_not_indexed(): void
    {
        $response = $this->actingAsGuest()->get('/m/pos');

        $response->assertStatus(200);
        $response->assertSee('noindex', false);
    }

    public function test_shells_carry_currency_settings_for_the_client(): void
    {
        $response = $this->actingAsGuest()->get('/m');

        $response->assertStatus(200);
        $response->assertSee('data-currency-settings', false);
        $response->assertSee('currency_symbol', false);
    }

    public function test_shells_render_tab_links_to_the_other_pages(): void
    {
        $response = $this->actingAsGuest()->get('/m');

        $response->assertStatus(200);
        $response->assertSee(route('mobile.pos'), false);
        $response->assertSee(route('mobile.products'), false);
        $response->assertSee(route('mobile.dashboard'), false);
    }

    public function test_pos_shell_ships_a_barcode_field_for_hid_scanners(): void
    {
        $response = $this->actingAsGuest()->get('/m/pos');

        $response->assertStatus(200);
        $response->assertSee('id="pos-barcode-input"', false);
        $response->assertSee('enterkeyhint="search"', false);
    }

    public function test_root_still_redirects_to_the_admin_login(): void
    {
        // The PWA lives beside the panel, not in front of it.
        $this->actingAsGuest()
            ->get('/')
            ->assertRedirect(route('admin.login'));
    }

    public function test_mobile_api_still_requires_a_token_from_the_shell_origin(): void
    {
        // The shells are public; the data behind them is not. A page that
        // lost its token must land back on /m/login rather than leak JSON.
        $this->actingAsGuest()
            ->getJson('/api/v1/mobile/dashboard')
            ->assertStatus(401);
    }
}
