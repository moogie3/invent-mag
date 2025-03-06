<!doctype html>
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
            background: url('{{ asset('storage/background/background.jpeg') }}') no-repeat center center;
            background-size: cover;
            height: 100vh;
            align-items: center;
            justify-content: center;
            opacity: 0;
            animation: fadeIn 1s ease-in-out forwards;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('{{ asset('storage/background/background.jpeg') }}') no-repeat center center;
            background-size: cover;
            filter: blur(10px);
            /* Start with blur */
            opacity: 0;
            animation: blurFadeIn 1.5s ease-in-out forwards;
        }

        .page-center {
            background: rgba(255, 255, 255, 0.8);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes fadeInBackground {
            from {
                opacity: 0;
                filter: blur(15px);
            }

            to {
                opacity: 1;
                filter: blur(0);
            }
        }

        body,
        body::before {
            opacity: 0;
            animation: fadeInBackground 1s ease-in-out forwards;
            animation-delay: 0s;
        }
    </style>
</head>

{{-- BODY --}}

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
{{-- TOGGLE CHECK PASSWORD --}}
@if (!request()->is('forgot-password'))
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // password toggle feature
            const passwordField = document.getElementById("password");
            const togglePassword = document.getElementById("toggle-password");
            const toggleIcon = togglePassword.querySelector("i");

            togglePassword.addEventListener("click", function(e) {
                e.preventDefault(); // prevent link jump
                if (passwordField.type === "password") {
                    passwordField.type = "text"; // show password
                    toggleIcon.classList.remove("ti-eye");
                    toggleIcon.classList.add("ti-eye-off");
                } else {
                    passwordField.type = "password"; // hide password
                    toggleIcon.classList.remove("ti-eye-off");
                    toggleIcon.classList.add("ti-eye");
                }
            });
        });
    </script>
@endif
{{-- ERROR MODAL --}}
@if ($errors->any())
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <button type="button" class="btn-close m-2" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body text-center py-4">
                    <i class="ti ti-alert-triangle icon text-danger icon-lg mb-4"></i>
                    <h3 class="mb-3">Error!</h3>
                    <div class="text-secondary">
                        <div class="text-danger text-start text-center">
                            @foreach ($errors->all() as $error)
                                {{ $error }}<br>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger w-100" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var errorModalElement = document.getElementById("errorModal");
            var errorModal = new bootstrap.Modal(errorModalElement);

            setTimeout(() => {
                errorModal.show();
                document.body.insertAdjacentHTML("beforeend",
                    '<div class="modal-backdrop fade show modal-backdrop-custom"></div>');
            }, 100);

            setTimeout(() => {
                errorModal.hide();
                document.querySelector(".modal-backdrop-custom")?.remove();
            }, 2000);
        });
    </script>
@endif
{{-- SUCCESS MODAL --}}
@if (session('success'))
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <button type="button" class="btn-close m-2" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body text-center py-4">
                    <i class="ti ti-circle-check icon text-success icon-lg mb-4"></i>
                    <h3 class="mb-3">Success!</h3>
                    <div class="text-secondary">
                        <div class="text-success text-start text-center">
                            {{ session('success') }}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success w-100" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var successModalElement = document.getElementById("successModal");
            var successModal = new bootstrap.Modal(successModalElement);

            setTimeout(() => {
                successModal.show();
                document.body.insertAdjacentHTML("beforeend",
                    '<div class="modal-backdrop fade show modal-backdrop-custom"></div>');
            }, 100);

            setTimeout(() => {
                successModal.hide();
                document.querySelector(".modal-backdrop-custom")?.remove();
                window.location.href = "{{ route('admin.login') }}";
            }, 2000);
        });
    </script>
@endif
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</html>
