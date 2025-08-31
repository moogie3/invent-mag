<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="currency-locale" content="{{ app()->getLocale() }}">
    <meta name="currency-code" content="{{ optional(App\Models\CurrencySetting::first())->currency_code ?? 'USD' }}">
    <meta name="currency-decimal-places"
        content="{{ optional(App\Models\CurrencySetting::first())->decimal_places ?? 2 }}">
    <title>Invent-MAG | @yield('title')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link
        href="{{ asset('tabler/dist/css/tabler.min.css?v=' . filemtime(public_path('tabler/dist/css/tabler.min.css'))) }}"
        rel="stylesheet" />
    <link
        href="{{ asset('tabler/dist/css/tabler-flags.min.css?v=' . filemtime(public_path('tabler/dist/css/tabler-flags.min.css'))) }}"
        rel="stylesheet" />
    <link
        href="{{ asset('tabler/dist/css/tabler-payments.min.css?v=' . filemtime(public_path('tabler/dist/css/tabler-payments.min.css'))) }}"
        rel="stylesheet" />
    <link
        href="{{ asset('tabler/dist/css/tabler-vendors.min.css?v=' . filemtime(public_path('tabler/dist/css/tabler-vendors.min.css'))) }}"
        rel="stylesheet" />
    @vite('resources/css/app.css')
    @vite('resources/css/menu-sidebar.css')
    @vite('resources/js/admin/layouts/menu-sidebar.js')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/flatpickr" as="script" crossorigin>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr" crossorigin="anonymous"></script>
</head>

<body class="antialiased">
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
    @vite('resources/js/admin/layouts/page-loader.js')
</body>
