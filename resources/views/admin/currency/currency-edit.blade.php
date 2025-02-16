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
                    <div class="col-md-6">
                        <div class="card card-primary">
                            <div class="card-body">
                                @if(session('success'))
                                    <div class="alert alert-success">{{ session('success') }}</div>
                                @endif
                                <form action="{{ route('admin.currency.update') }}" method="POST">
                                    @csrf
                                    <fieldset class="form-fieldset container-xl">
                                    <div class="col-md-12 mb-3">
                                        <label>Currency Symbol</label>
                                        <input type="text" name="currency_symbol" class="form-control" value="{{ $setting->currency_symbol }}" required>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label>Decimal Separator</label>
                                        <input type="text" name="decimal_separator" class="form-control" value="{{ $setting->decimal_separator }}"
                                            required>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label>Thousand Separator</label>
                                        <input type="text" name="thousand_separator" class="form-control" value="{{ $setting->thousand_separator }}"
                                            required>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label>Decimal Places</label>
                                        <input type="number" name="decimal_places" class="form-control" value="{{ $setting->decimal_places }}" required>
                                    </div>
                                    </fieldset>
                                    <button type="submit" class="btn btn-primary">Save Settings</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3>Profile Settings</h3>
                            </div>
                            <div class="card-body">
                                @if(session('success_profile'))
                                    <div class="alert alert-success">{{ session('success_profile') }}</div>
                                @endif
                                <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3">
                                        <label>Avatar</label><br>
                                        <img src="{{ auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : asset('default-avatar.png') }}"
                                            alt="Avatar" class="img-thumbnail mb-2" width="100">
                                        <input type="file" name="avatar" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label>Name</label>
                                        <input type="text" name="name" class="form-control" value="{{ auth()->user()->name }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control" value="{{ auth()->user()->email }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>New Password</label>
                                        <input type="password" name="password" class="form-control" placeholder="Enter new password">
                                    </div>
                                    <div class="mb-3">
                                        <label>Confirm Password</label>
                                        <input type="password" name="password_confirmation" class="form-control"
                                            placeholder="Confirm new password">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
