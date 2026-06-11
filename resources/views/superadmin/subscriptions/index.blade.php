@extends('superadmin.layouts.base')
@section('title', 'Subscriptions')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-receipt"></i> Management</div>
                    <h2 class="page-title">Subscriptions</h2>
                    <div class="text-muted mt-1">Monitor all tenant subscriptions.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards mb-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Total</div>
                            <div class="h1 mb-0">{{ $stats['total'] }}</div>
                        </div>
                        <i class="ti ti-receipt stat-icon"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Active</div>
                            <div class="h1 mb-0 text-success">{{ $stats['active'] }}</div>
                        </div>
                        <i class="ti ti-check stat-icon text-success"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Trialing</div>
                            <div class="h1 mb-0 text-primary">{{ $stats['trialing'] }}</div>
                        </div>
                        <i class="ti ti-flask stat-icon text-primary"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Expired</div>
                            <div class="h1 mb-0 text-danger">{{ $stats['expired'] }}</div>
                        </div>
                        <i class="ti ti-alert-triangle stat-icon text-danger"></i>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card shadow border-0 rounded-4 stat-card card-hover">
                        <div class="card-body text-center">
                            <div class="subheader">Terminated</div>
                            <div class="h1 mb-0 text-secondary">{{ $stats['terminated'] }}</div>
                        </div>
                        <i class="ti ti-ban stat-icon text-secondary"></i>
                    </div>
                </div>
            </div>
            <div class="card shadow border-0 rounded-4">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Tenant</th>
                                <th>Plan</th>
                                <th>Status</th>
                                <th>Trial Ends</th>
                                <th>Plan Expires</th>
                                <th>Billing Period</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subscriptions as $sub)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm me-2" style="background-image: url('https://ui-avatars.com/api/?name={{ urlencode($sub->name) }}&background=random')"></span>
                                        <div>
                                            <span class="fw-semibold">{{ $sub->name }}</span>
                                            <div class="text-muted small">{{ $sub->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $sub->plan?->name ?? 'N/A' }}</td>
                                <td>
                                    @if($sub->plan_status === 'active')
                                        <span class="badge bg-success text-white">Active</span>
                                    @elseif($sub->plan_status === 'terminated')
                                        <span class="badge bg-secondary text-white">Terminated</span>
                                    @elseif($sub->trialExpired())
                                        <span class="badge bg-danger text-white">Trial Expired</span>
                                    @elseif($sub->onTrial())
                                        <span class="badge bg-primary text-white">Trial</span>
                                    @elseif($sub->plan_status === 'expired')
                                        <span class="badge bg-danger text-white">Expired</span>
                                    @else
                                        <span class="badge bg-secondary text-white">{{ $sub->plan_status ?? 'N/A' }}</span>
                                    @endif
                                </td>
                                <td class="text-muted">
                                    @if($sub->onTrial() || $sub->trialExpired())
                                        {{ $sub->trial_ends_at->format('M d, Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-muted">
                                    @if($sub->onTrial() || $sub->trialExpired())
                                        —
                                    @elseif($sub->plan_expires_at)
                                        {{ $sub->plan_expires_at->format('M d, Y') }}
                                    @elseif($sub->billing_period_end)
                                        {{ \Carbon\Carbon::parse($sub->billing_period_end)->format('M d, Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($sub->billing_period_start && $sub->billing_period_end)
                                        <small>{{ $sub->billing_period_start->format('M d') }} - {{ $sub->billing_period_end->format('M d, Y') }}</small>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($sub->plan_status !== 'terminated')
                                    <div class="dropdown">
                                        <button class="btn btn-icon-only btn-ghost" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <form method="POST" action="{{ route('superadmin.subscriptions.terminate', $sub) }}" onsubmit="return confirm('Are you sure you want to terminate {{ $sub->name }}\'s subscription? This action cannot be undone.')">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="ti ti-ban me-2"></i>Terminate
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    @else
                                    <div class="dropdown">
                                        <button class="btn btn-icon-only btn-ghost" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <form method="POST" action="{{ route('superadmin.subscriptions.reactivate', $sub) }}" onsubmit="return confirm('Reactivate {{ $sub->name }}\'s subscription?')">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-success">
                                                    <i class="ti ti-refresh me-2"></i>Reactivate
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No subscriptions found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($subscriptions->hasPages())
                <div class="card-footer d-flex justify-content-center border-top">
                    {{ $subscriptions->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endSection
