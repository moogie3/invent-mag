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
     * @group User Settings
     * @title Get User Settings
     * @authenticated
     *
     * @response {
     *  "navigation_type": "sidebar",
     *  "theme_mode": "dark",
     *  ...
     * }
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
     * @group User Settings
     * @title Update Theme Mode
     * @authenticated
     * @bodyParam theme_mode string required The new theme mode. Must be 'light' or 'dark'. Example: "dark"
     *
     * @response {
     *  "success": true,
     *  "message": "Theme mode updated successfully.",
     *  "theme_mode": "dark"
     * }
     */
    public function updateThemeMode(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validatedData = $request->validate([
            'theme_mode' => 'required|in:light,dark',
        ]);

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
