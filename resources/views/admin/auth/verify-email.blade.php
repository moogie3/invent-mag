@extends('admin.layouts.authbase')

@section('title', 'Email Verification')

@section('content')
    <div class="card card-md border-0 shadow-sm rounded-3">
        <div class="card-body">
            <h2 class="h2 text-center mb-5">Verify Your Email Address</h2>

            <p class="text-center mb-2">Thanks for signing up!</p>

            <p class="text-secondary mb-4 text-center">
                Quick step before we get started – just click the verification link we sent to your email. Didn't get it? No
                worries, we can send you another one!
            </p>

            @if (session('status') == 'verification-link-sent')
                <div class="alert alert-success" role="alert">
                    {{ __('messages.verification_link_sent') }}
                </div>
            @endif

            <div class="form-footer">
                <div class="d-grid mt-4">
                    <form action="{{ route('verification.send') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100" id="resendVerificationBtn">
                            <i class="ti ti-mail me-2"></i>
                            {{ __('messages.resend_verification_email') }}
                        </button>
                    </form>
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
