<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <script>
        /*
         * These pages have no session, so the saved choice lives in
         * localStorage. It has to be applied here, synchronously, while the
         * document is still parsing: theme-initializer.js is a module and runs
         * after first paint, which is what flashed the login page light for a
         * user who had already saved dark.
         */
        (function () {
            var saved = null;
            try { saved = localStorage.getItem('theme'); } catch (e) {}
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            // Tells the initializer this value was read from storage, so an OS
            // guess is never written back as if the user had chosen it.
            window.__inventThemeFromStorage = Boolean(saved);
            document.documentElement.setAttribute('data-bs-theme', saved || (prefersDark ? 'dark' : 'light'));
        })();
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    {{-- Prevent search engines from indexing the admin login page --}}
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>Invent-MAG | @yield('title')</title>
    @vite('resources/js/admin/layouts/theme-initializer.js')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('tabler/dist/css/tabler.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-flags.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-payments.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-vendors.min.css') }}" rel="stylesheet" />
    @vite('resources/css/auth.css')
    <link rel="stylesheet" href="{{ asset('tabler/dist/icons/tabler-icons.min.css') }}?2.47.0">
    @vite('resources/js/admin/layouts/page-loader.js')
</head>

<body class="auth-body is-loading-page">
    @include('admin.layouts.partials.global-loader')
    <div class="auth-content">
        <div class="page-center">
            <div class="container-tight">
                <div class="text-center mb-4">
                    <h1 class="navbar-brand text-center mx-auto m-0">
                        <a href="{{ route('admin.login') }}" class="nav-link fs-1 fw-bold">
                            <i class="ti ti-brand-minecraft fs-2 me-2"></i>Invent-MAG
                        </a>
                    </h1>
                </div>
                @yield('content')
            </div>
        </div>
    </div>
    <button id="theme-toggle-button" class="btn btn-icon theme-toggle-button"
        style="position: absolute; top: 1rem; right: 1rem;">
        <i class="ti ti-moon" id="theme-icon"></i>
    </button>
    @include('admin.layouts.partials.session-notifications')
    @include('admin.layouts.script')
    @vite('resources/js/admin/layouts/theme-visibility.js')
    @vite('resources/js/admin/layouts/theme-toggle.js')
</body>

</html>
