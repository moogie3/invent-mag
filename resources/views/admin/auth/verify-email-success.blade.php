@extends('admin.layouts.authbase')

@section('title', __('messages.email_verified_success_title'))

@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">{{ __('messages.email_verified_success_title') }}</h2>

            <p class="text-secondary text-center mb-4">
                {{ __('messages.email_verified_success_message') }}
            </p>

            <div class="d-flex justify-content-center mt-4">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-primary w-50">
                    {{ __('messages.go_to_dashboard') }}
                </a>
            </div>
        </div>
    </div>
@endsection