@extends('admin.layouts.base')

@section('title', 'Currency Settings')

@section('content')
    <div class="page-wrapper">
        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    <div class="card">
                        <div class="card-body">
                            <h2><i class="ti ti-coin me-2"></i>CURRENCY SETTINGS</h2>
                        </div>
                        <hr class="my-0">
                        <div class="row g-0">
                            <div class="col-12 col-md-3 border-end">
                                @include('admin.layouts.menu')
                            </div>
                            <div class="col-12 col-md-9 d-flex flex-column">
                                <div class="card-body">
                                    <form id="currencySettingsForm" action="{{ route('admin.setting.currency.update') }}"
                                        method="POST">
                                        @csrf

                                        <!-- Currency Selection Section -->
                                        <div class="settings-section mb-5">
                                            <div class="settings-section-header">
                                                <div class="settings-icon-wrapper">
                                                    <i class="ti ti-world"></i>
                                                </div>
                                                <div class="settings-section-title">
                                                    <h3 class="mb-1">Currency Selection</h3>
                                                    <p class="text-muted mb-0 small">Choose your primary currency and
                                                        position</p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="form-label">Select Currency</div>
                                                        <select name="selected_currency" id="selectedCurrency"
                                                            class="form-select" required>
                                                            @foreach ($predefinedCurrencies as $currency)
                                                                <option value="{{ $currency['code'] }}"
                                                                    data-symbol="{{ $currency['symbol'] }}"
                                                                    data-locale="{{ $currency['locale'] }}"
                                                                    {{ $setting->currency_code == $currency['code'] ? 'selected' : '' }}>
                                                                    {{ $currency['name'] }} ({{ $currency['symbol'] }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Select your business currency</small>
                                                    </div>
                                                    <input type="hidden" name="currency_code" id="currencyCode"
                                                        value="{{ $setting->currency_code }}">
                                                    <input type="hidden" name="locale" id="locale"
                                                        value="{{ $setting->locale }}">
                                                    <input type="hidden" name="currency_symbol" id="currencySymbol"
                                                        value="{{ $setting->currency_symbol }}">
                                                    <div class="col-md-6">
                                                        <div class="form-label">Currency Position</div>
                                                        <select name="position" class="form-select" required>
                                                            <option value="prefix"
                                                                {{ $setting->position == 'prefix' ? 'selected' : '' }}>
                                                                Prefix
                                                                ($100)</option>
                                                            <option value="suffix"
                                                                {{ $setting->position == 'suffix' ? 'selected' : '' }}>
                                                                Suffix
                                                                (100$)</option>
                                                        </select>
                                                        <small class="text-muted">Choose where to display currency
                                                            symbol</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Number Formatting Section -->
                                        <div class="settings-section mb-4">
                                            <div class="settings-section-header">
                                                <div class="settings-icon-wrapper">
                                                    <i class="ti ti-calculator"></i>
                                                </div>
                                                <div class="settings-section-title">
                                                    <h3 class="mb-1">Number Formatting</h3>
                                                    <p class="text-muted mb-0 small">Configure decimal and thousand
                                                        separators</p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="form-label">Decimal Separator</div>
                                                        <input type="text" name="decimal_separator" class="form-control"
                                                            value="{{ $setting->decimal_separator }}" required
                                                            placeholder="e.g., .">
                                                        <small class="text-muted">Character used to separate decimal
                                                            places</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-label">Thousand Separator</div>
                                                        <input type="text" name="thousand_separator" class="form-control"
                                                            value="{{ $setting->thousand_separator }}" required
                                                            placeholder="e.g., ,">
                                                        <small class="text-muted">Character used to separate
                                                            thousands</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-label">Decimal Places</div>
                                                        <input type="number" name="decimal_places" class="form-control"
                                                            value="{{ $setting->decimal_places }}" required
                                                            placeholder="e.g., 2" min="0" max="10">
                                                        <small class="text-muted">Number of digits after decimal
                                                            point</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="d-flex align-items-center h-100">
                                                            <div class="alert alert-info mb-0 w-100">
                                                                <div class="d-flex align-items-center">
                                                                    <i class="ti ti-info-circle me-2"></i>
                                                                    <div>
                                                                        <strong>Current Format:</strong>
                                                                        <span class="badge bg-primary text-white ms-2">
                                                                            {{ $setting->position == 'prefix' ? $setting->currency_symbol : '' }}1{{ $setting->thousand_separator }}234{{ $setting->decimal_separator }}{{ str_repeat('0', $setting->decimal_places) }}{{ $setting->position == 'suffix' ? $setting->currency_symbol : '' }}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <small class="text-muted mt-1 d-block">Preview of how
                                                                    numbers will be displayed</small>
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
                                        <button type="button" class="btn btn-primary" id="showModalButton">Save
                                            Settings</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('admin.layouts.modals.currmodals')
@endsection
