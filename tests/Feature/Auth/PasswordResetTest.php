<?php

namespace Tests\Feature\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Auth;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;
    use RefreshDatabase, CreatesTenant;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
    }

    #[Test]
    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        Auth::guard('web')->logout();
        $response = $this->get('/admin/forgot-password');

        $response->assertStatus(200);
    }

    #[Test]
    public function test_reset_password_link_can_be_requested(): void
    {
        Auth::guard('web')->logout();
        Notification::fake();

        $this->post('/admin/forgot-password', ['email' => $this->user->email]);

        Notification::assertSentTo($this->user, ResetPassword::class);
    }

    #[Test]
    public function test_reset_password_screen_can_be_rendered(): void
    {
        Auth::guard('web')->logout();
        Notification::fake();

        $this->post('/admin/forgot-password', ['email' => $this->user->email]);

        Notification::assertSentTo($this->user, ResetPassword::class, function ($notification) {
            $response = $this->get('/admin/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    #[Test]
    public function test_password_can_be_reset_with_valid_token(): void
    {
        Auth::guard('web')->logout();
        Notification::fake();

        $this->post('/admin/forgot-password', ['email' => $this->user->email]);

        Notification::assertSentTo($this->user, ResetPassword::class, function ($notification) {
            $response = $this->post('/admin/reset-password', [
                'token' => $notification->token,
                'email' => $this->user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('admin.login'));

            return true;
        });
    }
}
