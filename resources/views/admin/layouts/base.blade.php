<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <!-- CRITICAL: Proper viewport meta tag for consistent sizing -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Invent-MAG | @yield('title')</title>

    <!-- Force consistent font loading to prevent FOUT/FOIT -->
    <link rel="preconnect" href="https://rsms.me" crossorigin>
    <link rel="preload" href="https://rsms.me/inter/Inter-Regular.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="https://rsms.me/inter/Inter-Medium.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="https://rsms.me/inter/Inter-SemiBold.woff2" as="font" type="font/woff2" crossorigin>

    <!-- Load Inter font with font-display for consistent rendering -->
    <style>
        @import url('https://rsms.me/inter/inter.css');

        :root {
            --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }

        html {
            font-family: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
            font-size: 16px;
            /* Force browser default */
            -webkit-text-size-adjust: 100%;
            -moz-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }
    </style>

    <!-- Load Tabler CSS with version cache busting -->
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

    <!-- Load your custom CSS AFTER Tabler with proper cache busting -->
    <link href="{{ asset('css/app.css?v=' . filemtime(public_path('css/app.css'))) }}" rel="stylesheet" />

    <!-- External resources with SRI for security and consistency -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">

    <!-- Preload critical scripts -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/flatpickr" as="script" crossorigin>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script
        src="{{ asset('js/admin/layouts/page-loader.js?v=' . filemtime(public_path('js/admin/layouts/page-loader.js'))) }}">
    </script>
</head>

<body>
    <div class="page page-center">
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
    <div class="main-content">
        @include('admin.layouts.navbar')
        @yield('content')
    </div>
    @include('admin.layouts.footer')
    @include('admin.layouts.script')
</body>

</html>
