@extends('superadmin.layouts.base')
@section('title', 'Plans')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-packages"></i> Management</div>
                    <h2 class="page-title">
                        Plans
                        <a href="#" class="ms-3 text-muted" data-bs-toggle="modal" data-bs-target="#how-it-works-modal" title="How It Works">
                            <i class="ti ti-help-circle fs-3"></i>
                        </a>
                    </h2>
                    <div class="text-muted mt-1">Manage subscription plans and pricing tiers.</div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <a href="{{ route('superadmin.plans.create') }}" class="btn btn-primary shadow-sm">
                        <i class="ti ti-plus me-1"></i>Create Plan
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                @forelse($plans as $plan)
                <div class="col">
                    <div class="card plan-card shadow border-0 h-100 card-hover">
                        @if($plan->is_active)
                        <div class="card-status-top bg-success" style="height: 4px;"></div>
                        @else
                        <div class="card-status-top bg-secondary" style="height: 4px;"></div>
                        @endif
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h3 class="card-title mb-0 fs-2">{{ $plan->name }}</h3>
                                    <div class="text-muted small">Slug: <code>{{ $plan->slug }}</code></div>
                                </div>
                                @if($plan->is_active)
                                <span class="badge bg-success text-white fs-6">Active</span>
                                @else
                                <span class="badge bg-secondary text-white fs-6">Inactive</span>
                                @endif
                            </div>
                            <div class="mb-3 pb-2 border-bottom">
                                <span class="plan-price">{{ App\Helpers\CurrencyHelper::formatPlatform($plan->price) }}</span>
                                <span class="plan-period">/ {{ $plan->billing_cycle }}</span>
                            </div>
                            <p class="text-muted mb-3">{{ $plan->description ?: 'No description provided.' }}</p>
                            <div class="row g-3 mb-3">
                                <div class="col-4 text-center">
                                    <div class="fw-bold fs-3">{{ $plan->tenants_count }}</div>
                                    <div class="text-muted small">Subscribers</div>
                                </div>
                                <div class="col-4 text-center">
                                    <div class="fw-bold fs-3">{{ $plan->max_users === -1 ? '∞' : $plan->max_users }}</div>
                                    <div class="text-muted small">Max Users</div>
                                </div>
                                <div class="col-4 text-center">
                                    <div class="fw-bold fs-3">{{ $plan->trial_days }}</div>
                                    <div class="text-muted small">Trial Days</div>
                                </div>
                            </div>
                            @if($plan->features)
                            <div class="mb-3">
                                <div class="text-muted small fw-semibold mb-2">Features</div>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($plan->features as $feature)
                                    <span class="badge bg-success text-white">{{ $feature }}</span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            <div class="d-flex gap-2 mt-auto pt-2 border-top">
                                <a href="{{ route('superadmin.plans.edit', $plan) }}" class="btn btn-primary flex-grow-1">
                                    <i class="ti ti-edit me-1"></i>Edit Plan
                                </a>
                                <form method="POST" action="{{ route('superadmin.plans.destroy', $plan) }}" class="confirm-form" data-message="Delete this plan? This cannot be undone.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Delete plan">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="card shadow border-0 rounded-4">
                        <div class="card-body text-center py-5 text-muted">No plans created yet.</div>
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script nonce="{{ $cspNonce }}">
document.querySelectorAll('.confirm-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        if (!confirm(form.getAttribute('data-message'))) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
@endSection