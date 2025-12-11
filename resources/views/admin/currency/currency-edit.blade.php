@extends('admin.layouts.base')

@section('title', __('messages.currency_page_title'))

@section('content')
    <div class="page-wrapper">
        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    <div class="card">
                        <div class="card-body">
                            <h2><i class="ti ti-coin me-2"></i>{{ __('messages.currency_settings_title') }}</h2>
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
                                                    <h3 class="mb-1">{{ __('messages.currency_selection_title') }}</h3>
                                                    <p class="text-muted mb-0 small">{{ __('messages.currency_selection_description') }}</p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="form-label">{{ __('messages.currency_selection_select_currency') }}</div>
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
                                                        <small class="text-muted">{{ __('messages.currency_selection_select_business_currency_description') }}</small>
                                                    </div>
                                                    <input type="hidden" name="currency_code" id="currencyCode"
                                                        value="{{ $setting->currency_code }}">
                                                    <input type="hidden" name="locale" id="locale"
                                                        value="{{ $setting->locale }}">
                                                    <input type="hidden" name="currency_symbol" id="currencySymbol"
                                                        value="{{ $setting->currency_symbol }}">
                                                    <div class="col-md-6">
                                                        <div class="form-label">{{ __('messages.currency_selection_currency_position') }}</div>
                                                        <select name="position" class="form-select" required>
                                                            <option value="prefix"
                                                                {{ $setting->position == 'prefix' ? 'selected' : '' }}>
                                                                {{ __('messages.currency_selection_prefix') }}
                                                                ($100)</option>
                                                            <option value="suffix"
                                                                {{ $setting->position == 'suffix' ? 'selected' : '' }}>
                                                                {{ __('messages.currency_selection_suffix') }}
                                                                (100$)</option>
                                                        </select>
                                                        <small class="text-muted">{{ __('messages.currency_selection_choose_symbol_display_description') }}</small>
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
                                                    <h3 class="mb-1">{{ __('messages.currency_number_formatting_title') }}</h3>
                                                    <p class="text-muted mb-0 small">{{ __('messages.currency_number_formatting_description') }}</p>
                                                </div>
                                            </div>
                                            <div class="settings-section-content">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="form-label">{{ __('messages.currency_number_formatting_decimal_separator') }}</div>
                                                        <input type="text" name="decimal_separator" class="form-control"
                                                            value="{{ $setting->decimal_separator }}" required
                                                            placeholder="e.g., .">
                                                        <small class="text-muted">{{ __('messages.currency_number_formatting_decimal_separator_description') }}</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-label">{{ __('messages.currency_number_formatting_thousand_separator') }}</div>
                                                        <input type="text" name="thousand_separator" class="form-control"
                                                            value="{{ $setting->thousand_separator }}" required
                                                            placeholder="e.g., ,">
                                                        <small class="text-muted">{{ __('messages.currency_number_formatting_thousand_separator_description') }}</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-label">{{ __('messages.currency_number_formatting_decimal_places') }}</div>
                                                        <input type="number" name="decimal_places" class="form-control"
                                                            value="{{ $setting->decimal_places }}" required
                                                            placeholder="e.g., 2" min="0" max="10">
                                                        <small class="text-muted">{{ __('messages.currency_number_formatting_decimal_places_description') }}</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="d-flex align-items-center h-100">
                                                            <div class="alert alert-info mb-0 w-100">
                                                                <div class="d-flex align-items-center">
                                                                    <i class="ti ti-info-circle me-2"></i>
                                                                    <div>
                                                                        <strong>{{ __('messages.currency_number_formatting_current_format') }}</strong>
                                                                        <span class="badge bg-primary text-white ms-2">
                                                                            {{ $setting->position == 'prefix' ? $setting->currency_symbol : '' }}1{{ $setting->thousand_separator }}234{{ $setting->decimal_separator }}{{ str_repeat('0', $setting->decimal_places) }}{{ $setting->position == 'suffix' ? $setting->currency_symbol : '' }}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <small class="text-muted mt-1 d-block">{{ __('messages.currency_number_formatting_preview_description') }}</small>
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
                                        <button type="button" class="btn btn-primary" id="showModalButton">{{ __('messages.save_settings') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('admin.layouts.modals.currency.currmodals')
@endsection
