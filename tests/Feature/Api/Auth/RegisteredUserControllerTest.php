<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Multitenancy\Models\Tenant;

class RegisteredUserControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_new_users_can_register_via_api()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'shopname' => 'Test Shop',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'user',
            'access_token',
            'token_type',
            'tenant_domain',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertDatabaseHas('tenants', ['name' => 'Test Shop']);
    }

    public function test_registration_requires_validation()
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'shopname', 'email', 'password']);
    }

    public function test_registration_requires_unique_email()
    {
        // First user
        $this->postJson('/api/register', [
            'name' => 'Test User',
            'shopname' => 'Test Shop',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // Second user with same email
        $response = $this->postJson('/api/register', [
            'name' => 'Test User 2',
            'shopname' => 'Test Shop 2',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_unique_shopname()
    {
        // First user
        $this->postJson('/api/register', [
            'name' => 'Test User',
            'shopname' => 'Test Shop',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // Second user with same shopname
        $response = $this->postJson('/api/register', [
            'name' => 'Test User 2',
            'shopname' => 'Test Shop',
            'email' => 'test2@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shopname']);
    }
}
