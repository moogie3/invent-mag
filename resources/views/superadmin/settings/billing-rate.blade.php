@extends('superadmin.layouts.base')
@section('title', 'Billing Rate')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-cash"></i> Configuration</div>
                    <h2 class="page-title">
                        Billing Exchange Rate
                        <a href="#" class="ms-3 text-muted" data-bs-toggle="modal" data-bs-target="#how-it-works-modal" title="How It Works">
                            <i class="ti ti-help-circle fs-3"></i>
                        </a>
                    </h2>
                    <div class="text-muted mt-1">Set the USD → IDR rate used to convert plan prices at checkout. This rate determines the exact IDR gross amount charged via the payment gateway.</div>
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
                                <a href="{{ route('superadmin.settings.platform-currency') }}"
                                    class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('superadmin.settings.platform-currency') ? 'active' : '' }}">
                                    <i class="ti ti-currency-dollar fs-2 me-2"></i> Platform Currency
                                </a>
                                <a href="{{ route('superadmin.settings.billing-rate') }}"
                                    class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('superadmin.settings.billing-rate') ? 'active' : '' }}">
                                    <i class="ti ti-cash fs-2 me-2"></i> Billing Rate
                                </a>
                                <a href="{{ route('superadmin.settings.preferences') }}"
                                    class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('superadmin.settings.preferences') ? 'active' : '' }}">
                                    <i class="ti ti-adjustments fs-2 me-2"></i> Preferences
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-9 d-flex flex-column">
                        <form action="{{ route('superadmin.settings.billing-rate.update') }}" method="POST">
                            @csrf
                            <div class="card-body">

                                <div class="settings-section-header">
                                    <div class="settings-icon-wrapper">
                                        <i class="ti ti-cash"></i>
                                    </div>
                                    <div class="settings-section-title">
                                        <h3 class="mb-1">USD → IDR Billing Rate</h3>
                                        <p class="text-muted mb-0 small">Plan prices are stored in USD. This rate converts them to the IDR amount shown at checkout and charged to the customer.</p>
                                    </div>
                                </div>

                                @if($rateUpdatedAt)
                                    <div class="alert alert-info border-0 mb-4">
                                        <div class="d-flex align-items-center">
                                            <i class="ti ti-info-circle me-2"></i>
                                            <div>
                                                <strong>Last updated:</strong>
                                                {{ $rateUpdatedAt->format('d M Y, H:i') }}
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="usd_idr_rate">Exchange Rate (USD → IDR)</label>
                                        <div class="input-group input-group-flat">
                                            <span class="input-group-text">1 USD = Rp</span>
                                            <input type="number" step="1" min="1" name="usd_idr_rate" id="usd_idr_rate"
                                                class="form-control @error('usd_idr_rate') is-invalid @enderror"
                                                value="{{ old('usd_idr_rate', number_format($rate, 0, '.', '')) }}" required>
                                            @error('usd_idr_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <small class="text-muted">Example: for a $19 plan at 16,000 → Rp 304.000 (before 12% tax).</small>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="alert alert-light border-0 mb-0">
                                            <div class="text-muted small fw-semibold mb-2">Price preview (before 12% PPN)</div>
                                            <div class="d-flex flex-column gap-1 small">
                                                <div>Starter $19 → <strong>Rp {{ number_format(19 * $rate, 0, ',', '.') }}</strong></div>
                                                <div>Professional $59 → <strong>Rp {{ number_format(59 * $rate, 0, ',', '.') }}</strong></div>
                                                <div>Enterprise $109 → <strong>Rp {{ number_format(109 * $rate, 0, ',', '.') }}</strong></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="card-footer bg-transparent">
                                <div class="btn-list justify-content-end">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="ti ti-device-floppy me-1"></i>Save Rate
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
