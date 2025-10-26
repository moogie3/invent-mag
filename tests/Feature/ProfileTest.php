<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        dd(class_exists(\App\Models\CurrencySetting::class));
        // Ensure a CurrencySetting exists for tests that might rely on it
        \App\Models\CurrencySetting::firstOrCreate(
            ['currency_code' => 'IDR'],
            [
                'currency_symbol' => 'Rp',
                'decimal_separator' => ',',
                'thousand_separator' => '.',
                'decimal_places' => 0,
                'position' => 'prefix',
                'locale' => 'id-ID',
            ]
        );
    }

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('admin.setting.profile.edit'));

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(route('admin.setting.profile.update'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'timezone' => 'UTC',
                'shopname' => '',
                'address' => '',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.setting.profile.edit'));

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(route('admin.setting.profile.update'), [
                'name' => 'Test User',
                'email' => $user->email,
                'timezone' => 'UTC',
                'shopname' => '',
                'address' => '',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.setting.profile.edit'));

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    // public function test_user_can_delete_their_account(): void
    // {
    //     $user = User::factory()->create();

    //     $response = $this
    //         ->actingAs($user)
    //         ->delete('/admin/profile', [
    //             'password' => 'password',
    //         ]);

    //     $response
    //         ->assertSessionHasNoErrors()
    //         ->assertRedirect('/');

    //     $this->assertGuest();
    //     $this->assertNull($user->fresh());
    // }

    // public function test_correct_password_must_be_provided_to_delete_account(): void
    // {
    //     $user = User::factory()->create();

    //     $response = $this
    //         ->actingAs($user)
    //         ->from('/admin/profile')
    //         ->delete('/admin/profile', [
    //             'password' => 'wrong-password',
    //         ]);

    //     $response
    //         ->assertSessionHasErrorsIn('userDeletion', 'password')
    //         ->assertRedirect('/admin/profile');

    //     $this->assertNotNull($user->fresh());
    // }
}
