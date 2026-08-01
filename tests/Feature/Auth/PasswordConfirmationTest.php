<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class PasswordConfirmationTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
    }

    #[Test]
    public function test_confirm_password_screen_can_be_rendered(): void
    {
        $response = $this->actingAs($this->user)->get(route('password.confirm'));

        $response->assertStatus(200);
    }

    #[Test]
    public function test_password_can_be_confirmed(): void
    {
        $response = $this->actingAs($this->user)->post(route('password.confirm'), [
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    #[Test]
    public function test_password_is_not_confirmed_with_invalid_password(): void
    {
        $response = $this->actingAs($this->user)->post(route('password.confirm'), [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
    }
}
