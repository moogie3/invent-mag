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

    @vite('resources/js/admin/layouts/theme-initializer.js')

    @php
        $currencySettings = App\Helpers\CurrencyHelper::getSettings();
    @endphp
    <script>
        window.currencySettings = {
            locale: "{{ $currencySettings->locale }}",
            currency_code: "{{ $currencySettings->currency_code }}",
            currency_symbol: "{{ $currencySettings->currency_symbol }}",
            decimal_places: {{ $currencySettings->decimal_places }},
            decimal_separator: "{{ $currencySettings->decimal_separator }}",
            thousand_separator: "{{ $currencySettings->thousand_separator }}",
            position: "{{ $currencySettings->position }}",
        };
    </script>

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
    @vite('resources/js/admin/layouts/menu-sidebar.js')

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/flatpickr" as="script" crossorigin>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr" crossorigin="anonymous"></script>

    <style>
        .sidebar-locked .sidebar {
            position: fixed;
        }
    </style>
    
</head>

<body
    class="{{ auth()->check() && (auth()->user()->system_settings['navigation_type'] ?? 'sidebar') === 'navbar' ? 'layout-navbar' : ((auth()->user()->system_settings['navigation_type'] ?? 'sidebar') === 'both' ? 'layout-navbar-v2' : '') }} {{ auth()->check() && (auth()->user()->system_settings['sticky_navbar'] ?? false) ? 'sticky-navbar' : '' }} {{ auth()->check() && (auth()->user()->system_settings['sidebar_lock'] ?? false) ? 'sidebar-locked' : '' }} {{ auth()->check() && (auth()->user()->system_settings['compact_mode'] ?? false) ? 'compact-mode' : '' }}">

    <div class="page-loader">
        <div class="container container-slim py-4">
            <div class="text-center">
                <div class="mb-3">
                    <a class="navbar-brand navbar-brand-autodark">
                        <i class="ti ti-brand-minecraft fs-2 me-2"></i>Invent-MAG
                    </a>
                </div>
                <div class="spinner-border" role="status"></div>
            </div>
        </div>
    </div>

    <div class="wrapper">
        @include('admin.layouts.menu-sidebar')
        <div class="main-content">
            @include('admin.layouts.navbar')
            @yield('content')
        </div>
    </div>

    @include('admin.layouts.footer')
    @include('admin.layouts.script')
    @include('admin.layouts.partials.session-notifications')
    @include('admin.layouts.modals.general.keyboard-shortcuts-modal')

    @vite('resources/js/admin/layouts/page-loader.js')
    @vite('resources/js/admin/layouts/theme-toggle.js')
    @vite('resources/js/admin/layouts/theme-visibility.js')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const showThemeToggle =
                {{ json_encode(auth()->user()->system_settings['show_theme_toggle'] ?? true) }};
            console.log('show_theme_toggle setting (from base.blade.php):', showThemeToggle);
        });
    </script>

    @if(isset($isDebugMode) && $isDebugMode)
        <div style="position: fixed; bottom: 0; left: 0; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; font-size: 0.8em; z-index: 10000;">
            <strong>Debug Mode:</strong> ON<br>
            Current Route: {{ Route::currentRouteName() ?? 'N/A' }}<br>
            User ID: {{ auth()->user()->id ?? 'N/A' }}
        </div>
    @endif
</body>

</html>
