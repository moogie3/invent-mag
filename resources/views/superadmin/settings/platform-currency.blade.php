@extends('superadmin.layouts.base')
@section('title', 'Platform Currency')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-currency-dollar"></i> Configuration</div>
                    <h2 class="page-title">
                        Platform Currency Settings
                        <a href="#" class="ms-3 text-muted" data-bs-toggle="modal" data-bs-target="#how-it-works-modal" title="How It Works">
                            <i class="ti ti-help-circle fs-3"></i>
                        </a>
                    </h2>
                    <div class="text-muted mt-1">Configure the currency symbol and formatting for the super admin panel. This does not affect tenant currency settings.</div>
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
                                <a href="{{ route('superadmin.settings.preferences') }}"
                                    class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('superadmin.settings.preferences') ? 'active' : '' }}">
                                    <i class="ti ti-adjustments fs-2 me-2"></i> Preferences
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-9 d-flex flex-column">
                        <form action="{{ route('superadmin.settings.platform-currency.update') }}" method="POST">
                            @csrf
                            <div class="card-body">

                                {{-- Currency Selection --}}
                                <div class="settings-section-header">
                                    <div class="settings-icon-wrapper">
                                        <i class="ti ti-world"></i>
                                    </div>
                                    <div class="settings-section-title">
                                        <h3 class="mb-1">Currency Selection</h3>
                                        <p class="text-muted mb-0 small">Choose the currency for the super admin panel billing and revenue displays.</p>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-label">Currency Code</div>
                                        <select name="currency_code" id="currencyCode" class="form-select" required>
                                            @foreach ($predefinedCurrencies as $currency)
                                                <option value="{{ $currency['code'] }}"
                                                    {{ $settings->currency_code == $currency['code'] ? 'selected' : '' }}>
                                                    {{ $currency['name'] }} ({{ $currency['symbol'] }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-label">Currency Symbol</div>
                                        <input type="text" name="currency_symbol" id="currencySymbol" class="form-control"
                                            value="{{ $settings->currency_symbol }}" required
                                            placeholder="e.g., $">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-label">Currency Position</div>
                                        <select name="position" class="form-select" required>
                                            <option value="prefix"
                                                {{ $settings->position == 'prefix' ? 'selected' : '' }}>
                                                Prefix ($100)</option>
                                            <option value="suffix"
                                                {{ $settings->position == 'suffix' ? 'selected' : '' }}>
                                                Suffix (100$)</option>
                                        </select>
                                    </div>
                                </div>

                                <hr class="my-4">

                                {{-- Number Formatting --}}
                                <div class="settings-section-header">
                                    <div class="settings-icon-wrapper">
                                        <i class="ti ti-calculator"></i>
                                    </div>
                                    <div class="settings-section-title">
                                        <h3 class="mb-1">Number Formatting</h3>
                                        <p class="text-muted mb-0 small">Configure how monetary values are formatted. These auto-fill when you change the currency code.</p>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="form-label">Decimal Separator</div>
                                        <input type="text" name="decimal_separator" id="decimalSeparator" class="form-control"
                                            value="{{ $settings->decimal_separator }}" required
                                            placeholder="e.g., .">
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-label">Thousand Separator</div>
                                        <input type="text" name="thousand_separator" id="thousandSeparator" class="form-control"
                                            value="{{ $settings->thousand_separator }}" required
                                            placeholder="e.g., ,">
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-label">Decimal Places</div>
                                        <input type="number" name="decimal_places" id="decimalPlaces" class="form-control"
                                            value="{{ $settings->decimal_places }}" required
                                            placeholder="e.g., 2" min="0" max="10">
                                    </div>
                                    <div class="col-12">
                                        <div class="alert alert-info mb-0 mt-1">
                                            <div class="d-flex align-items-center">
                                                <i class="ti ti-info-circle me-2"></i>
                                                <div>
                                                    <strong>Preview:</strong>
                                                    <span class="badge bg-primary text-white ms-2">
                                                        {{ $settings->position == 'prefix' ? $settings->currency_symbol : '' }}1{{ $settings->thousand_separator }}234{{ $settings->decimal_separator }}{{ str_repeat('0', $settings->decimal_places) }}{{ $settings->position == 'suffix' ? $settings->currency_symbol : '' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="card-footer bg-transparent">
                                <div class="btn-list justify-content-end">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="ti ti-device-floppy me-1"></i>Save Settings
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
@push('scripts')
@vite('resources/js/superadmin/settings/platform-currency.js')
@endpush
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
