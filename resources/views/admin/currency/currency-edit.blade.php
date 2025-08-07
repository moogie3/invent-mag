@extends('admin.layouts.base')

@section('title', 'Currency Settings')

@section('content')
    <div class="page-wrapper">
        <div class="page-header">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            Overview
                        </div>
                        <h2 class="page-title">
                            Currency Settings
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    <div class="card">
                        <div class="row g-0">
                            <div class="col-12 col-md-3 border-end">
                                @include('admin.layouts.menu')
                            </div>
                            <div class="col-12 col-md-9 d-flex flex-column">
                                <div class="card-body">
                                    <form id="currencySettingsForm" action="{{ route('admin.setting.currency.update') }}"
                                        method="POST">
                                        @csrf
                                        <h2 class="mb-4">Currency Settings</h2>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <label>Select Currency</label>
                                                <select name="selected_currency" id="selectedCurrency" class="form-select" required>
                                                    @foreach($predefinedCurrencies as $currency)
                                                        <option value="{{ $currency['code'] }}"
                                                            data-symbol="{{ $currency['symbol'] }}"
                                                            data-locale="{{ $currency['locale'] }}"
                                                            {{ $setting->currency_code == $currency['code'] ? 'selected' : '' }}>
                                                            {{ $currency['name'] }} ({{ $currency['symbol'] }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <input type="hidden" name="currency_code" id="currencyCode" value="{{ $setting->currency_code }}">
                                            <input type="hidden" name="locale" id="locale" value="{{ $setting->locale }}">
                                            <input type="hidden" name="currency_symbol" id="currencySymbol" value="{{ $setting->currency_symbol }}">
                                            <div class="col-md-6">
                                                <label>Decimal Separator</label>
                                                <input type="text" name="decimal_separator" class="form-control"
                                                    value="{{ $setting->decimal_separator }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Thousand Separator</label>
                                                <input type="text" name="thousand_separator" class="form-control"
                                                    value="{{ $setting->thousand_separator }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Decimal Places</label>
                                                <input type="number" name="decimal_places" class="form-control"
                                                    value="{{ $setting->decimal_places }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Currency Position</label>
                                                <select name="position" class="form-select" required>
                                                    <option value="prefix" {{ $setting->position == 'prefix' ? 'selected' : '' }}>Prefix ($100)</option>
                                                    <option value="suffix" {{ $setting->position == 'suffix' ? 'selected' : '' }}>Suffix (100$)</option>
                                                </select>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectedCurrency = document.getElementById('selectedCurrency');
        const currencyCodeInput = document.getElementById('currencyCode');
        const localeInput = document.getElementById('locale');
        const currencySymbolInput = document.getElementById('currencySymbol');

        function updateHiddenFields() {
            const selectedOption = selectedCurrency.options[selectedCurrency.selectedIndex];
            currencyCodeInput.value = selectedOption.value;
            localeInput.value = selectedOption.dataset.locale;
            currencySymbolInput.value = selectedOption.dataset.symbol;
        }

        selectedCurrency.addEventListener('change', updateHiddenFields);

        // Initialize on page load
        updateHiddenFields();
    });
</script>
@endpush
