@php
    $themeMode = auth()->user()->system_settings['theme_mode'] ?? 'light';
    $navigationType = auth()->user()->system_settings['navigation_type'] ?? 'sidebar';
    $sidebarLock = auth()->user()->system_settings['sidebar_lock'] ?? false;
    $stickyNavbar = auth()->user()->system_settings['sticky_navbar'] ?? false;
@endphp

<!DOCTYPE html>
<html lang="en" data-bs-theme="{{ $themeMode }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="default-placeholder-url" content="{{ asset('img/default_placeholder.png') }}">
    {{-- Prevent search engines from indexing the admin panel --}}
    <meta name="robots" content="noindex, nofollow, noarchive">

    {{-- Page-specific head content (e.g. turbo-cache-control meta) --}}
    @yield('header')

    @vite('resources/js/admin/layouts/theme-initializer.js')

    @php
        $currencySettings = App\Helpers\CurrencyHelper::getSettings();
    @endphp
    

    <title>Invent-MAG | @yield('title')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="{{ asset('tabler/dist/css/tabler.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-flags.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-payments.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-vendors.min.css') }}" rel="stylesheet" />

    @vite('resources/css/app.css')
    @vite('resources/css/menu-sidebar.css')
    @vite('resources/css/settings.css')
    @vite('resources/css/tutorial-modal.css')
    @vite('resources/js/app.js')
    @vite('resources/js/admin/layouts/menu-sidebar.js')

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/flatpickr" as="script" crossorigin>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr" crossorigin="anonymous"></script>

    {{-- Move all scripts to head for Turbo compatibility --}}
    @include('admin.layouts.script')
    @vite('resources/js/admin/layouts/page-loader.js')
    @vite('resources/js/admin/layouts/theme-toggle.js')
    @vite('resources/js/admin/layouts/theme-visibility.js')

    <style>
        .sidebar-locked .sidebar {
            position: fixed;
        }
    </style>


</head>

<body data-route="{{ Route::currentRouteName() }}" data-currency-settings='{{ json_encode(['locale' => $currencySettings->locale, 'currency_code' => $currencySettings->currency_code, 'currency_symbol' => $currencySettings->currency_symbol, 'decimal_places' => $currencySettings->decimal_places, 'decimal_separator' => $currencySettings->decimal_separator, 'thousand_separator' => $currencySettings->thousand_separator, 'position' => $currencySettings->position]) }}'
    class="is-loading-page {{ auth()->check() && (auth()->user()->system_settings['navigation_type'] ?? 'sidebar') === 'navbar' ? 'layout-navbar' : ((auth()->user()->system_settings['navigation_type'] ?? 'sidebar') === 'both' ? 'layout-navbar-v2' : '') }} {{ auth()->check() && (auth()->user()->system_settings['sticky_navbar'] ?? false) ? 'sticky-navbar' : '' }} {{ auth()->check() && (auth()->user()->system_settings['sidebar_lock'] ?? false) ? 'sidebar-locked' : '' }}">

    @include('admin.layouts.partials.global-loader')

    <div class="wrapper">
        @if($navigationType !== 'navbar')
            @include('admin.layouts.menu-sidebar')
        @endif
        <div class="main-content">
            @if(in_array($navigationType, ['navbar', 'both']))
                @include('admin.layouts.navbar')
            @endif
            @include('admin.layouts.partials.impersonation-banner')
            @include('admin.layouts.partials.trial-banner')
            @if(request()->routeIs('admin.setting.*') || request()->routeIs('admin.users.*') || request()->routeIs('admin.hr.*'))
                {{-- Disable turbo-frame for settings and user management pages to fix modal issues --}}
                @yield('content')
            @else
                <turbo-frame id="main-content" data-turbo-action="advance">
                    {{-- Route indicator: updated on every Turbo navigation so tutorial-modal.js knows the current page --}}
                    <span id="page-route-indicator" data-route="{{ Route::currentRouteName() }}" class="d-none" aria-hidden="true"></span>
                    @yield('content')
                </turbo-frame>
            @endif
        </div>
    </div>

    @include('admin.layouts.footer')
    @include('admin.layouts.partials.session-notifications')
    @include('admin.layouts.modals.general.keyboard-shortcuts-modal')
    @include('admin.layouts.modals.general.tutorial-modal')

    @turbo

    @if(isset($isDebugMode) && $isDebugMode)
        <div style="position: fixed; bottom: 0; left: 0; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; font-size: 0.8em; z-index: 10000;">
            <strong>Debug Mode:</strong> ON<br>
            Current Route: {{ Route::currentRouteName() ?? 'N/A' }}<br>
            User ID: {{ auth()->user()->id ?? 'N/A' }}
        </div>
    @endif
</body>

</html>
