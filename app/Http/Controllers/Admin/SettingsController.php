<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.settings');
    }

    public function update(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $validatedData = $request->validate([
                'navigation_type' => 'required|in:sidebar,navbar,both',
                'theme_mode' => 'required|in:light,dark,auto',
                'notification_duration' => 'required|integer|min:0',
                'auto_logout_time' => 'required|integer|min:0',
                'data_refresh_rate' => 'required|integer|min:0',
                'system_language' => 'required|in:en,id',
            ]);

            $checkboxFields = [
                'sidebar_lock',
                'show_theme_toggle',
                'enable_sound_notifications',
                'enable_browser_notifications',
                'show_success_messages',
                'remember_last_page',
                'enable_animations',
                'lazy_load_images',
                'enable_debug_mode',
                'enable_keyboard_shortcuts',
                'show_tooltips',
                'compact_mode',
                'sticky_navbar',
            ];

            $settingsToSave = $validatedData;

            foreach ($checkboxFields as $field) {
                $settingsToSave[$field] = $request->has($field);
            }

            // Get existing settings or create empty array
            $existingSettings = $user->system_settings ?? [];

            // Merge with new settings
            $user->system_settings = array_merge($existingSettings, $settingsToSave);

            Log::debug('Before save - show_theme_toggle:', [
                'value' => $user->system_settings['show_theme_toggle'] ?? 'not set',
                'user_id' => $user->id
            ]);

            // Save the user
            $user->save();

            // Reload the user from the database to get the latest settings
            $user->refresh();

            Log::debug('After save (and refresh) - show_theme_toggle:', [
                'value' => $user->system_settings['show_theme_toggle'] ?? 'not set',
                'user_id' => $user->id
            ]);

            Log::info('System settings updated successfully', [
                'user_id' => $user->id,
                'theme_mode' => $settingsToSave['theme_mode'],
                'settings' => $settingsToSave
            ]);

            return back()->with('success', 'System settings updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating system settings', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to update system settings. Please try again.');
        }
    }

    public function updateThemeMode(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $validatedData = $request->validate([
                'theme_mode' => 'required|in:light,dark,auto',
            ]);

            $existingSettings = $user->system_settings ?? [];
            $user->system_settings = array_merge($existingSettings, [
                'theme_mode' => $validatedData['theme_mode']
            ]);

            $user->save();

            Log::info('Theme mode updated', [
                'user_id' => $user->id,
                'theme_mode' => $validatedData['theme_mode']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Theme mode updated successfully.',
                'theme_mode' => $validatedData['theme_mode']
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating theme mode', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update theme mode.'
            ], 500);
        }
    }
}
