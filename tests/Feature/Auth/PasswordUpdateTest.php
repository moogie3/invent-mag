<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use PHPUnit\Framework\Attributes\Test;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
    }

    #[Test]
    public function test_password_can_be_updated(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->from('/admin/profile')
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'StrongUniquePass!789',
                'password_confirmation' => 'StrongUniquePass!789',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin/profile');

        $this->assertTrue(Hash::check('StrongUniquePass!789', $this->user->refresh()->password));
    }

    #[Test]
    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->from('/admin/profile')
            ->put(route('user-password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'StrongUniquePass!789',
                'password_confirmation' => 'StrongUniquePass!789',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/admin/profile');
    }
}
