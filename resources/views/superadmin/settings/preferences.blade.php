@extends('superadmin.layouts.base')
@section('title', 'Preferences')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-adjustments"></i> Configuration</div>
                    <h2 class="page-title">System Preferences</h2>
                    <div class="text-muted mt-1">Customize your interface, theme, notifications and more.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <h2 class="mb-0"><i class="ti ti-settings me-2"></i>System Settings</h2>
                </div>
                <hr class="my-0">

                <div class="row g-0">
                    <div class="col-12 col-md-3 border-end">
                        <div class="card-body">
                            <h3 class="header"><i class="ti ti-menu-2 me-2"></i>Setting List</h3>
                            <hr class="my-3">
                            <div class="list-group list-group-transparent">
                                <a href="{{ route('superadmin.settings.index') }}"
                                    class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('superadmin.settings.index') ? 'active' : '' }}">
                                    <i class="ti ti-settings fs-2 me-2"></i> Application
                                </a>
                                <a href="{{ route('superadmin.settings.preferences') }}"
                                    class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('superadmin.settings.preferences') ? 'active' : '' }}">
                                    <i class="ti ti-adjustments fs-2 me-2"></i> Preferences
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-9 d-flex flex-column">
                        <form method="POST" action="{{ route('superadmin.settings.preferences.update') }}">
                            @csrf

                            <div class="card-body">

                                {{-- Interface Layout --}}
                                <div class="settings-section-header">
                                    <div class="settings-icon-wrapper">
                                        <i class="ti ti-layout-2"></i>
                                    </div>
                                    <div class="settings-section-title">
                                        <h3 class="mb-1">Interface Layout</h3>
                                        <p class="text-muted mb-0 small">Customize your navigation and layout preferences.</p>
                                    </div>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="sidebar_lock" value="1" {{ $userSettings['sidebar_lock'] ? 'checked' : '' }}>
                                            <label class="form-check-label">Enable Sidebar Lock</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="sticky_navbar" value="1" {{ $userSettings['sticky_navbar'] ? 'checked' : '' }}>
                                            <label class="form-check-label">Enable Sticky Navbar</label>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                {{-- Theme --}}
                                <div class="settings-section-header mt-4">
                                    <div class="settings-icon-wrapper">
                                        <i class="ti ti-palette"></i>
                                    </div>
                                    <div class="settings-section-title">
                                        <h3 class="mb-1">Theme Configuration</h3>
                                        <p class="text-muted mb-0 small">Control the visual appearance and theme settings.</p>
                                    </div>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <div class="form-label">Theme Mode</div>
                                        <select name="theme_mode" class="form-control" required>
                                            <option value="light" {{ $userSettings['theme_mode'] === 'light' ? 'selected' : '' }}>Light Theme</option>
                                            <option value="dark" {{ $userSettings['theme_mode'] === 'dark' ? 'selected' : '' }}>Dark Theme</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-label">Theme Toggle Visibility</div>
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="show_theme_toggle" value="0">
                                            <input class="form-check-input" type="checkbox" name="show_theme_toggle" value="1" {{ $userSettings['show_theme_toggle'] ? 'checked' : '' }}>
                                            <label class="form-check-label">Show theme toggle button</label>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                {{-- Notifications --}}
                                <div class="settings-section-header mt-4">
                                    <div class="settings-icon-wrapper">
                                        <i class="ti ti-bell"></i>
                                    </div>
                                    <div class="settings-section-title">
                                        <h3 class="mb-1">Notifications & Alerts</h3>
                                        <p class="text-muted mb-0 small">Manage notification preferences and alert settings.</p>
                                    </div>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="enable_sound_notifications" value="1" {{ $userSettings['enable_sound_notifications'] ? 'checked' : '' }}>
                                            <label class="form-check-label">Enable Sound Notifications</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="enable_browser_notifications" value="1" {{ $userSettings['enable_browser_notifications'] ? 'checked' : '' }}>
                                            <label class="form-check-label">Enable Browser Notifications</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="show_success_messages" value="1" {{ $userSettings['show_success_messages'] ? 'checked' : '' }}>
                                            <label class="form-check-label">Show Success Messages</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-label">Notification Duration (seconds)</div>
                                        <select name="notification_duration" class="form-control" required>
                                            <option value="3" {{ $userSettings['notification_duration'] == 3 ? 'selected' : '' }}>3 seconds</option>
                                            <option value="5" {{ $userSettings['notification_duration'] == 5 ? 'selected' : '' }}>5 seconds</option>
                                            <option value="10" {{ $userSettings['notification_duration'] == 10 ? 'selected' : '' }}>10 seconds</option>
                                            <option value="0" {{ $userSettings['notification_duration'] == 0 ? 'selected' : '' }}>Manual dismiss</option>
                                        </select>
                                    </div>
                                </div>

                                <hr>

                                {{-- Session & Security --}}
                                <div class="settings-section-header mt-4">
                                    <div class="settings-icon-wrapper">
                                        <i class="ti ti-shield-lock"></i>
                                    </div>
                                    <div class="settings-section-title">
                                        <h3 class="mb-1">Session & Security</h3>
                                        <p class="text-muted mb-0 small">Configure session timeout and security preferences.</p>
                                    </div>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <div class="form-label">Auto Logout Time (minutes)</div>
                                        <select name="auto_logout_time" class="form-control" required>
                                            <option value="0" {{ $userSettings['auto_logout_time'] == 0 ? 'selected' : '' }}>Disabled</option>
                                            <option value="15" {{ $userSettings['auto_logout_time'] == 15 ? 'selected' : '' }}>15 minutes</option>
                                            <option value="30" {{ $userSettings['auto_logout_time'] == 30 ? 'selected' : '' }}>30 minutes</option>
                                            <option value="60" {{ $userSettings['auto_logout_time'] == 60 ? 'selected' : '' }}>1 hour</option>
                                            <option value="120" {{ $userSettings['auto_logout_time'] == 120 ? 'selected' : '' }}>2 hours</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch" style="margin-top: 1.7rem;">
                                            <input class="form-check-input" type="checkbox" name="remember_last_page" value="1" {{ $userSettings['remember_last_page'] ? 'checked' : '' }}>
                                            <label class="form-check-label">Remember Last Visited Page</label>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                {{-- Performance --}}
                                <div class="settings-section-header mt-4">
                                    <div class="settings-icon-wrapper">
                                        <i class="ti ti-rocket"></i>
                                    </div>
                                    <div class="settings-section-title">
                                        <h3 class="mb-1">Performance</h3>
                                        <p class="text-muted mb-0 small">Optimize system performance and resource usage.</p>
                                    </div>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="enable_animations" value="1" {{ $userSettings['enable_animations'] ? 'checked' : '' }}>
                                            <label class="form-check-label">Enable UI Animations</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="lazy_load_images" value="1" {{ $userSettings['lazy_load_images'] ? 'checked' : '' }}>
                                            <label class="form-check-label">Lazy Load Images</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="fluid_layout" value="1" {{ $userSettings['fluid_layout'] ? 'checked' : '' }}>
                                            <label class="form-check-label">Enable Fluid Layout</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-label">Data Refresh Rate (seconds)</div>
                                        <select name="data_refresh_rate" class="form-control" required>
                                            <option value="0" {{ $userSettings['data_refresh_rate'] == 0 ? 'selected' : '' }}>Manual only</option>
                                            <option value="15" {{ $userSettings['data_refresh_rate'] == 15 ? 'selected' : '' }}>15 seconds</option>
                                            <option value="30" {{ $userSettings['data_refresh_rate'] == 30 ? 'selected' : '' }}>30 seconds</option>
                                            <option value="60" {{ $userSettings['data_refresh_rate'] == 60 ? 'selected' : '' }}>1 minute</option>
                                            <option value="300" {{ $userSettings['data_refresh_rate'] == 300 ? 'selected' : '' }}>5 minutes</option>
                                        </select>
                                    </div>
                                </div>

                                <hr>

                                {{-- Language --}}
                                <div class="settings-section-header mt-4">
                                    <div class="settings-icon-wrapper">
                                        <i class="ti ti-language"></i>
                                    </div>
                                    <div class="settings-section-title">
                                        <h3 class="mb-1">Language & Localization</h3>
                                        <p class="text-muted mb-0 small">Set your preferred language and regional settings.</p>
                                    </div>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <div class="form-label">System Language</div>
                                        <select name="system_language" class="form-control" required>
                                            <option value="en" {{ $userSettings['system_language'] === 'en' ? 'selected' : '' }}>English</option>
                                            <option value="id" {{ $userSettings['system_language'] === 'id' ? 'selected' : '' }}>Bahasa Indonesia</option>
                                        </select>
                                    </div>
                                </div>

                                <hr>

                                {{-- Advanced --}}
                                <div class="settings-section-header mt-4">
                                    <div class="settings-icon-wrapper">
                                        <i class="ti ti-adjustments"></i>
                                    </div>
                                    <div class="settings-section-title">
                                        <h3 class="mb-1">Advanced</h3>
                                        <p class="text-muted mb-0 small">Additional options for power users and debugging.</p>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="enable_debug_mode" value="1" {{ $userSettings['enable_debug_mode'] ? 'checked' : '' }}>
                                            <label class="form-check-label">Enable Debug Information</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="enable_keyboard_shortcuts" value="1" {{ $userSettings['enable_keyboard_shortcuts'] ? 'checked' : '' }}>
                                            <label class="form-check-label">Enable Keyboard Shortcuts</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="show_tooltips" value="1" {{ $userSettings['show_tooltips'] ? 'checked' : '' }}>
                                            <label class="form-check-label">Show Help Tooltips</label>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="card-footer bg-transparent">
                                <div class="btn-list justify-content-end">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="ti ti-device-floppy me-1"></i>Save Preferences
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('head-scripts')
<style>
.settings-section-header {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}
.settings-icon-wrapper {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--tblr-bg-surface-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
    color: var(--tblr-primary);
}
</style>
@endpush
