<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SetLocaleTest extends TestCase
{
    use RefreshDatabase;

    private SetLocale $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SetLocale();
    }

    #[Test]
    public function test_sets_locale_from_user_settings_if_authenticated_and_setting_exists()
    {
        // 1. Arrange
        $user = User::factory()->create([
            'system_settings' => ['system_language' => 'id']
        ]);
        $this->actingAs($user);
        $request = new Request();
        $next = function ($req) {
            // This is a dummy "next" middleware in the chain.
            return new \Illuminate\Http\Response('Success');
        };

        // 2. Act
        $this->middleware->handle($request, $next);

        // 3. Assert
        $this->assertEquals('id', App::getLocale());
    }

    #[Test]
    public function test_uses_fallback_locale_if_user_has_no_setting()
    {
        // 1. Arrange
        $user = User::factory()->create([
            'system_settings' => [] // No language setting
        ]);
        $this->actingAs($user);

        // Set a specific fallback for this test
        config(['app.fallback_locale' => 'fr']);

        $request = new Request();
        $next = function ($req) {
            return new \Illuminate\Http\Response('Success');
        };

        // 2. Act
        $this->middleware->handle($request, $next);

        // 3. Assert
        $this->assertEquals('fr', App::getLocale());
    }

    #[Test]
    public function test_does_not_change_locale_for_unauthenticated_user()
    {
        // 1. Arrange
        // Ensure no user is authenticated
        Auth::logout();

        // Set a known initial locale
        $initialLocale = 'en';
        App::setLocale($initialLocale);

        $request = new Request();
        $next = function ($req) {
            return new \Illuminate\Http\Response('Success');
        };

        // 2. Act
        $this->middleware->handle($request, $next);

        // 3. Assert
        $this->assertEquals($initialLocale, App::getLocale());
    }
}
