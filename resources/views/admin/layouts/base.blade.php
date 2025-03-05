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

        body {
            font-feature-settings: "cv03", "cv04", "cv11";
        }

        @media print {
            .purchase-info {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                /* Change to 3 for a 3-column layout */
                gap: 15px;
            }

            .purchase-info>div {
                break-inside: avoid;
                /* Prevents column breaks in print */
                page-break-inside: avoid;
            }

            .sales-info {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                /* Change to 3 for a 3-column layout */
                gap: 15px;
            }

            .sales-info>div {
                break-inside: avoid;
                /* Prevents column breaks in print */
                page-break-inside: avoid;
            }

            /* Hide elements that shouldn't be included in the PDF */
            .no-print {
                display: none !important;
            }

            /* Ensure the invoice prints properly */
            body {
                font-size: 12px;
                /* Adjust font size */
                color: black;
                /* Ensure text is black */
            }
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
                    <a href="." class="navbar-brand navbar-brand-autodark"><img src="./static/logo-small.svg"
                            height="36" alt=""></a>
                </div>
                <div class="text-secondary mb-3">Loading</div>
                <div class="progress progress-sm">
                    <div class="progress-bar progress-bar-indeterminate"></div>
                </div>
            </div>
        </div>
    </div>
    @include('admin.layouts.navbar')
    <div class="main-content">
        @yield('content')
    </div>
    @include('admin.layouts.footer')
    @include('admin.layouts.script')
</body>

</html>
