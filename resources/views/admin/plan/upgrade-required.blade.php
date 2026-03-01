@extends('admin.layouts.base')

@section('title', __('plan.upgrade_required'))

@section('content')
<div class="page-body">
    <div class="container-tight py-4">
        <div class="empty">
            <div class="mb-4">
                <a class="h2 navbar-brand navbar-brand-autodark">
                    <i class="ti ti-brand-minecraft fs-1 me-2 text-primary"></i>Invent-MAG
                </a>
            </div>

            <div class="empty-icon bg-warning-lt rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                <i class="ti ti-lock fs-1 text-warning"></i>
            </div>

            <p class="empty-title fw-bold">{{ __('plan.upgrade_required') }}</p>

            <p class="empty-subtitle text-secondary mb-3">
                {{ $upgradeMessage ?? __('plan.upgrade_desc') }}
            </p>

            @if(isset($currentPlan))
                <p class="text-muted small">
                    {{ __('plan.you_are_on') }} <strong class="text-dark">{{ $currentPlan->name }}</strong> {{ __('plan.plan') }}
                </p>
            @endif

            <div class="empty-action d-flex flex-column flex-sm-row justify-content-center gap-3">
                <a href="{{ route('admin.setting.plan.upgrade', isset($feature) ? ['feature' => $feature] : []) }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="ti ti-arrow-up-circle me-2"></i>{{ __('plan.view_plans') }}
                </a>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="ti ti-arrow-left me-2"></i>{{ __('plan.go_back') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
