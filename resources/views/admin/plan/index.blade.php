@extends('admin.layouts.base')

@section('title', __('plan.title'))

@section('content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="{{ $containerClass ?? "container-xl" }}">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <h2><i class="ti ti-crown me-2"></i>{{ __('plan.title') }}</h2>
                    </div>
                    <hr class="my-0">
                    <div class="row g-0">
                        <div class="col-12 col-md-3 border-end">
                            @include('admin.layouts.menu')
                        </div>
                        <div class="col-12 col-md-9 d-flex flex-column">
                            <div class="card-body">
                                @if(session('success'))
                                    <div class="alert alert-success alert-dismissible" role="alert">
                                        <div class="d-flex">
                                            <div><i class="ti ti-check me-2"></i></div>
                                            <div>{{ session('success') }}</div>
                                        </div>
                                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                                    </div>
                                @endif

                                @if(session('error'))
                                    <div class="alert alert-danger alert-dismissible" role="alert">
                                        <div class="d-flex">
                                            <div><i class="ti ti-alert-circle me-2"></i></div>
                                            <div>{{ session('error') }}</div>
                                        </div>
                                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                                    </div>
                                @endif

                                {{-- Current Plan Section --}}
                                @php
                                    $tenant = app('currentTenant');
                                    $isDemo = $tenant && str_starts_with($tenant->name, 'Demo ');
                                @endphp

                                @if($isDemo)
                                    <div class="alert alert-info border-0 rounded-4 shadow-sm mb-4">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="ti ti-info-circle fs-2 me-2"></i>
                                            <h4 class="mb-0">Live Demo Environment</h4>
                                        </div>
                                        <p class="mb-0">You are currently viewing a live demo of the <strong>{{ $currentPlan?->name ?? 'Professional' }}</strong> tier. This environment resets every 24 hours. To save your data and start using Invent-Mag for real, create your own workspace.</p>
                                    </div>
                                @endif

                                <div class="settings-section mb-5">
                                    <div class="settings-section-header">
                                        <div class="settings-icon-wrapper">
                                            <i class="ti ti-crown"></i>
                                        </div>
                                        <div class="settings-section-title">
                                            <h3 class="mb-1">{{ __('plan.current_plan') }}</h3>
                                            <p class="text-muted mb-0 small">
                                                {{ __('plan.manage_subscription') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="settings-section-content">
                                        <div class="row g-3 align-items-center">
                                            <div class="col-md-7">
                                                @if($currentPlan)
                                                    <div class="d-flex align-items-center mb-2">
                                                        <h3 class="mb-0 me-2">{{ $currentPlan->name }}</h3>
                                                        @if($isDemo)
                                                            <span class="badge bg-info-lt rounded-pill">Demo Workspace</span>
                                                        @elseif($usageStats['trial']['on_trial'] ?? false)
                                                            <span class="badge bg-warning-lt rounded-pill">{{ __('plan.trial_ends_in', ['days' => $usageStats['trial']['days_remaining']]) }}</span>
                                                        @else
                                                            <span class="badge bg-success-lt rounded-pill">{{ __('plan.current') }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-secondary mb-2 fw-medium">
                                                        ${{ number_format($currentPlan->price, 2) }} <span class="text-muted fw-normal">{{ __('plan.billing_monthly') }}</span>
                                                    </div>
                                                    @if($currentPlan->description)
                                                        <p class="text-muted small mb-0">{{ $currentPlan->description }}</p>
                                                    @endif
                                                @else
                                                    <div class="d-flex align-items-center mb-2">
                                                        <h3 class="mb-0 me-2">{{ __('plan.unlimited_access') }}</h3>
                                                        <span class="badge bg-success-lt rounded-pill">{{ __('plan.current') }}</span>
                                                    </div>
                                                    <p class="text-muted small mb-0">{{ __('plan.legacy_desc') }}</p>
                                                @endif
                                            </div>
                                            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                                                @if($isDemo)
                                                    <a href="{{ config('app.frontend_url', 'http://localhost:4321') }}/signup" target="_blank" class="btn btn-primary rounded-pill px-4 shadow-sm">
                                                        <i class="ti ti-rocket me-2"></i>Start Free Trial
                                                    </a>
                                                @else
                                                    <a href="{{ route('admin.setting.plan.upgrade') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
                                                        <i class="ti ti-arrow-up-circle me-2"></i>{{ __('plan.upgrade_plan') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Usage Statistics Section --}}
                                <div class="settings-section mb-5">
                                    <div class="settings-section-header">
                                        <div class="settings-icon-wrapper">
                                            <i class="ti ti-chart-bar"></i>
                                        </div>
                                        <div class="settings-section-title">
                                            <h3 class="mb-1">{{ __('plan.usage_statistics') }}</h3>
                                        </div>
                                    </div>
                                    <div class="settings-section-content">
                                        <div class="row g-4">
                                            {{-- Users --}}
                                            <div class="col-md-6">
                                                <div class="form-label mb-2 fw-medium">{{ __('plan.users') }}</div>
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-secondary small">{{ __('plan.used') }}</span>
                                                    <span class="small fw-bold">
                                                        {{ $usageStats['users']['current'] ?? 0 }}
                                                        <span class="text-muted fw-normal">{{ __('plan.of') }}</span> {{ ($usageStats['users']['unlimited'] ?? true) ? __('plan.unlimited') : $usageStats['users']['limit'] }}
                                                    </span>
                                                </div>
                                                @if(!($usageStats['users']['unlimited'] ?? true))
                                                    <div class="progress progress-sm rounded-pill">
                                                        <div class="progress-bar rounded-pill {{ ($usageStats['users']['percentage'] ?? 0) > 80 ? 'bg-danger' : 'bg-primary' }}"
                                                             style="width: {{ $usageStats['users']['percentage'] ?? 0 }}%">
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="progress progress-sm rounded-pill">
                                                        <div class="progress-bar bg-success rounded-pill" style="width: 100%"></div>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Warehouses --}}
                                            <div class="col-md-6">
                                                <div class="form-label mb-2 fw-medium">{{ __('plan.warehouses') }}</div>
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-secondary small">{{ __('plan.used') }}</span>
                                                    <span class="small fw-bold">
                                                        {{ $usageStats['warehouses']['current'] ?? 0 }}
                                                        <span class="text-muted fw-normal">{{ __('plan.of') }}</span> {{ ($usageStats['warehouses']['unlimited'] ?? true) ? __('plan.unlimited') : $usageStats['warehouses']['limit'] }}
                                                    </span>
                                                </div>
                                                @if(!($usageStats['warehouses']['unlimited'] ?? true))
                                                    <div class="progress progress-sm rounded-pill">
                                                        <div class="progress-bar rounded-pill {{ ($usageStats['warehouses']['percentage'] ?? 0) > 80 ? 'bg-danger' : 'bg-primary' }}"
                                                             style="width: {{ $usageStats['warehouses']['percentage'] ?? 0 }}%">
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="progress progress-sm rounded-pill">
                                                        <div class="progress-bar bg-success rounded-pill" style="width: 100%"></div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Plan Features Section --}}
                                <div class="settings-section mb-4">
                                    <div class="settings-section-header">
                                        <div class="settings-icon-wrapper">
                                            <i class="ti ti-list-check"></i>
                                        </div>
                                        <div class="settings-section-title">
                                            <h3 class="mb-1">{{ __('plan.plan_features') }}</h3>
                                            <p class="text-muted mb-0 small">
                                                {{ __('plan.your_plan_includes') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="settings-section-content">
                                        <div class="card bg-light border-0 rounded-4">
                                            <div class="card-body">
                                                @if($currentPlan && is_array($currentPlan->features))
                                                    <div class="row g-3">
                                                        @foreach($features as $slug => $label)
                                                            <div class="col-md-6 col-lg-4">
                                                                <div class="d-flex align-items-center">
                                                                    @if($currentPlan->hasFeature($slug))
                                                                        <div class="bg-success-lt rounded-circle p-1 me-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                                            <i class="ti ti-check text-success" style="font-size: 14px;"></i>
                                                                        </div>
                                                                        <span class="small fw-medium">{{ $label }}</span>
                                                                    @else
                                                                        <div class="bg-secondary-lt rounded-circle p-1 me-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                                            <i class="ti ti-x text-muted" style="font-size: 14px;"></i>
                                                                        </div>
                                                                        <span class="small text-muted">{{ $label }}</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-success-lt rounded-circle p-1 me-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                            <i class="ti ti-check text-success" style="font-size: 14px;"></i>
                                                        </div>
                                                        <span class="fw-medium">{{ __('plan.unlimited_access') }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
