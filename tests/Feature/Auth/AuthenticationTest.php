<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Illuminate\Support\Facades\Auth;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;
    use RefreshDatabase, CreatesTenant;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
    }

    #[Test]
    public function test_login_screen_can_be_rendered(): void
    {
        Auth::guard('web')->logout();
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    }

    #[Test]
    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $response = $this->post('/admin/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    #[Test]
    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        Auth::guard('web')->logout();
        $this->post('/admin/login', [
            'email' => $this->user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    #[Test]
    public function test_users_can_logout(): void
    {
        $response = $this->actingAs($this->user)->post('/admin/logout');

        $this->assertGuest();
        $response->assertRedirect('/admin/login');
    }
}
