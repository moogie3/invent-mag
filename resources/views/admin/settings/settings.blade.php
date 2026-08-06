@extends('admin.layouts.base')

@section('title', __('messages.system_settings'))

@section('content')
    <div class="page-wrapper">
        <div class="page-body">
                <div class="{{ $containerClass ?? "container-xl" }}">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body d-flex justify-content-between align-items-center py-3">
                            <h2 class="mb-0"><i class="ti ti-settings me-2"></i>{{ __('messages.system_settings') }}</h2>
                            <a href="#" class="text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                                <i class="ti ti-help-circle fs-3"></i>
                            </a>
                        </div>
                        <hr class="my-0">
                        <div class="row g-0">
                            <div class="col-12 col-md-3 border-end">
                                @include('admin.layouts.menu')
                            </div>
                            <div
                                class="@if ((auth()->user()->system_settings['navigation_type'] ?? 'sidebar') === 'navbar') col-9 @else col-12 col-md-9 @endif d-flex flex-column">
                                <div class="card-body">
                                    <form id="systemSettingsForm" action="{{ route('admin.setting.update') }}"
                                        method="POST">
                                        @method('PUT')
                                        @csrf
                                        <!-- Interface Layout Settings -->
                                        <div class="settings-section mb-5">
                                            <div class="settings-section-header">
                                                <div class="settings-icon-wrapper">
                                                    <i class="ti ti-layout-2"></i>
                                                </div>
                                                <div class="settings-section-title">
                                                    <h3 class="mb-1">{{ __('messages.interface_layout') }}</h3>
                                                    <p class="text-muted mb-0 small">
                                                        {{ __('messages.customize_your_navigation_and_layout_preferences') }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label" for="navigation_type">{{ __('messages.navigation_type') }}</label>
                                                        <select name="navigation_type" id="navigation_type" class="form-control @error('navigation_type') is-invalid @enderror" required>
                                                            <option value="sidebar"
                                                                {{ old('navigation_type', auth()->user()->system_settings['navigation_type'] ?? 'sidebar') === 'sidebar' ? 'selected' : '' }}>
                                                                {{ __('messages.sidebar_navigation') }}
                                                            </option>
                                                            <option value="navbar"
                                                                {{ old('navigation_type', auth()->user()->system_settings['navigation_type'] ?? 'sidebar') === 'navbar' ? 'selected' : '' }}>
                                                                {{ __('messages.top_navigation_bar') }}
                                                            </option>
                                                            <option value="both"
                                                                {{ old('navigation_type', auth()->user()->system_settings['navigation_type'] ?? 'sidebar') === 'both' ? 'selected' : '' }}>
                                                                {{ __('messages.both') }}
                                                            </option>
                                                        </select>
                                                        @error('navigation_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>

                                                    <div class="col-md-6" id="sidebar-options-wrapper">
                                                        <div class="form-label">{{ __('messages.sidebar_options') }}</div>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="sidebar_lock"
                                                                {{ auth()->user()->system_settings['sidebar_lock'] ?? false ? 'checked' : '' }}>
                                                            <label
                                                                class="form-check-label">{{ __('messages.enable_sidebar_lock') }}</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6" id="navbar-options-wrapper">
                                                        <div class="form-label">{{ __('messages.navbar_options') }}</div>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="sticky_navbar"
                                                                {{ auth()->user()->system_settings['sticky_navbar'] ?? false ? 'checked' : '' }}>
                                                            <label
                                                                class="form-check-label">{{ __('messages.enable_sticky_navbar') }}</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Theme Settings -->
                                        <div class="settings-section mb-5">
                                            <div class="settings-section-header">
                                                <div class="settings-icon-wrapper">
                                                    <i class="ti ti-palette"></i>
                                                </div>
                                                <div class="settings-section-title">
                                                    <h3 class="mb-1">{{ __('messages.theme_configuration') }}</h3>
                                                    <p class="text-muted mb-0 small">
                                                        {{ __('messages.control_the_visual_appearance_and_theme_settings') }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label" for="theme_mode">{{ __('messages.theme_mode') }}</label>
                                                        <select name="theme_mode" id="theme_mode" class="form-control @error('theme_mode') is-invalid @enderror"
                                                            required>
                                                            <option value="light"
                                                                {{ old('theme_mode', auth()->user()->system_settings['theme_mode'] ?? 'light') === 'light' ? 'selected' : '' }}>
                                                                {{ __('messages.light_theme') }}
                                                            </option>
                                                            <option value="dark"
                                                                {{ old('theme_mode', auth()->user()->system_settings['theme_mode'] ?? 'light') === 'dark' ? 'selected' : '' }}>
                                                                {{ __('messages.dark_theme') }}
                                                            </option>

                                                        </select>
                                                        @error('theme_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-label">
                                                            {{ __('messages.theme_toggle_visibility') }}</div>
                                                        <div class="form-check form-switch">
                                                            <input type="hidden" name="show_theme_toggle" value="0">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="show_theme_toggle" id="showThemeToggleCheckbox"
                                                                value="1"
                                                                {{ auth()->user()->system_settings['show_theme_toggle'] ?? true ? 'checked' : '' }}>
                                                            <label
                                                                class="form-check-label">{{ __('messages.show_theme_toggle_button') }}</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Notification Settings -->
                                        <div class="settings-section mb-5">
                                            <div class="settings-section-header">
                                                <div class="settings-icon-wrapper">
                                                    <i class="ti ti-bell"></i>
                                                </div>
                                                <div class="settings-section-title">
                                                    <h3 class="mb-1">{{ __('messages.notifications_alerts') }}</h3>
                                                    <p class="text-muted mb-0 small">
                                                        {{ __('messages.manage_notification_preferences_and_alert_settings') }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="form-check form-switch mb-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="enable_sound_notifications"
                                                                {{ auth()->user()->system_settings['enable_sound_notifications'] ?? true ? 'checked' : '' }}>
                                                            <label
                                                                class="form-check-label">{{ __('messages.enable_sound_notifications') }}</label>
                                                        </div>
                                                        <div class="form-check form-switch mb-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="enable_browser_notifications"
                                                                {{ auth()->user()->system_settings['enable_browser_notifications'] ?? true ? 'checked' : '' }}>
                                                            <label
                                                                class="form-check-label">{{ __('messages.enable_browser_notifications') }}</label>
                                                        </div>
                                                        <div class="form-check form-switch mb-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="show_success_messages"
                                                                {{ auth()->user()->system_settings['show_success_messages'] ?? true ? 'checked' : '' }}>
                                                            <label
                                                                class="form-check-label">{{ __('messages.show_success_messages') }}</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label" for="notification_duration">
                                                            {{ __('messages.notification_duration_seconds') }}</label>
                                                        <select name="notification_duration" id="notification_duration" class="form-control @error('notification_duration') is-invalid @enderror"
                                                            required>
                                                            <option value="3"
                                                                {{ old('notification_duration', auth()->user()->system_settings['notification_duration'] ?? '5') == 3 ? 'selected' : '' }}>
                                                                {{ __('messages.3_seconds') }}</option>
                                                            <option value="5"
                                                                {{ old('notification_duration', auth()->user()->system_settings['notification_duration'] ?? '5') == 5 ? 'selected' : '' }}>
                                                                {{ __('messages.5_seconds') }}</option>
                                                            <option value="10"
                                                                {{ old('notification_duration', auth()->user()->system_settings['notification_duration'] ?? '5') == 10 ? 'selected' : '' }}>
                                                                {{ __('messages.10_seconds') }}</option>
                                                            <option value="0"
                                                                {{ old('notification_duration', auth()->user()->system_settings['notification_duration'] ?? '5') == 0 ? 'selected' : '' }}>
                                                                {{ __('messages.manual_dismiss') }}</option>
                                                        </select>
                                                        @error('notification_duration')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Session & Security Settings -->
                                        <div class="settings-section mb-5">
                                            <div class="settings-section-header">
                                                <div class="settings-icon-wrapper">
                                                    <i class="ti ti-shield-lock"></i>
                                                </div>
                                                <div class="settings-section-title">
                                                    <h3 class="mb-1">{{ __('messages.session_security') }}</h3>
                                                    <p class="text-muted mb-0 small">
                                                        {{ __('messages.configure_session_timeout_and_security_preferences') }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label" for="auto_logout_time">
                                                            {{ __('messages.auto_logout_time_minutes') }}</label>
                                                        <select name="auto_logout_time" id="auto_logout_time" class="form-control @error('auto_logout_time') is-invalid @enderror" required>
                                                            <option value="0"
                                                                {{ old('auto_logout_time', auth()->user()->system_settings['auto_logout_time'] ?? '60') == 0 ? 'selected' : '' }}>
                                                                {{ __('messages.disabled') }}</option>
                                                            <option value="15"
                                                                {{ old('auto_logout_time', auth()->user()->system_settings['auto_logout_time'] ?? '60') == 15 ? 'selected' : '' }}>
                                                                {{ __('messages.15_minutes') }}</option>
                                                            <option value="30"
                                                                {{ old('auto_logout_time', auth()->user()->system_settings['auto_logout_time'] ?? '60') == 30 ? 'selected' : '' }}>
                                                                {{ __('messages.30_minutes') }}</option>
                                                            <option value="60"
                                                                {{ old('auto_logout_time', auth()->user()->system_settings['auto_logout_time'] ?? '60') == 60 ? 'selected' : '' }}>
                                                                {{ __('messages.1_hour') }}</option>
                                                            <option value="120"
                                                                {{ old('auto_logout_time', auth()->user()->system_settings['auto_logout_time'] ?? '60') == 120 ? 'selected' : '' }}>
                                                                {{ __('messages.2_hours') }}</option>
                                                        </select>
                                                        @error('auto_logout_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="remember_last_page"
                                                                {{ auth()->user()->system_settings['remember_last_page'] ?? true ? 'checked' : '' }}>
                                                            <label
                                                                class="form-check-label">{{ __('messages.remember_last_visited_page') }}</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Performance Settings -->
                                        <div class="settings-section mb-5">
                                            <div class="settings-section-header">
                                                <div class="settings-icon-wrapper">
                                                    <i class="ti ti-rocket"></i>
                                                </div>
                                                <div class="settings-section-title">
                                                    <h3 class="mb-1">{{ __('messages.performance') }}</h3>
                                                    <p class="text-muted mb-0 small">
                                                        {{ __('messages.optimize_system_performance_and_resource_usage') }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="form-check form-switch mb-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="enable_animations"
                                                                {{ auth()->user()->system_settings['enable_animations'] ?? true ? 'checked' : '' }}>
                                                            <label
                                                                class="form-check-label">{{ __('messages.enable_ui_animations') }}</label>
                                                        </div>
                                                        <div class="form-check form-switch mb-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="lazy_load_images"
                                                                {{ auth()->user()->system_settings['lazy_load_images'] ?? true ? 'checked' : '' }}>
                                                            <label
                                                                class="form-check-label">{{ __('messages.lazy_load_images') }}</label>
                                                        </div>
                                                        <div class="form-check form-switch mb-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="fluid_layout"
                                                                {{ auth()->user()->system_settings['fluid_layout'] ?? false ? 'checked' : '' }}>
                                                            <label
                                                                class="form-check-label">{{ __('messages.enable_fluid_layout') }}</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label" for="data_refresh_rate">
                                                            {{ __('messages.data_refresh_rate_seconds') }}</label>
                                                        <select name="data_refresh_rate" id="data_refresh_rate" class="form-control @error('data_refresh_rate') is-invalid @enderror" required>
                                                            <option value="0"
                                                                {{ old('data_refresh_rate', auth()->user()->system_settings['data_refresh_rate'] ?? '30') == 0 ? 'selected' : '' }}>
                                                                {{ __('messages.manual_only') }}</option>
                                                            <option value="15"
                                                                {{ old('data_refresh_rate', auth()->user()->system_settings['data_refresh_rate'] ?? '30') == 15 ? 'selected' : '' }}>
                                                                {{ __('messages.15_seconds') }}</option>
                                                            <option value="30"
                                                                {{ old('data_refresh_rate', auth()->user()->system_settings['data_refresh_rate'] ?? '30') == 30 ? 'selected' : '' }}>
                                                                {{ __('messages.30_seconds') }}</option>
                                                            <option value="60"
                                                                {{ old('data_refresh_rate', auth()->user()->system_settings['data_refresh_rate'] ?? '30') == 60 ? 'selected' : '' }}>
                                                                {{ __('messages.1_minute') }}</option>
                                                            <option value="300"
                                                                {{ old('data_refresh_rate', auth()->user()->system_settings['data_refresh_rate'] ?? '30') == 300 ? 'selected' : '' }}>
                                                                {{ __('messages.5_minutes') }}</option>
                                                        </select>
                                                        @error('data_refresh_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Language & Localization -->
                                        <div class="settings-section mb-5">
                                            <div class="settings-section-header">
                                                <div class="settings-icon-wrapper">
                                                    <i class="ti ti-language"></i>
                                                </div>
                                                <div class="settings-section-title">
                                                    <h3 class="mb-1">{{ __('messages.language_localization') }}</h3>
                                                    <p class="text-muted mb-0 small">
                                                        {{ __('messages.set_your_preferred_language_and_regional_settings') }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label" for="system_language">{{ __('messages.system_language') }}</label>
                                                        <select name="system_language" id="system_language" class="form-control @error('system_language') is-invalid @enderror" required>
                                                            <option value="en"
                                                                {{ old('system_language', auth()->user()->system_settings['system_language'] ?? 'en') === 'en' ? 'selected' : '' }}>
                                                                {{ __('messages.english') }}</option>
                                                            <option value="id"
                                                                {{ old('system_language', auth()->user()->system_settings['system_language'] ?? 'en') === 'id' ? 'selected' : '' }}>
                                                                {{ __('messages.bahasa_indonesia') }}</option>
                                                        </select>
                                                        @error('system_language')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        

                                        <!-- Advanced Settings -->
                                        <div class="settings-section mb-4">
                                            <div class="settings-section-header">
                                                <div class="settings-icon-wrapper">
                                                    <i class="ti ti-adjustments"></i>
                                                </div>
                                                <div class="settings-section-title">
                                                    <h3 class="mb-1">{{ __('messages.advanced') }}</h3>
                                                    <p class="text-muted mb-0 small">
                                                        {{ __('messages.additional_options_for_power_users_and_debugging') }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="form-check form-switch mb-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="enable_debug_mode"
                                                                {{ auth()->user()->system_settings['enable_debug_mode'] ?? false ? 'checked' : '' }}>
                                                            <label
                                                                class="form-check-label">{{ __('messages.enable_debug_information') }}</label>
                                                        </div>
                                                        <div class="form-check form-switch mb-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="enable_keyboard_shortcuts"
                                                                {{ auth()->user()->system_settings['enable_keyboard_shortcuts'] ?? true ? 'checked' : '' }}>
                                                            <label
                                                                class="form-check-label">{{ __('messages.enable_keyboard_shortcuts') }}</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-check form-switch mb-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="show_tooltips"
                                                                {{ auth()->user()->system_settings['show_tooltips'] ?? true ? 'checked' : '' }}>
                                                            <label
                                                                class="form-check-label">{{ __('messages.show_help_tooltips') }}</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <button type="button" class="btn btn-info"
                                                            id="showShortcutsModalBtn">
                                                            <i
                                                                class="ti ti-keyboard me-2"></i>{{ __('messages.view_shortcuts') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </form>

                                    <!-- Tax Region (outside main form — saved independently via AJAX) -->
                                    @if(($tenant->plan->slug ?? '') !== 'starter')
                                    <div class="settings-section mb-5">
                                        <div class="settings-section-header">
                                            <div class="settings-icon-wrapper">
                                                <i class="ti ti-world"></i>
                                            </div>
                                            <div class="settings-section-title">
                                                <h3 class="mb-1">{{ __('messages.tax_region') }}</h3>
                                                <p class="text-muted mb-0 small">
                                                    {{ __('messages.tax_region_description') }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="settings-section-content">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="form-label">{{ __('messages.tax_jurisdiction') }}</div>
                                                    <select id="taxJurisdictionSelect" class="form-control">
                                                        <option value="" {{ ($tenant->tax_jurisdiction ?? '') === '' ? 'selected' : '' }}>
                                                            {{ __('messages.tax_jurisdiction_none') }}
                                                        </option>
                                                        <option value="ID" {{ ($tenant->tax_jurisdiction ?? '') === 'ID' ? 'selected' : '' }}>
                                                            {{ __('messages.tax_jurisdiction_id') }}
                                                        </option>
                                                    </select>
                                                    <div class="form-text">{{ __('messages.tax_jurisdiction_hint') }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                <div class="card-footer bg-transparent mt-auto">
                                    <div class="btn-list justify-content-end">
                                        <button type="button" class="btn btn-secondary"
                                            id="resetButton">{{ __('messages.reset_to_defaults') }}</button>
                                        <button type="submit" form="systemSettingsForm"
                                            class="btn btn-primary">{{ __('messages.save_settings') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function() {
    var taxJurisdictionSelect = document.getElementById('taxJurisdictionSelect');
    if (taxJurisdictionSelect) {
        taxJurisdictionSelect.addEventListener('change', function() {
            var jurisdiction = this.value;
            var select = this;
            fetch('{{ route("admin.setting.update-tax-jurisdiction") }}', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ tax_jurisdiction: jurisdiction })
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    return { ok: response.ok, status: response.status, data: data };
                }).catch(function() {
                    return { ok: response.ok, status: response.status, data: null };
                });
            })
            .then(function(result) {
                if (result.data && result.data.success) {
                    window.location.reload();
                    return;
                }
                if (result.status === 419 || result.status === 401) {
                    window.location.reload();
                    return;
                }
                alert((result.data && result.data.message) || 'Failed to update tax jurisdiction.');
            })
            .catch(function(err) { alert('An error occurred while updating tax jurisdiction.'); });
        });
    }
});
</script>
