<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Invent-MAG | @yield('title')</title>
    <link href="{{ asset('tabler/dist/css/tabler.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-flags.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-payments.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-vendors.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/auth.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>

<body class="auth-body">
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
    @include('admin.layouts.script')
</body>

</html>
