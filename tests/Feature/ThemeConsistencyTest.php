<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class ThemeConsistencyTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
    }

    #[Test]
    public function the_login_form_lets_turbo_go_so_the_saved_theme_applies(): void
    {
        Auth::guard('web')->logout();
        $response = $this->get('/admin/login');

        $response->assertOk();
        $this->assertStringContainsString(
            'data-turbo="false"',
            $response->getContent(),
            'Turbo must not swallow the login POST: only a full page load renders the saved theme.'
        );
    }

    #[Test]
    public function the_login_layout_does_not_pin_a_hardcoded_light_theme(): void
    {
        Auth::guard('web')->logout();
        $response = $this->get('/admin/login');

        $response->assertOk();
        $this->assertStringNotContainsString(
            '<html lang="en" data-bs-theme="light">',
            $response->getContent(),
            'The auth layout must not pin light: the saved choice is applied before first paint.'
        );
    }

    #[Test]
    public function an_error_page_follows_the_users_theme(): void
    {
        $this->user->system_settings = ['theme_mode' => 'light'];
        $this->user->save();

        $response = $this->actingAs($this->user)->get('/definitely-not-a-route');

        $response->assertNotFound();
        $this->assertStringContainsString(
            'data-bs-theme="light"',
            $response->getContent(),
            'An error page must use the theme it computes instead of a hardcoded one.'
        );
    }
}
