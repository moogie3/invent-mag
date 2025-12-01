<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'navigation_type' => 'required|in:sidebar,navbar,both',
            'theme_mode' => 'required|in:light,dark',
            'notification_duration' => 'required|integer|min:0',
            'auto_logout_time' => 'required|numeric|min:0',
            'data_refresh_rate' => 'required|integer|min:0',
            'system_language' => 'required|in:en,id',
            'sidebar_lock' => 'boolean',
            'show_theme_toggle' => 'boolean',
            'enable_sound_notifications' => 'boolean',
            'enable_browser_notifications' => 'boolean',
            'show_success_messages' => 'boolean',
            'remember_last_page' => 'boolean',
            'enable_animations' => 'boolean',
            'lazy_load_images' => 'boolean',
            'enable_debug_mode' => 'boolean',
            'enable_keyboard_shortcuts' => 'boolean',
            'show_tooltips' => 'boolean',
            'compact_mode' => 'boolean',
            'sticky_navbar' => 'boolean',
        ];
    }
}
