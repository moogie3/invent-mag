@php
    try {
        $themeMode = auth()->check() ? (auth()->user()->system_settings['theme_mode'] ?? 'light') : 'light';
    } catch (\Throwable $e) {
        $themeMode = 'light';
    }
@endphp

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Invent-MAG | @yield('title')</title>
    <link href="{{ asset('tabler/dist/css/tabler.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-flags.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-payments.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-vendors.min.css') }}" rel="stylesheet" />
    @vite('resources/css/error.css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    @vite('resources/js/admin/layouts/page-loader.js')
</head>

<body class="error-page is-loading-page">
    @include('admin.layouts.partials.global-loader')

    <div class="page">
        <div class="page-wrapper">
            <div class="page-body">
                <div class="{{ $containerClass ?? "container-xl" }}">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    @include('admin.layouts.partials.session-notifications')
    @include('admin.layouts.script')
    @vite('resources/js/admin/layouts/theme-toggle.js')
    @vite('resources/js/admin/layouts/theme-visibility.js')
</body>

</html>
