@extends('superadmin.layouts.base')
@section('title', 'System Settings')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-settings"></i> Configuration</div>
                    <h2 class="page-title">System Settings</h2>
                    <div class="text-muted mt-1">Global application configuration.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="card shadow border-0 rounded-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-sliders me-2 text-muted"></i>Application Settings</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.settings.update') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Application Name</label>
                            <input type="text" name="app_name" class="form-control" value="{{ $settings['app_name'] }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Application URL</label>
                            <input type="url" name="app_url" class="form-control" value="{{ $settings['app_url'] }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Environment</label>
                            <input type="text" class="form-control" value="{{ $settings['app_env'] }}" readonly>
                            <small class="text-muted">Change via .env file</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-check">
                                <input type="checkbox" name="app_debug" class="form-check-input" @checked($settings['app_debug'])>
                                <span class="form-check-label">Debug Mode</span>
                            </label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mail Driver</label>
                            <input type="text" class="form-control" value="{{ $settings['mail_driver'] }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mail From Address</label>
                            <input type="text" class="form-control" value="{{ $settings['mail_from_address'] }}" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary px-4"><i class="ti ti-device-floppy me-1"></i>Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endSection