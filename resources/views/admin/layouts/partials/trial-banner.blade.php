@php
    $tenant = null;
    try {
        $tenant = app('currentTenant');
    } catch (\Exception $e) {}
    
    $isDemo = $tenant && str_starts_with($tenant->name, 'Demo ');
@endphp

@if($tenant && $tenant instanceof \App\Models\Tenant)
    @if($isDemo)
        <style>
            .hide-caret::after { display: none !important; }
        </style>
        <div class="alert alert-info alert-dismissible mb-0 shadow-sm border-0 border-bottom d-none" role="alert" id="demo-banner"
             style="border-radius: 0;">
            <div class="{{ $containerClass ?? 'container-xl' }} d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="ti ti-info-circle text-info fs-3 me-2"></i>
                    <span>
                        <strong>Live Demo:</strong> You are currently viewing a live demo workspace. All data resets every 24 hours.
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ config('app.frontend_url', 'http://localhost:4321') }}/signup" target="_blank" class="btn btn-sm btn-info text-white rounded-pill px-3 shadow-sm d-none d-sm-inline-flex">
                        <i class="ti ti-rocket me-1"></i>Start Free Trial
                    </a>
                    <!-- Close Options Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-icon btn-ghost-info rounded-circle dropdown-toggle hide-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 0; box-shadow: none; width: 24px; height: 24px;">
                            <i class="ti ti-x"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="border-radius: 12px; min-width: 200px;">
                            <li>
                                <button type="button" class="dropdown-item py-2" data-bs-dismiss="alert" aria-label="Close">
                                    <i class="ti ti-x text-muted me-2"></i> {{ __('plan.close_normal') }}
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item py-2" id="demo-btn-dismiss-session">
                                    <i class="ti ti-eye-off text-muted me-2"></i> {{ __('plan.close_session') }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const banner = document.getElementById('demo-banner');
                if (!banner) return;

                if (sessionStorage.getItem('demo_banner_dismissed_session') !== 'true') {
                    banner.classList.remove('d-none');
                }

                const sessionBtn = document.getElementById('demo-btn-dismiss-session');
                if (sessionBtn) {
                    sessionBtn.addEventListener('click', function() {
                        sessionStorage.setItem('demo_banner_dismissed_session', 'true');
                        const alert = new bootstrap.Alert(banner);
                        alert.close();
                    });
                }
            });
        </script>
    @elseif($tenant->onTrial())
        <style>
            .hide-caret::after { display: none !important; }
        </style>
        <div class="alert alert-warning alert-dismissible mb-0 shadow-sm border-0 border-bottom d-none" role="alert" id="trial-banner"
             style="border-radius: 0;">
            <div class="{{ $containerClass ?? 'container-xl' }} d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="ti ti-clock-stop text-warning fs-3 me-2"></i>
                    <span>
                        {!! __('plan.trial_banner_desc', ['days' => '<strong>' . $tenant->trialDaysRemaining() . '</strong>']) !!}
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.setting.plan.upgrade') }}" class="btn btn-sm btn-warning text-white rounded-pill px-3 shadow-sm d-none d-sm-inline-flex">
                        <i class="ti ti-rocket me-1"></i>{{ __('plan.upgrade_now') }}
                    </a>
                    <!-- Close Options Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-icon btn-ghost-warning rounded-circle dropdown-toggle hide-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 0; box-shadow: none; width: 24px; height: 24px;">
                            <i class="ti ti-x"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="border-radius: 12px; min-width: 200px;">
                            <li>
                                <button type="button" class="dropdown-item py-2" data-bs-dismiss="alert" aria-label="Close">
                                    <i class="ti ti-x text-muted me-2"></i> {{ __('plan.close_normal') }}
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item py-2" id="btn-dismiss-session">
                                    <i class="ti ti-eye-off text-muted me-2"></i> {{ __('plan.close_session') }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const banner = document.getElementById('trial-banner');
                if (!banner) return;

                if (sessionStorage.getItem('trial_banner_dismissed_session') !== 'true') {
                    banner.classList.remove('d-none');
                }

                const sessionBtn = document.getElementById('btn-dismiss-session');
                if (sessionBtn) {
                    sessionBtn.addEventListener('click', function() {
                        sessionStorage.setItem('trial_banner_dismissed_session', 'true');
                        const alert = new bootstrap.Alert(banner);
                        alert.close();
                    });
                }
            });
        </script>
    @endif
@endif
