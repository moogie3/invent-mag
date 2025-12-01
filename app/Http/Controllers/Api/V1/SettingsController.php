<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @group User Settings
 *
 * APIs for managing user-specific settings
 */
class SettingsController extends Controller
{
    /**
     * Update User Settings
     *
     * @group User Settings
     * @authenticated
     * @bodyParam navigation_type string The type of navigation. Example: "sidebar"
     * @bodyParam theme_mode string The current theme mode. Example: dark
     * @bodyParam notification_duration integer The duration notifications are displayed. Example: 5
     * @bodyParam auto_logout_time integer The time in minutes after which the user is automatically logged out. Example: 60
     * @bodyParam data_refresh_rate integer The data refresh rate in seconds. Example: 30
     * @bodyParam system_language string The system language. Example: "en"
     * @bodyParam sidebar_lock boolean Whether the sidebar is locked. Example: false
     * @bodyParam show_theme_toggle boolean Whether to show the theme toggle. Example: true
     * @bodyParam enable_sound_notifications boolean Whether sound notifications are enabled. Example: true
     * @bodyParam enable_browser_notifications boolean Whether browser notifications are enabled. Example: true
     * @bodyParam show_success_messages boolean Whether success messages are shown. Example: true
     * @bodyParam remember_last_page boolean Whether to remember the last page visited. Example: true
     * @bodyParam enable_animations boolean Whether animations are enabled. Example: true
     * @bodyParam lazy_load_images boolean Whether images are lazy loaded. Example: true
     * @bodyParam enable_debug_mode boolean Whether debug mode is enabled. Example: false
     * @bodyParam enable_keyboard_shortcuts boolean Whether keyboard shortcuts are enabled. Example: true
     * @bodyParam show_tooltips boolean Whether tooltips are shown. Example: true
     * @bodyParam compact_mode boolean Whether compact mode is enabled. Example: false
     * @bodyParam sticky_navbar boolean Whether the navbar is sticky. Example: false
     *
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField message string A message describing the result of the request.
     * @response 200 {"success": true, "message": "System settings updated successfully."}
     */
    public function update(\App\Http\Requests\Api\V1\UpdateSettingsRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validatedData = $request->validated();

        $existingSettings = $user->system_settings ?? [];

        // Merge with new settings
        $user->system_settings = array_merge($existingSettings, $validatedData);

        $user->save();

        Log::info('System settings updated successfully via API', [
            'user_id' => Auth::id(),
            'settings' => $validatedData
        ]);

        return response()->json(['success' => true, 'message' => 'System settings updated successfully.', 'settings' => $validatedData]);
    }

    /**
     * Get User Settings
     *
     * @group User Settings
     * @authenticated
     *
     * @response 200 {"navigation_type": "sidebar","theme_mode": "light","notification_duration": 5,"auto_logout_time": 60,"data_refresh_rate": 30,"system_language": "en","sidebar_lock": false,"show_theme_toggle": true,"enable_sound_notifications": true,"enable_browser_notifications": true,"show_success_messages": true,"remember_last_page": true,"enable_animations": true,"lazy_load_images": true,"enable_debug_mode": false,"enable_keyboard_shortcuts": true,"show_tooltips": true,"compact_mode": false,"sticky_navbar": false}
     */
    public function getSettings()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $settings = $user->system_settings ?? [];

        $defaults = [
            'navigation_type' => 'sidebar',
            'theme_mode' => 'light',
            'notification_duration' => 5,
            'auto_logout_time' => 60,
            'data_refresh_rate' => 30,
            'system_language' => 'en',
            'sidebar_lock' => false,
            'show_theme_toggle' => true,
            'enable_sound_notifications' => true,
            'enable_browser_notifications' => true,
            'show_success_messages' => true,
            'remember_last_page' => true,
            'enable_animations' => true,
            'lazy_load_images' => true,
            'enable_debug_mode' => false,
            'enable_keyboard_shortcuts' => true,
            'show_tooltips' => true,
            'compact_mode' => false,
            'sticky_navbar' => false,
        ];

        $settings = array_merge($defaults, $settings);

        return response()->json($settings);
    }

    /**
     * Update Theme Mode
     *
     * @group User Settings
     * @authenticated
     * @bodyParam theme_mode string required The new theme mode. Must be 'light' or 'dark'. Example: "dark"
     *
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField message string A message describing the result of the request.
     * @responseField theme_mode string The updated theme mode.
     */
    public function updateThemeMode(\App\Http\Requests\Api\V1\UpdateThemeModeRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validatedData = $request->validated();

        $existingSettings = $user->system_settings ?? [];
        $user->system_settings = array_merge($existingSettings, [
            'theme_mode' => $validatedData['theme_mode']
        ]);

        $user->save();

        Log::info('Theme mode updated via API', [
            'user_id' => Auth::id(),
            'theme_mode' => $validatedData['theme_mode']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Theme mode updated successfully.',
            'theme_mode' => $validatedData['theme_mode']
        ]);
    }
}
