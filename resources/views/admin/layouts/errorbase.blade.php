<!DOCTYPE html>
<html lang="en">

{{-- HEAD --}}

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Invent-MAG | @yield('title')</title>
    <link href="{{ asset('tabler/dist/css/tabler.min.css?1692870487') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-flags.min.css?1692870487') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-payments.min.css?1692870487') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-vendors.min.css?1692870487') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/demo.min.css?1692870487') }}" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        @import url('https://rsms.me/inter/inter.css');

        :root {
            --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }

        html,
        body {
            height: 100%;
            font-feature-settings: "cv03", "cv04", "cv11"
        }

        .page {
            display: flex;
            flex-direction: column;
            min-height: 85vh;
        }

        .page-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .footer {
            margin-top: auto;
            /* Ensures it stays at the bottom */
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Hide the loader once the page has fully loaded
            setTimeout(() => {
                document.querySelector(".page-center").style.display = "none";
            }, 300); // Smooth transition delay (optional)
        });
    </script>
</head>

{{-- BODY --}}

<body>
    <div class="page page-center">
        <div class="container container-slim py-4">
            <div class="text-center">
                <div class="mb-3">
                    <a class="navbar-brand navbar-brand-autodark"><i
                            class="ti ti-brand-minecraft fs-2 me-2"></i>Invent-MAG</a>
                </div>
                <div class="spinner-border" role="status"></div>
            </div>
        </div>
    </div>
    <div class="main-content">
        @yield('content')
    </div>
    @include('admin.layouts.footer')
    @include('admin.layouts.script')
</body>

</html>
