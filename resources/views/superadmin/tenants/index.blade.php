@extends('superadmin.layouts.base')
@section('title', 'Tenants')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-building-community"></i> Management</div>
                    <h2 class="page-title">Tenants</h2>
                    <div class="text-muted mt-1">Manage all tenant workspaces on the platform.</div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <span class="text-muted"><i class="ti ti-building me-1"></i> {{ $tenants->total() }} total</span>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="card shadow border-0 rounded-4">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Domain</th>
                                <th>Plan</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tenants as $tenant)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm me-2" style="background-image: url('https://ui-avatars.com/api/?name={{ urlencode($tenant->name) }}&background=random')"></span>
                                        <div>
                                            <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="text-reset text-decoration-none fw-semibold">{{ $tenant->name }}</a>
                                            <div class="text-muted small">{{ $tenant->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td><code>{{ $tenant->domain ?? 'N/A' }}</code></td>
                                <td>{{ $tenant->plan?->name ?? 'No plan' }}</td>
                                <td>
                                    @if($tenant->plan_status === 'active')
                                        <span class="badge bg-success text-white">Active</span>
                                    @elseif($tenant->plan_status === 'trialing')
                                        <span class="badge bg-primary text-white">Trial</span>
                                    @elseif($tenant->plan_status === 'expired')
                                        <span class="badge bg-danger text-white">Expired</span>
                                    @else
                                        <span class="badge bg-secondary text-white">{{ $tenant->plan_status ?? 'N/A' }}</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $tenant->created_at->format('M d, Y') }}</td>
                                <td>
                                    <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="btn btn-icon-only btn-ghost" title="View details">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No tenants found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($tenants->hasPages())
                <div class="card-footer d-flex justify-content-center border-top">
                    {{ $tenants->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endSection