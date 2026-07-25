@php
    $themeMode = 'light';
    $platformCurrency = App\Helpers\CurrencyHelper::getPlatformSettings();
@endphp

<!DOCTYPE html>
<html lang="en" data-bs-theme="{{ $themeMode }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive">

    @vite('resources/js/admin/layouts/theme-initializer.js')

    <title>Invent-MAG | @yield('title')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="{{ asset('tabler/dist/css/tabler.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-flags.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-payments.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-vendors.min.css') }}" rel="stylesheet" />

    @vite('resources/css/app.css')
    @vite('resources/css/superadmin.css')

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    @include('superadmin.layouts.script')

    @vite('resources/js/admin/layouts/page-loader.js')
    @vite('resources/js/admin/layouts/theme-toggle.js')
    @vite('resources/js/admin/layouts/theme-visibility.js')
    @vite('resources/js/admin/layouts/menu-sidebar.js')

    @stack('head-scripts')

</head>

<body class="is-loading-page">

    @include('admin.layouts.partials.global-loader')

    <div class="wrapper">
        @include('superadmin.layouts.menu-sidebar')
        <div class="main-content">
            @include('superadmin.layouts.navbar')
            <turbo-frame id="main-content" data-turbo-action="advance">
                <span id="page-route-indicator" data-route="{{ Route::currentRouteName() }}" class="d-none" aria-hidden="true"></span>
                @yield('content')
                @include('admin.layouts.partials.session-notifications')
            </turbo-frame>
        </div>
    </div>

    @include('admin.layouts.footer')

    @include('superadmin.layouts.modals.how-it-works-modal')

    @stack('scripts')

    @turbo
</body>

</html>
