<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Tests\Feature\BaseFeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class RegistrationTest extends BaseFeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.defaults.guard' => 'web']);
    }

    #[Test]
    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/admin/register');

        $response->assertStatus(200);
    }

    #[Test]
    public function test_new_users_can_register(): void
    {
        Event::fake();

        $response = $this->post('/admin/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        Event::assertDispatched(Registered::class);
        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice'));
    }
}
