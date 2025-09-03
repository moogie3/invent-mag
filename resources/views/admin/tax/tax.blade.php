@extends('admin.layouts.base')

@section('title', 'Tax Settings')

@section('content')
    <div class="page-wrapper">
        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    <div class="card">
                        <div class="card-body">
                            <h2><i class="ti ti-receipt-tax me-2"></i>TAX SETTINGS</h2>
                        </div>
                        <hr class="my-0">
                        <div class="row g-0">
                            <div class="col-12 col-md-3 border-end">
                                @include('admin.layouts.menu')
                            </div>
                            <div class="col-12 col-md-9 d-flex flex-column">
                                <div class="card-body">
                                    <form id="taxSettingsForm" action="{{ route('admin.setting.tax.update') }}"
                                        method="POST">
                                        @csrf

                                        <!-- Tax Configuration Section -->
                                        <div class="settings-section mb-5">
                                            <div class="settings-section-header">
                                                <div class="settings-icon-wrapper">
                                                    <i class="ti ti-calculator"></i>
                                                </div>
                                                <div class="settings-section-title">
                                                    <h3 class="mb-1">Tax Configuration</h3>
                                                    <p class="text-muted mb-0 small">Set up tax name and rate for your
                                                        business</p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="form-label">Tax Name</div>
                                                        <input type="text" name="name" class="form-control"
                                                            value="{{ optional($tax)->name ?? '' }}" required
                                                            placeholder="e.g., VAT, Sales Tax, GST">
                                                        <small class="text-muted">Enter a descriptive name for your
                                                            tax</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-label">Tax Rate (%)</div>
                                                        <input type="number" name="rate" class="form-control"
                                                            value="{{ optional($tax)->rate ?? '' }}" step="0.01" required
                                                            placeholder="e.g., 10.00" min="0" max="100">
                                                        <small class="text-muted">Enter tax percentage (e.g., 10 for
                                                            10%)</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Tax Activation Section -->
                                        <div class="settings-section mb-4">
                                            <div class="settings-section-header">
                                                <div class="settings-icon-wrapper">
                                                    <i class="ti ti-toggle-left"></i>
                                                </div>
                                                <div class="settings-section-title">
                                                    <h3 class="mb-1">Tax Activation</h3>
                                                    <p class="text-muted mb-0 small">Enable or disable tax calculations
                                                        system-wide</p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="form-label mb-2">Activate Tax</div>
                                                        <div class="form-check form-switch">
                                                            <!-- Hidden input ensures '0' is sent if unchecked -->
                                                            <input type="hidden" name="is_active" value="0">
                                                            <input class="form-check-input" type="checkbox" name="is_active"
                                                                id="taxSwitch" value="1"
                                                                {{ optional($tax)->is_active ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="taxSwitch">Enable
                                                                Tax</label>
                                                        </div>
                                                        <small class="text-muted">When enabled, tax will be applied to all
                                                            applicable transactions</small>
                                                    </div>
                                                    <!-- Replace the tax status alert section in your blade template -->
                                                    <div class="col-md-6">
                                                        <div class="d-flex align-items-center h-100">
                                                            <div class="alert alert-info mb-0 w-100">
                                                                <div class="d-flex align-items-center">
                                                                    <i class="ti ti-info-circle me-2"></i>
                                                                    <div>
                                                                        <strong>Tax Status:</strong>
                                                                        @if (optional($tax)->is_active)
                                                                            <span
                                                                                class="badge bg-success text-white ms-2">Active</span>
                                                                        @else
                                                                            <span
                                                                                class="badge bg-danger text-white ms-2">Inactive</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                @if (optional($tax)->is_active)
                                                                    <small class="text-muted mt-1 d-block">Tax is currently
                                                                        being applied to transactions</small>
                                                                @else
                                                                    <small class="text-muted mt-1 d-block">Tax is not being
                                                                        applied to transactions</small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </form>
                                </div>
                                <div class="card-footer bg-transparent mt-auto">
                                    <div class="btn-list justify-content-end">
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#confirmModal">Save Settings</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.layouts.modals.taxmodals')
@endsection
