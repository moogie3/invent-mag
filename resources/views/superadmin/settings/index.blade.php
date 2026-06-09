@extends('superadmin.layouts.base')
@section('title', 'System Settings')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-settings"></i> Configuration</div>
                    <h2 class="page-title">Application Settings</h2>
                    <div class="text-muted mt-1">Global application configuration written to the .env file.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body d-flex justify-content-between align-items-center py-3">
                    <h2 class="mb-0"><i class="ti ti-settings me-2"></i>System Settings</h2>
                </div>
                <hr class="my-0">

                <div class="row g-0">
                    <div class="col-12 col-md-3 border-end">
                        <div class="card-body">
                            <h3 class="header"><i class="ti ti-menu-2 me-2"></i>Setting List</h3>
                            <hr class="my-3">
                            <div class="list-group list-group-transparent">
                                <a href="{{ route('superadmin.settings.index') }}"
                                    class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('superadmin.settings.index') ? 'active' : '' }}">
                                    <i class="ti ti-settings fs-2 me-2"></i> Application
                                </a>
                                <a href="{{ route('superadmin.settings.preferences') }}"
                                    class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('superadmin.settings.preferences') ? 'active' : '' }}">
                                    <i class="ti ti-adjustments fs-2 me-2"></i> Preferences
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-9 d-flex flex-column">
                        <form method="POST" action="{{ route('superadmin.settings.update') }}">
                            @csrf
                            <div class="card-body">
                                <div class="settings-section-header">
                                    <div class="settings-icon-wrapper">
                                        <i class="ti ti-settings"></i>
                                    </div>
                                    <div class="settings-section-title">
                                        <h3 class="mb-1">Application Settings</h3>
                                        <p class="text-muted mb-0 small">Global application configuration written to the .env file.</p>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-label">Application Name</div>
                                        <input type="text" name="app_name" class="form-control" value="{{ $appSettings['app_name'] }}">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-label">Application URL</div>
                                        <input type="url" name="app_url" class="form-control" value="{{ $appSettings['app_url'] }}">
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-label">Environment</div>
                                        <input type="text" class="form-control" value="{{ $appSettings['app_env'] }}" readonly>
                                        <small class="text-muted">Change via .env file</small>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check" style="margin-top: 1.7rem;">
                                            <input type="checkbox" name="app_debug" class="form-check-input" @checked($appSettings['app_debug'])>
                                            <span class="form-check-label">Debug Mode</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-4">
                                        <div class="form-label">Mail Driver</div>
                                        <input type="text" class="form-control" value="{{ $appSettings['mail_driver'] }}" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-label">Mail From Address</div>
                                        <input type="text" class="form-control" value="{{ $appSettings['mail_from_address'] }}" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-label">Mail From Name</div>
                                        <input type="text" class="form-control" value="{{ $appSettings['mail_from_name'] }}" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="btn-list justify-content-end">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="ti ti-device-floppy me-1"></i>Save Application Settings
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('head-scripts')
<style>
.settings-section-header {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}
.settings-icon-wrapper {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--tblr-bg-surface-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
    color: var(--tblr-primary);
}
</style>
@endpush
