@extends('superadmin.layouts.base')
@section('title', 'Permissions')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-shield-lock"></i> System</div>
                    <h2 class="page-title">
                        Permissions
                        <a href="#" class="ms-3 text-muted" data-bs-toggle="modal" data-bs-target="#how-it-works-modal" title="How It Works">
                            <i class="ti ti-help-circle fs-3"></i>
                        </a>
                    </h2>
                    <div class="text-muted mt-1">View permissions and roles across the system.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-lg-8">
                    <div class="card shadow border-0 rounded-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="ti ti-key me-2 text-muted"></i>Permissions by Module</h3>
                        </div>
                        <div class="card-body">
                            @forelse($permissions as $module => $perms)
                            <div class="mb-4">
                                <h4 class="text-capitalize mb-2">{{ $module }}</h4>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($perms as $perm)
                                    <span class="badge bg-secondary text-white">{{ $perm->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                            @empty
                            <p class="text-muted">No permissions found.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card shadow border-0 rounded-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="ti ti-users me-2 text-muted"></i>Roles</h3>
                        </div>
                        <div class="card-body">
                            @forelse($roles as $role)
                            <div class="mb-3">
                                <h4 class="mb-1">{{ $role->name }}</h4>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($role->permissions as $perm)
                                    <span class="badge bg-info text-white">{{ $perm->name }}</span>
                                    @endforeach
                                </div>
                                @if($role->permissions->isEmpty())
                                <span class="text-muted small">No permissions assigned</span>
                                @endif
                            </div>
                            @empty
                            <p class="text-muted">No roles found.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endSection