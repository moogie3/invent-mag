<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $superUserRole = Role::firstOrCreate(['name' => 'superuser']);
        $this->adminUser->assignRole($superUserRole);
    }

    public function test_index_displays_settings_page()
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.setting.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.settings.settings');
    }

    public function test_update_settings_successfully()
    {
        $settingsData = [
            'navigation_type' => 'navbar',
            'theme_mode' => 'dark',
            'notification_duration' => 10,
            'auto_logout_time' => 120,
            'data_refresh_rate' => 60,
            'system_language' => 'id',
            'sidebar_lock' => true,
            'show_theme_toggle' => false,
            'enable_sound_notifications' => false,
            'enable_browser_notifications' => false,
            'show_success_messages' => false,
            'remember_last_page' => false,
            'enable_animations' => false,
            'lazy_load_images' => false,
            'enable_debug_mode' => true,
            'enable_keyboard_shortcuts' => false,
            'show_tooltips' => false,
            'compact_mode' => true,
            'sticky_navbar' => true,
        ];

        $response = $this->actingAs($this->adminUser)->put(route('admin.setting.update'), $settingsData);

        $response->assertRedirect(route('admin.setting.index'));
        $response->assertSessionHas('success', 'System settings updated successfully.');

        $this->adminUser->refresh();
        $this->assertEquals('navbar', $this->adminUser->system_settings['navigation_type']);
        $this->assertEquals('dark', $this->adminUser->system_settings['theme_mode']);
        $this->assertTrue($this->adminUser->system_settings['sidebar_lock']);
        $this->assertFalse($this->adminUser->system_settings['show_theme_toggle']);
    }

    public function test_update_settings_with_invalid_data_returns_validation_errors()
    {
        $settingsData = [
            'navigation_type' => 'invalid', // Invalid value
            'theme_mode' => 'invalid', // Invalid value
            'notification_duration' => 'abc', // Invalid type
            'auto_logout_time' => -1, // Invalid value
            'system_language' => 'fr', // Invalid value
        ];

        $response = $this->actingAs($this->adminUser)->putJson(route('admin.setting.update'), $settingsData);

        $response->assertStatus(422)->assertJsonValidationErrors([
            'navigation_type',
            'theme_mode',
            'notification_duration',
            'auto_logout_time',
            'system_language',
        ]);
    }

    public function test_get_settings_returns_user_settings_with_defaults()
    {
        // Set some custom settings for the user
        $this->adminUser->system_settings = [
            'navigation_type' => 'navbar',
            'theme_mode' => 'dark',
            'notification_duration' => 10,
        ];
        $this->adminUser->save();

        $response = $this->actingAs($this->adminUser)->get(route('admin.api.settings'));

        $response->assertStatus(200);
        $response->assertJson([
            'navigation_type' => 'navbar',
            'theme_mode' => 'dark',
            'notification_duration' => 10,
            'auto_logout_time' => 60, // Default value
            'system_language' => 'en', // Default value
        ]);
    }

    public function test_update_theme_mode_successfully()
    {
        $response = $this->actingAs($this->adminUser)->put(route('admin.setting.update-theme-mode'), [
            'theme_mode' => 'dark'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Theme mode updated successfully.',
            'theme_mode' => 'dark'
        ]);

        $this->adminUser->refresh();
        $this->assertEquals('dark', $this->adminUser->system_settings['theme_mode']);
    }

    public function test_update_theme_mode_with_invalid_data_returns_validation_errors()
    {
        $response = $this->actingAs($this->adminUser)->putJson(route('admin.setting.update-theme-mode'), [
                'theme_mode' => 'invalid'
            ]);

            $response->assertStatus(422)->assertJsonValidationErrors(['theme_mode']);
        }
}
