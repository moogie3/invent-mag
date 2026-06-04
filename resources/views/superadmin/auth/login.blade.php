<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>Invent-MAG | Super Admin Login</title>
    @vite('resources/js/admin/layouts/theme-initializer.js')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('tabler/dist/css/tabler.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-flags.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-payments.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-vendors.min.css') }}" rel="stylesheet" />
    @vite('resources/css/auth.css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    @vite('resources/js/admin/layouts/page-loader.js')
</head>

<body class="auth-body is-loading-page">
    @include('admin.layouts.partials.global-loader')
    <div class="auth-content">
        <div class="page-center">
            <div class="container-tight">
                <div class="text-center mb-4">
                    <h1 class="navbar-brand text-center mx-auto m-0">
                        <a href="{{ route('superadmin.login') }}" class="nav-link fs-1 fw-bold">
                            <i class="ti ti-shield-lock fs-2 me-2"></i>Super Admin
                        </a>
                    </h1>
                    <div class="text-muted mt-1">Platform Management</div>
                </div>
                <div class="card card-md border-0 shadow-sm rounded-3">
                    <div class="card-body">
                        <h2 class="h2 text-center mb-5">Sign in</h2>
                        <form action="{{ route('superadmin.login') }}" method="POST" autocomplete="off" novalidate>
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Your email" autocomplete="off" required value="{{ old('email') }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Password</label>
                                <div class="input-group input-group-flat">
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Your password" autocomplete="off" id="password" required>
                                    <span class="input-group-text">
                                        <a href="#" class="link-secondary" title="Show password" data-bs-toggle="tooltip" id="toggle-password">
                                            <i class="ti ti-eye fs-1"></i>
                                        </a>
                                    </span>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-footer">
                                <button type="submit" class="btn btn-primary w-100" id="loginSubmitBtn">
                                    <span class="login-btn-text">Sign in</span>
                                    <span class="login-btn-loading d-none">
                                        <span class="spinner-border spinner-border-sm me-2" role="status" style="width: 14px; height: 14px;"></span>
                                        Signing in...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('admin.layouts.partials.session-notifications')

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"
        nonce="{{ $cspNonce ?? '' }}"></script>
    <script src="{{ asset('tabler/dist/js/tabler.min.js?1692870487') }}" nonce="{{ $cspNonce ?? '' }}"></script>
    <script src="{{ asset('tabler/dist/js/demo.min.js?1692870487') }}" nonce="{{ $cspNonce ?? '' }}"></script>
</body>

</html>
