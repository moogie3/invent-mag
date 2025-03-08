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
                                <div class="card-body">
                                    <h4 class="subheader">Business settings</h4>
                                    <div class="list-group list-group-transparent">
                                        <a href="{{ route('admin.profile.edit') }}"
                                            class="list-group-item list-group-item-action d-flex align-items-center">My
                                            Account</a>
                                        <a href="{{ route('admin.notifications') }}"
                                            class="list-group-item list-group-item-action d-flex align-items-center">My
                                            Notifications</a>
                                        <a href="{{ route('admin.currency.edit') }}"
                                            class="list-group-item list-group-item-action d-flex align-items-center active">Currency
                                            Settings</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-9 d-flex flex-column">
                                <div class="card-body">
                                    <form action="{{ route('admin.currency.update') }}" method="POST">
                                        @csrf
                                        <h2 class="mb-4">Currency Settings</h2>
                                        <div class="row g-3">
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
                                        <div class="card-footer bg-transparent mt-auto">
                                            <div class="btn-list justify-content-end">
                                                <button type="submit" class="btn btn-primary">Save
                                                    Settings</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
