@php
    $themeMode = 'light';
@endphp

<!DOCTYPE html>
<html lang="en" data-bs-theme="{{ $themeMode }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive">

    @vite('resources/js/admin/layouts/theme-initializer.js')

    <title>Invent-MAG | @yield('title')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="{{ asset('tabler/dist/css/tabler.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-flags.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-payments.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/dist/css/tabler-vendors.min.css') }}" rel="stylesheet" />

    @vite('resources/css/app.css')
    @vite('resources/css/superadmin.css')

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    @include('superadmin.layouts.script')

    @vite('resources/js/admin/layouts/page-loader.js')
    @vite('resources/js/admin/layouts/theme-toggle.js')
    @vite('resources/js/admin/layouts/theme-visibility.js')
    @vite('resources/js/admin/layouts/menu-sidebar.js')

    @stack('head-scripts')

</head>

<body class="is-loading-page">

    @include('admin.layouts.partials.global-loader')

    <div class="wrapper">
        @include('superadmin.layouts.menu-sidebar')
        <div class="main-content">
            @include('superadmin.layouts.navbar')
            <turbo-frame id="main-content" data-turbo-action="advance">
                <span id="page-route-indicator" data-route="{{ Route::currentRouteName() }}" class="d-none" aria-hidden="true"></span>
                @yield('content')
            </turbo-frame>
        </div>
    </div>

    @include('admin.layouts.footer')
    @include('admin.layouts.partials.session-notifications')

    {{-- How It Works Modal --}}
    <div class="modal modal-blur fade" id="how-it-works-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h3 class="modal-title fw-bold">
                        <i class="ti ti-help-circle text-primary me-2"></i>How Invent-MAG Super Admin Works
                    </h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card border shadow-sm rounded-3 h-100">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <span class="avatar avatar-md bg-blue-lt rounded-3">
                                            <i class="ti ti-building-community fs-2 text-blue"></i>
                                        </span>
                                    </div>
                                    <h5 class="fw-semibold">Tenants</h5>
                                    <p class="text-muted small mb-0">Manage tenant accounts, view details, toggle active status, and monitor plan assignments.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border shadow-sm rounded-3 h-100">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <span class="avatar avatar-md bg-indigo-lt rounded-3">
                                            <i class="ti ti-packages fs-2 text-indigo"></i>
                                        </span>
                                    </div>
                                    <h5 class="fw-semibold">Plans &amp; Subscriptions</h5>
                                    <p class="text-muted small mb-0">Create and edit pricing plans. View all active, trialing, and expired subscriptions.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border shadow-sm rounded-3 h-100">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <span class="avatar avatar-md bg-orange-lt rounded-3">
                                            <i class="ti ti-flag fs-2 text-orange"></i>
                                        </span>
                                    </div>
                                    <h5 class="fw-semibold">Features &amp; Tiers</h5>
                                    <p class="text-muted small mb-0">Toggle feature availability per plan. Features are grouped by tier: Core, Professional, Enterprise.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border shadow-sm rounded-3 h-100">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <span class="avatar avatar-md bg-green-lt rounded-3">
                                            <i class="ti ti-currency-dollar fs-2 text-green"></i>
                                        </span>
                                    </div>
                                    <h5 class="fw-semibold">Billing &amp; Payments</h5>
                                    <p class="text-muted small mb-0">View revenue overview, all transaction orders, pending payments needing approval, and paid invoices.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border shadow-sm rounded-3 h-100">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <span class="avatar avatar-md bg-teal-lt rounded-3">
                                            <i class="ti ti-world fs-2 text-teal"></i>
                                        </span>
                                    </div>
                                    <h5 class="fw-semibold">Landing Page</h5>
                                    <p class="text-muted small mb-0">Edit the public-facing website content: hero, features, pricing tiers, and contact info.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border shadow-sm rounded-3 h-100">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <span class="avatar avatar-md bg-purple-lt rounded-3">
                                            <i class="ti ti-shield-lock fs-2 text-purple"></i>
                                        </span>
                                    </div>
                                    <h5 class="fw-semibold">System &amp; Security</h5>
                                    <p class="text-muted small mb-0">Manage permissions, view audit logs, configure system settings, and schedule backups.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center pt-0">
                    <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">
                        <i class="ti ti-check me-1"></i>Got it
                    </button>
                </div>
            </div>
        </div>
    </div>

    @stack('scripts')

    @turbo
</body>

</html>
