<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.settings');
    }

    public function update(Request $request)
    {
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
        ];

        $settingsToSave = $validatedData;

        foreach ($checkboxFields as $field) {
            $settingsToSave[$field] = $request->has($field);
        }

        // Get existing settings or create empty array
        $existingSettings = $user->system_settings ?? [];

        // Merge with new settings
        $user->system_settings = array_merge($existingSettings, $settingsToSave);

        // Save the user
        $user->save();

        return back()->with('success', 'System settings updated successfully.');
    }
}
