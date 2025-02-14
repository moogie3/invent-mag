@extends('admin.layouts.base')

@section('title', 'Currency Settings')

@section('content')
    <div class="page-wrapper">
        <!-- Page header -->
        <div class="page-header">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            Overview
                        </div>
                        <h2 class="page-title">
                            Settings
                        </h2>
                    </div>
                </div>
            </div>
        </div>
        <!-- Page body -->
        <div class="page-body">
            <div class="container-xl">
                <div class="row row-deck row-cards">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-body">
                                @if(session('success'))
                                    <div class="alert alert-success">{{ session('success') }}</div>
                                @endif
                                <form action="{{ route('admin.currency.update') }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label>Currency Symbol</label>
                                        <input type="text" name="currency_symbol" class="form-control" value="{{ $setting->currency_symbol }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Decimal Separator</label>
                                        <input type="text" name="decimal_separator" class="form-control" value="{{ $setting->decimal_separator }}"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Thousand Separator</label>
                                        <input type="text" name="thousand_separator" class="form-control" value="{{ $setting->thousand_separator }}"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Decimal Places</label>
                                        <input type="number" name="decimal_places" class="form-control" value="{{ $setting->decimal_places }}" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Save Settings</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
