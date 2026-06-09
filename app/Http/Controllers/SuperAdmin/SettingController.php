<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    public function index()
    {
        $appSettings = [
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'mail_driver' => config('mail.default'),
            'mail_from_address' => config('mail.from.address'),
            'mail_from_name' => config('mail.from.name'),
        ];

        return view('superadmin.settings.index', compact('appSettings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'app_debug' => 'boolean',
        ]);

        $envFile = base_path('.env');
        $env = file_get_contents($envFile);

        $env = preg_replace('/^APP_NAME=.*/m', 'APP_NAME="' . $validated['app_name'] . '"', $env);
        $env = preg_replace('/^APP_URL=.*/m', 'APP_URL=' . $validated['app_url'], $env);
        $env = preg_replace('/^APP_DEBUG=.*/m', 'APP_DEBUG=' . ($request->boolean('app_debug') ? 'true' : 'false'), $env);

        if (file_put_contents($envFile, $env) === false) {
            return back()->with('error', 'Could not write .env file.');
        }

        Artisan::call('config:clear');

        return redirect()->route('superadmin.settings.index')->with('success', 'Application settings updated successfully.');
    }

    public function preferences()
    {
        /** @var \App\Models\SuperAdmin $user */
        $user = Auth::guard('super_admin')->user();

        $settings = $user->system_settings ?? [];

        $defaults = [
            'theme_mode' => 'light',
            'show_theme_toggle' => true,
            'enable_sound_notifications' => true,
            'enable_browser_notifications' => true,
            'show_success_messages' => true,
            'notification_duration' => 5,
            'auto_logout_time' => 60,
            'remember_last_page' => true,
            'enable_animations' => true,
            'lazy_load_images' => true,
            'fluid_layout' => false,
            'data_refresh_rate' => 30,
            'system_language' => 'en',
            'sidebar_lock' => false,
            'sticky_navbar' => false,
            'enable_debug_mode' => false,
            'enable_keyboard_shortcuts' => true,
            'show_tooltips' => true,
        ];

        $userSettings = array_merge($defaults, $settings);

        return view('superadmin.settings.preferences', compact('userSettings'));
    }

    public function updatePreferences(Request $request)
    {
        /** @var \App\Models\SuperAdmin $user */
        $user = Auth::guard('super_admin')->user();

        $validatedData = $request->validate([
            'theme_mode' => 'required|in:light,dark',
            'notification_duration' => 'required|integer|min:0',
            'auto_logout_time' => 'required|numeric|min:0',
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
            'fluid_layout',
            'sticky_navbar',
            'enable_debug_mode',
            'enable_keyboard_shortcuts',
            'show_tooltips',
        ];

        $settingsToSave = $validatedData;

        foreach ($checkboxFields as $field) {
            $settingsToSave[$field] = $request->input($field) ? true : false;
        }

        $existingSettings = $user->system_settings ?? [];
        $user->system_settings = array_merge($existingSettings, $settingsToSave);
        $user->save();

        Log::info('Super admin preferences updated', [
            'user_id' => $user->id,
            'settings' => $settingsToSave,
        ]);

        return redirect()->route('superadmin.settings.preferences')->with('success', 'Preferences saved successfully.');
    }

    public function updateThemeMode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme_mode' => 'required|in:light,dark',
        ]);

        /** @var \App\Models\SuperAdmin $user */
        $user = Auth::guard('super_admin')->user();

        $existingSettings = $user->system_settings ?? [];
        $user->system_settings = array_merge($existingSettings, [
            'theme_mode' => $validated['theme_mode'],
        ]);

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Theme mode updated.',
            'theme_mode' => $validated['theme_mode'],
        ]);
    }
}
