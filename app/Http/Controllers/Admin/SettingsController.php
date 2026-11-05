<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\TaxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    public function index()
    {
        $tenant = app('currentTenant');

        return view('admin.settings.settings', ['tenant' => $tenant]);
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validatedData = $request->validate([
            'navigation_type' => 'required|in:sidebar,navbar,both',
            'theme_mode' => 'required|in:light,dark',
            'notification_duration' => 'required|integer|min:0',
            'auto_logout_time' => 'required|numeric|min:0',
            'data_refresh_rate' => 'required|integer|min:0',
            'system_language' => 'required|in:en,id',
            // Optional: present only when the Tax Region section renders
            // (starter plan hides it). The select itself lives outside
            // this form — a hidden field inside mirrors its value so
            // "Save Settings" persists the jurisdiction too.
            'tax_jurisdiction' => 'nullable|string|in:,ID',
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
            'sticky_navbar',
            'fluid_layout',
        ];

        $settingsToSave = $validatedData;

        // tax_jurisdiction is a tenant attribute, not a per-user system
        // setting — strip it before merging into system_settings and apply
        // it to the tenant with the same guard as the AJAX endpoint.
        $jurisdictionProvided = $request->has('tax_jurisdiction');
        $jurisdictionResult = null;
        if ($jurisdictionProvided) {
            $jurisdictionResult = $this->applyTaxJurisdiction($validatedData['tax_jurisdiction'] ?? null);
        }
        unset($settingsToSave['tax_jurisdiction']);

        foreach ($checkboxFields as $field) {
            $settingsToSave[$field] = $request->input($field) ? true : false;
        }

        // Get existing settings or create empty array
        $existingSettings = $user->system_settings ?? [];

        // Merge with new settings
        $user->system_settings = array_merge($existingSettings, $settingsToSave);

        Log::debug('Before save - show_theme_toggle:', [
            'value' => $user->system_settings['show_theme_toggle'] ?? 'not set',
            'user_id' => Auth::id(),
        ]);

        // Save the user
        $user->save();

        // Reload the user from the database to get the latest settings
        $user->refresh();

        Log::debug('After save (and refresh) - show_theme_toggle:', [
            'value' => $user->system_settings['show_theme_toggle'] ?? 'not set',
            'user_id' => Auth::id(),
        ]);

        Log::info('System settings updated successfully', [
            'user_id' => Auth::id(),
            'theme_mode' => $settingsToSave['theme_mode'],
            'settings' => $settingsToSave,
        ]);

        try {
            app(ActivityLogService::class)->logCreate(
                ActivityLog::MODULE_SETTINGS,
                'System settings updated'
            );
        } catch (\Throwable $e) {
            // never block settings save for logging failure
        }

        // Settings save proceeds even when the jurisdiction change was
        // refused by the safeguard — but the message must say so, otherwise
        // a success toast would claim a jurisdiction change that never
        // happened (the exact bug this path fixes).
        $message = 'System settings updated successfully.';
        if ($jurisdictionResult !== null && ! $jurisdictionResult['success']) {
            $message .= ' '.$jurisdictionResult['message'];
        } elseif ($jurisdictionResult['changed'] ?? false) {
            $message .= ' '.__('messages.tax_jurisdiction_saved');
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $message, 'settings' => $settingsToSave]);
        }

        return redirect()->route('admin.setting.index')->with('success', $message);
    }

    public function getSettings()
    {
        /** @var User $user */
        $user = Auth::user();

        $settings = $user->system_settings ?? [];

        // Ensure default values are present if not set
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
            'fluid_layout' => false,
        ];

        $settings = array_merge($defaults, $settings);

        return response()->json($settings);
    }

    public function updateTaxJurisdiction(Request $request)
    {
        $validated = $request->validate([
            'tax_jurisdiction' => 'nullable|string|max:10',
        ]);

        $result = $this->applyTaxJurisdiction($validated['tax_jurisdiction'] ?? null);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        }

        return redirect()->route('admin.setting.index')->with('success', $result['message']);
    }

    /**
     * Apply a tax-jurisdiction change, enforcing the ID→None safeguard.
     *
     * Shared by updateTaxJurisdiction() (the AJAX select) and update()
     * (the hidden field mirrored into the main settings form) so both
     * save paths apply identical rules — before this, only the select
     * handled the field and the main form silently dropped it.
     *
     * @return array{success: bool, changed: bool, message: string}
     */
    private function applyTaxJurisdiction(?string $jurisdiction): array
    {
        /** @var \App\Models\Tenant $tenant */
        $tenant = app('currentTenant');

        $jurisdiction = $jurisdiction ?: null;

        // Block switching away from ID if draft FakturPajak or unissued PPN sales exist
        if ($tenant->tax_jurisdiction === 'ID' && $jurisdiction !== 'ID') {
            $check = $tenant->canDisableIndonesianJurisdiction();
            if (! $check['safe']) {
                return ['success' => false, 'changed' => false, 'message' => $check['message']];
            }
        }

        // Idempotent: a Save Settings click that didn't touch the select
        // still posts the current value — skip the write and the extra
        // "jurisdiction saved" note in the toast.
        $changed = ($tenant->tax_jurisdiction ?? null) !== $jurisdiction;
        if ($changed) {
            $tenant->update(['tax_jurisdiction' => $jurisdiction]);
        }

        if ($jurisdiction === 'ID') {
            $created = app(TaxService::class)->ensureIndonesianDefaults($tenant);
            if (! empty($created)) {
                Log::info('Auto-created default Indonesian tax records', [
                    'tenant_id' => $tenant->id,
                    'taxes' => $created,
                ]);
            }
        }

        return ['success' => true, 'changed' => $changed, 'message' => __('messages.tax_jurisdiction_saved')];
    }

    public function updateThemeMode(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validatedData = $request->validate([
            'theme_mode' => 'required|in:light,dark',
        ]);

        $existingSettings = $user->system_settings ?? [];
        $user->system_settings = array_merge($existingSettings, [
            'theme_mode' => $validatedData['theme_mode'],
        ]);

        $user->save();

        Log::info('Theme mode updated', [
            'user_id' => Auth::id(),
            'theme_mode' => $validatedData['theme_mode'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Theme mode updated successfully.',
            'theme_mode' => $validatedData['theme_mode'],
        ]);
    }
}
