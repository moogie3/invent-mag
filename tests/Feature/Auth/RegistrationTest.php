<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Auth;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_registration_screen_can_be_rendered(): void
    {
        Auth::guard('web')->logout();
        $response = $this->withoutMiddleware(\App\Http\Middleware\IdentifyTenant::class)->get('/admin/register');

        $response->assertStatus(200);
    }

    #[Test]
    public function test_new_users_can_register(): void
    {
        Event::fake();

        $response = $this->withoutMiddleware(\App\Http\Middleware\IdentifyTenant::class)->post('/admin/register', [
            'name' => 'Test User',
            'shopname' => 'Test Shop',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        Event::assertDispatched(Registered::class);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $response->assertRedirect(route('verification.notice'));
    }
}
