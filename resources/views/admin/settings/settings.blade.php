@extends('admin.layouts.base')

@section('title', 'System Settings')

@section('content')
    <div class="page-wrapper">
        <div class="page-header">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            Overview
                        </div>
                        <h2 class="page-title">
                            System Settings
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    <div class="card">
                        <div class="row g-0">
                            <div class="col-12 col-md-3 border-end">
                                @include('admin.layouts.menu')
                            </div>
                            <div class="col-12 col-md-9 d-flex flex-column">
                                <div class="card-body">
                                    <h2 class="mb-4">System Configuration</h2>

                                    <form id="systemSettingsForm" action="{{ route('admin.setting.update') }}"
                                        method="POST">
                                        @method('PUT')
                                        @csrf

                                        <!-- Interface Layout Settings -->
                                        <h3 class="card-title">Interface Layout</h3>
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <div class="form-label">Navigation Type</div>
                                                <select name="navigation_type" class="form-control" required>
                                                    <option value="sidebar"
                                                        {{ (auth()->user()->system_settings['navigation_type'] ?? 'sidebar') === 'sidebar' ? 'selected' : '' }}>
                                                        Sidebar Navigation
                                                    </option>
                                                    <option value="navbar"
                                                        {{ (auth()->user()->system_settings['navigation_type'] ?? 'sidebar') === 'navbar' ? 'selected' : '' }}>
                                                        Top Navigation Bar
                                                    </option>
                                                    <option value="both"
                                                        {{ (auth()->user()->system_settings['navigation_type'] ?? 'sidebar') === 'both' ? 'selected' : '' }}>
                                                        Both
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-label">Sidebar Options</div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="sidebar_lock"
                                                        {{ auth()->user()->system_settings['sidebar_lock'] ?? false ? 'checked' : '' }}>
                                                    <label class="form-check-label">Enable Sidebar Lock Button</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-label">Navbar Options</div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="sticky_navbar"
                                                        {{ auth()->user()->system_settings['sticky_navbar'] ?? false ? 'checked' : '' }}>
                                                    <label class="form-check-label">Enable Sticky Navbar</label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Theme Settings -->
                                        <h3 class="card-title">Theme Configuration</h3>
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <div class="form-label">Theme Mode</div>
                                                <select name="theme_mode" class="form-control" required>
                                                    <option value="light"
                                                        {{ (auth()->user()->system_settings['theme_mode'] ?? 'light') === 'light' ? 'selected' : '' }}>
                                                        Light Theme
                                                    </option>
                                                    <option value="dark"
                                                        {{ (auth()->user()->system_settings['theme_mode'] ?? 'light') === 'dark' ? 'selected' : '' }}>
                                                        Dark Theme
                                                    </option>
                                                    <option value="auto"
                                                        {{ (auth()->user()->system_settings['theme_mode'] ?? 'light') === 'auto' ? 'selected' : '' }}>
                                                        Auto (System Preference)
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-label">Theme Toggle Visibility</div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="show_theme_toggle"
                                                        {{ auth()->user()->system_settings['show_theme_toggle'] ?? true ? 'checked' : '' }}>
                                                    <label class="form-check-label">Show Theme Toggle Button</label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Notification Settings -->
                                        <h3 class="card-title">Notifications & Alerts</h3>
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="enable_sound_notifications"
                                                        {{ auth()->user()->system_settings['enable_sound_notifications'] ?? true ? 'checked' : '' }}>
                                                    <label class="form-check-label">Enable Sound Notifications</label>
                                                </div>
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="enable_browser_notifications"
                                                        {{ auth()->user()->system_settings['enable_browser_notifications'] ?? true ? 'checked' : '' }}>
                                                    <label class="form-check-label">Enable Browser Notifications</label>
                                                </div>
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="show_success_messages"
                                                        {{ auth()->user()->system_settings['show_success_messages'] ?? true ? 'checked' : '' }}>
                                                    <label class="form-check-label">Show Success Messages</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-label">Notification Duration (seconds)</div>
                                                <select name="notification_duration" class="form-control" required>
                                                    <option value="3"
                                                        {{ (auth()->user()->system_settings['notification_duration'] ?? '5') === '3' ? 'selected' : '' }}>
                                                        3 seconds</option>
                                                    <option value="5"
                                                        {{ (auth()->user()->system_settings['notification_duration'] ?? '5') === '5' ? 'selected' : '' }}>
                                                        5 seconds</option>
                                                    <option value="10"
                                                        {{ (auth()->user()->system_settings['notification_duration'] ?? '5') === '10' ? 'selected' : '' }}>
                                                        10 seconds</option>
                                                    <option value="0"
                                                        {{ (auth()->user()->system_settings['notification_duration'] ?? '5') === '0' ? 'selected' : '' }}>
                                                        Manual Dismiss</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Session & Security Settings -->
                                        <h3 class="card-title">Session & Security</h3>
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <div class="form-label">Auto-logout Time (minutes)</div>
                                                <select name="auto_logout_time" class="form-control" required>
                                                    <option value="0"
                                                        {{ (auth()->user()->system_settings['auto_logout_time'] ?? '60') === '0' ? 'selected' : '' }}>
                                                        Disabled</option>
                                                    <option value="15"
                                                        {{ (auth()->user()->system_settings['auto_logout_time'] ?? '60') === '15' ? 'selected' : '' }}>
                                                        15 minutes</option>
                                                    <option value="30"
                                                        {{ (auth()->user()->system_settings['auto_logout_time'] ?? '60') === '30' ? 'selected' : '' }}>
                                                        30 minutes</option>
                                                    <option value="60"
                                                        {{ (auth()->user()->system_settings['auto_logout_time'] ?? '60') === '60' ? 'selected' : '' }}>
                                                        1 hour</option>
                                                    <option value="120"
                                                        {{ (auth()->user()->system_settings['auto_logout_time'] ?? '60') === '120' ? 'selected' : '' }}>
                                                        2 hours</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="remember_last_page"
                                                        {{ auth()->user()->system_settings['remember_last_page'] ?? true ? 'checked' : '' }}>
                                                    <label class="form-check-label">Remember Last Visited Page</label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Performance Settings -->
                                        <h3 class="card-title">Performance</h3>
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="enable_animations"
                                                        {{ auth()->user()->system_settings['enable_animations'] ?? true ? 'checked' : '' }}>
                                                    <label class="form-check-label">Enable UI Animations</label>
                                                </div>
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="lazy_load_images"
                                                        {{ auth()->user()->system_settings['lazy_load_images'] ?? true ? 'checked' : '' }}>
                                                    <label class="form-check-label">Lazy Load Images</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-label">Data Refresh Rate (seconds)</div>
                                                <select name="data_refresh_rate" class="form-control" required>
                                                    <option value="0"
                                                        {{ (auth()->user()->system_settings['data_refresh_rate'] ?? '30') === '0' ? 'selected' : '' }}>
                                                        Manual Only</option>
                                                    <option value="15"
                                                        {{ (auth()->user()->system_settings['data_refresh_rate'] ?? '30') === '15' ? 'selected' : '' }}>
                                                        15 seconds</option>
                                                    <option value="30"
                                                        {{ (auth()->user()->system_settings['data_refresh_rate'] ?? '30') === '30' ? 'selected' : '' }}>
                                                        30 seconds</option>
                                                    <option value="60"
                                                        {{ (auth()->user()->system_settings['data_refresh_rate'] ?? '30') === '60' ? 'selected' : '' }}>
                                                        1 minute</option>
                                                    <option value="300"
                                                        {{ (auth()->user()->system_settings['data_refresh_rate'] ?? '30') === '300' ? 'selected' : '' }}>
                                                        5 minutes</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Language & Localization -->
                                        <h3 class="card-title">Language & Localization</h3>
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-4">
                                                <div class="form-label">System Language</div>
                                                <select name="system_language" class="form-control" required>
                                                    <option value="en"
                                                        {{ (auth()->user()->system_settings['system_language'] ?? 'en') === 'en' ? 'selected' : '' }}>
                                                        English</option>
                                                    <option value="id"
                                                        {{ (auth()->user()->system_settings['system_language'] ?? 'en') === 'id' ? 'selected' : '' }}>
                                                        Bahasa Indonesia</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Advanced Settings -->
                                        <h3 class="card-title">Advanced</h3>
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="enable_debug_mode"
                                                        {{ auth()->user()->system_settings['enable_debug_mode'] ?? false ? 'checked' : '' }}>
                                                    <label class="form-check-label">Enable Debug Information</label>
                                                </div>
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="enable_keyboard_shortcuts"
                                                        {{ auth()->user()->system_settings['enable_keyboard_shortcuts'] ?? true ? 'checked' : '' }}>
                                                    <label class="form-check-label">Enable Keyboard Shortcuts</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" name="show_tooltips"
                                                        {{ auth()->user()->system_settings['show_tooltips'] ?? true ? 'checked' : '' }}>
                                                    <label class="form-check-label">Show Help Tooltips</label>
                                                </div>
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" name="compact_mode"
                                                        {{ auth()->user()->system_settings['compact_mode'] ?? false ? 'checked' : '' }}>
                                                    <label class="form-check-label">Compact View Mode</label>
                                                </div>
                                            </div>
                                        </div>

                                    </form>
                                </div>
                                <div class="card-footer bg-transparent mt-auto">
                                    <div class="btn-list justify-content-end">
                                        <button type="button" class="btn btn-secondary"
                                            onclick="resetToDefaults()">Reset to Defaults</button>
                                        <button type="submit" form="systemSettingsForm" class="btn btn-primary">Save
                                            Settings</button>
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
