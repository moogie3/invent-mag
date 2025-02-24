<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Invent-Mag | @yield('title')</title>
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
    </style>
</head>

<body class="d-flex flex-column">
    <script src="{{ asset('tabler/dist/js/demo-theme.min.js?1692870487') }}"></script>
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="text-center mb-4">
                <h1 class="navbar-brand text-center mx-auto m-0">
                    <a href="{{ route('login') }}" class="nav-link fs-1 fw-bold">
                        <i class="ti ti-brand-minecraft fs-2 me-2"></i>Invent-MAG
                    </a>
                </h1>
            </div>
            @yield('content')
        </div>
    </div>
</body>
</body>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Password Toggle Feature
        const passwordField = document.getElementById("password");
        const togglePassword = document.getElementById("toggle-password");
        const toggleIcon = togglePassword.querySelector("i");

        togglePassword.addEventListener("click", function(e) {
            e.preventDefault(); // Prevent link jump
            if (passwordField.type === "password") {
                passwordField.type = "text"; // Show password
                toggleIcon.classList.remove("ti-eye");
                toggleIcon.classList.add("ti-eye-off");
            } else {
                passwordField.type = "password"; // Hide password
                toggleIcon.classList.remove("ti-eye-off");
                toggleIcon.classList.add("ti-eye");
            }
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</html>
