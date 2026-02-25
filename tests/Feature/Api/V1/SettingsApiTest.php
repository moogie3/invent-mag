<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->user->assignRole($role);
    }

    public function test_get_settings_returns_unauthorized_if_user_is_not_authenticated()
    {
        $response = $this->getJson('/api/v1/settings');

        $response->assertUnauthorized();
    }

    public function test_get_settings_returns_json_data()
    {
        $this->user->system_settings = ['theme_mode' => 'dark'];
        $this->user->save();

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/settings');

        $response->assertOk()
            ->assertJsonStructure([
                'theme_mode',
            ])
            ->assertJson([
                'theme_mode' => 'dark',
            ]);
    }

    public function test_update_theme_mode_returns_unauthorized_if_user_is_not_authenticated()
    {
        $response = $this->putJson('/api/v1/settings/theme-mode', ['theme_mode' => 'light']);

        $response->assertUnauthorized();
    }

    public function test_update_theme_mode_modifies_user_settings()
    {
        $this->user->system_settings = ['theme_mode' => 'dark'];
        $this->user->save();

        $response = $this->actingAs($this->user, 'sanctum')->putJson('/api/v1/settings/theme-mode', [
            'theme_mode' => 'light',
        ]);

        $response->assertOk()
            ->assertJson([
                'theme_mode' => 'light',
            ]);

        $this->user->refresh();
        $this->assertEquals('light', $this->user->system_settings['theme_mode']);
    }
}
