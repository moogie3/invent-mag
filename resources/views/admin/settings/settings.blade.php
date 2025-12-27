@extends('admin.layouts.base')

@section('title', __('messages.system_settings'))

@section('content')
    <div class="page-wrapper">
        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    <div class="card">
                        <div class="card-body">
                            <h2><i class="ti ti-settings me-2"></i>{{ __('messages.system_settings') }}</h2>
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
                                                        <div class="form-label">{{ __('messages.navigation_type') }}</div>
                                                        <select name="navigation_type" class="form-control" required>
                                                            <option value="sidebar"
                                                                {{ (auth()->user()->system_settings['navigation_type'] ?? 'sidebar') === 'sidebar' ? 'selected' : '' }}>
                                                                {{ __('messages.sidebar_navigation') }}
                                                            </option>
                                                            <option value="navbar"
                                                                {{ (auth()->user()->system_settings['navigation_type'] ?? 'sidebar') === 'navbar' ? 'selected' : '' }}>
                                                                {{ __('messages.top_navigation_bar') }}
                                                            </option>
                                                            <option value="both"
                                                                {{ (auth()->user()->system_settings['navigation_type'] ?? 'sidebar') === 'both' ? 'selected' : '' }}>
                                                                {{ __('messages.both') }}
                                                            </option>
                                                        </select>
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
                                                        <div class="form-label">{{ __('messages.theme_mode') }}</div>
                                                        <select name="theme_mode" id="themeModeSelect" class="form-control"
                                                            required>
                                                            <option value="light"
                                                                {{ (auth()->user()->system_settings['theme_mode'] ?? 'light') === 'light' ? 'selected' : '' }}>
                                                                {{ __('messages.light_theme') }}
                                                            </option>
                                                            <option value="dark"
                                                                {{ (auth()->user()->system_settings['theme_mode'] ?? 'light') === 'dark' ? 'selected' : '' }}>
                                                                {{ __('messages.dark_theme') }}
                                                            </option>

                                                        </select>
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
                                                        <div class="form-label">
                                                            {{ __('messages.notification_duration_seconds') }}</div>
                                                        <select name="notification_duration" class="form-control"
                                                            required>
                                                            <option value="3"
                                                                {{ (auth()->user()->system_settings['notification_duration'] ?? '5') === '3' ? 'selected' : '' }}>
                                                                {{ __('messages.3_seconds') }}</option>
                                                            <option value="5"
                                                                {{ (auth()->user()->system_settings['notification_duration'] ?? '5') === '5' ? 'selected' : '' }}>
                                                                {{ __('messages.5_seconds') }}</option>
                                                            <option value="10"
                                                                {{ (auth()->user()->system_settings['notification_duration'] ?? '5') === '10' ? 'selected' : '' }}>
                                                                {{ __('messages.10_seconds') }}</option>
                                                            <option value="0"
                                                                {{ (auth()->user()->system_settings['notification_duration'] ?? '5') === '0' ? 'selected' : '' }}>
                                                                {{ __('messages.manual_dismiss') }}</option>
                                                        </select>
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
                                                        <div class="form-label">
                                                            {{ __('messages.auto_logout_time_minutes') }}</div>
                                                        <select name="auto_logout_time" class="form-control" required>
                                                            <option value="0"
                                                                {{ (auth()->user()->system_settings['auto_logout_time'] ?? '60') === '0' ? 'selected' : '' }}>
                                                                {{ __('messages.disabled') }}</option>
                                                            <option value="15"
                                                                {{ (auth()->user()->system_settings['auto_logout_time'] ?? '60') === '15' ? 'selected' : '' }}>
                                                                {{ __('messages.15_minutes') }}</option>
                                                            <option value="30"
                                                                {{ (auth()->user()->system_settings['auto_logout_time'] ?? '60') === '30' ? 'selected' : '' }}>
                                                                {{ __('messages.30_minutes') }}</option>
                                                            <option value="60"
                                                                {{ (auth()->user()->system_settings['auto_logout_time'] ?? '60') === '60' ? 'selected' : '' }}>
                                                                {{ __('messages.1_hour') }}</option>
                                                            <option value="120"
                                                                {{ (auth()->user()->system_settings['auto_logout_time'] ?? '60') === '120' ? 'selected' : '' }}>
                                                                {{ __('messages.2_hours') }}</option>
                                                        </select>
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
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-label">
                                                            {{ __('messages.data_refresh_rate_seconds') }}</div>
                                                        <select name="data_refresh_rate" class="form-control" required>
                                                            <option value="0"
                                                                {{ (auth()->user()->system_settings['data_refresh_rate'] ?? '30') === '0' ? 'selected' : '' }}>
                                                                {{ __('messages.manual_only') }}</option>
                                                            <option value="15"
                                                                {{ (auth()->user()->system_settings['data_refresh_rate'] ?? '30') === '15' ? 'selected' : '' }}>
                                                                {{ __('messages.15_seconds') }}</option>
                                                            <option value="30"
                                                                {{ (auth()->user()->system_settings['data_refresh_rate'] ?? '30') === '30' ? 'selected' : '' }}>
                                                                {{ __('messages.30_seconds') }}</option>
                                                            <option value="60"
                                                                {{ (auth()->user()->system_settings['data_refresh_rate'] ?? '30') === '60' ? 'selected' : '' }}>
                                                                {{ __('messages.1_minute') }}</option>
                                                            <option value="300"
                                                                {{ (auth()->user()->system_settings['data_refresh_rate'] ?? '30') === '300' ? 'selected' : '' }}>
                                                                {{ __('messages.5_minutes') }}</option>
                                                        </select>
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
                                                        <div class="form-label">{{ __('messages.system_language') }}</div>
                                                        <select name="system_language" class="form-control" required>
                                                            <option value="en"
                                                                {{ (auth()->user()->system_settings['system_language'] ?? 'en') === 'en' ? 'selected' : '' }}>
                                                                {{ __('messages.english') }}</option>
                                                            <option value="id"
                                                                {{ (auth()->user()->system_settings['system_language'] ?? 'en') === 'id' ? 'selected' : '' }}>
                                                                {{ __('messages.bahasa_indonesia') }}</option>
                                                        </select>
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
                                                        <div class="form-check form-switch mb-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="compact_mode"
                                                                {{ auth()->user()->system_settings['compact_mode'] ?? false ? 'checked' : '' }}>
                                                            <label
                                                                class="form-check-label">{{ __('messages.compact_view_mode') }}</label>
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
