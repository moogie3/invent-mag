@extends('superadmin.layouts.base')
@section('title', 'Tenant Details')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-ghost btn-sm me-2">
                            <i class="ti ti-arrow-left"></i>
                        </a>
                        <div>
                            <div class="page-pretitle"><i class="ti ti-building-community"></i> Management / Tenants</div>
                            <h2 class="page-title mb-0">{{ $tenant->name }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-lg-4">
                    <div class="card shadow border-0 rounded-4 card-hover">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <span class="avatar avatar-xl" style="background-image: url('https://ui-avatars.com/api/?name={{ urlencode($tenant->name) }}&background=random&size=128')"></span>
                            </div>
                            <h3>{{ $tenant->name }}</h3>
                            <div class="text-muted">{{ $tenant->email }}</div>
                            <div class="mt-3">
                                @if($tenant->plan_status === 'active')
                                    <span class="badge bg-success text-white fs-6">Active</span>
                                @elseif($tenant->plan_status === 'trialing')
                                    <span class="badge bg-primary text-white fs-6">Trial</span>
                                @elseif($tenant->plan_status === 'expired')
                                    <span class="badge bg-danger text-white fs-6">Expired</span>
                                @else
                                    <span class="badge bg-secondary text-white fs-6">{{ $tenant->plan_status ?? 'N/A' }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card shadow border-0 rounded-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="ti ti-info-circle me-2 text-muted"></i>Tenant Information</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-5">Domain</dt>
                                <dd class="col-7"><code>{{ $tenant->domain ?? 'N/A' }}</code></dd>

                                <dt class="col-5">Plan</dt>
                                <dd class="col-7">{{ $tenant->plan?->name ?? 'No plan' }}</dd>

                                <dt class="col-5">Plan Status</dt>
                                <dd class="col-7">{{ $tenant->plan_status ?? 'N/A' }}</dd>

                                <dt class="col-5">Trial Ends</dt>
                                <dd class="col-7">{{ $tenant->trial_ends_at?->format('M d, Y') ?? 'N/A' }}</dd>

                                <dt class="col-5">Plan Expires</dt>
                                <dd class="col-7">{{ $tenant->plan_expires_at?->format('M d, Y') ?? 'N/A' }}</dd>

                                <dt class="col-5">Created</dt>
                                <dd class="col-7">{{ $tenant->created_at->format('M d, Y H:i') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endSection