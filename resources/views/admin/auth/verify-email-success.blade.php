@extends('admin.layouts.authbase')

@section('title', __('messages.email_verified_success_title'))

@section('content')
    <div class="card card-md border-0 shadow-sm rounded-3">
        <div class="card-body">
            <h2 class="h2 text-center mb-5">{{ __('messages.email_verified_success_title') }}</h2>

            <p class="text-secondary text-center mb-4">
                {{ __('messages.email_verified_success_message') }}
            </p>

            <div class="form-footer">
                <div class="d-flex justify-content-center mt-4">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary w-100">
                        {{ __('messages.go_to_dashboard') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="text-center text-secondary mt-3">
        <form method="POST" action="{{ route('admin.logout') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-link text-secondary p-0 border-0" style="text-decoration: none;">
                Back to Login
            </button>
        </form>
    </div>
@endsection