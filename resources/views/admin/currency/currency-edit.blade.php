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
                                                <label>Currency Symbol</label>
                                                <input type="text" name="currency_symbol" class="form-control"
                                                    value="{{ $setting->currency_symbol }}" required>
                                            </div>
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
                                        </div>
                                    </form>
                                </div>
                                <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel"
                                    aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="confirmModalLabel">Confirm Changes</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to update the currency settings?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Cancel</button>
                                                <button type="button" class="btn btn-primary" id="confirmSubmit">Yes,
                                                    Save</button>
                                            </div>
                                        </div>
                                    </div>
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
@endsection
