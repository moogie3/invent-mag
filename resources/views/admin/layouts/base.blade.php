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

        .main-content {
            visibility: hidden;
        }

        .footer {
            margin-top: auto;
        }

        #invoiceContainer {
            display: flex;
            flex-direction: column;
            height: 560px;
            /* Match the height of the product grid */
        }

        #productList {
            flex-grow: 1;
            /* Makes the list take up available space */
            overflow-y: hidden;
            /* Initially hides scrolling */
            border: 1px solid gray;
            /* Optional border for styling */
            padding: 10px;
        }

        #productList:hover {
            overflow-y: auto;
            /* Enables scrolling only on hover */
        }

        #totalPriceContainer {
            padding-top: 10px;
            /* Adds some space above the total price */
            background: white;
            /* Ensures background consistency */
        }


        @media print {
            .purchase-info {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
            }

            .purchase-info>div {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .sales-info {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .sales-info>div {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .no-print {
                display: none !important;
            }

            body {
                font-size: 12px;
                color: black;
            }
        }
    </style>
    {{-- PAGE LOADER SCRIPT --}}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(() => {
                document.querySelector(".page-center").style.display = "none";
                document.querySelector(".main-content").style.visibility = "visible";
            }, 300);
        });
    </script>
</head>

{{-- BODY --}}

<body>
    <div class="page page-center">
        <div class="container container-slim py-4">
            <div class="text-center">
                <div class="text-center">
                    <div class="mb-3">
                        <a class="navbar-brand navbar-brand-autodark"><i
                                class="ti ti-brand-minecraft fs-2 me-2"></i>Invent-MAG</a>
                    </div>
                    <div class="spinner-border" role="status"></div>
                </div>
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
