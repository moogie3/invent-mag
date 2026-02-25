@extends('admin.layouts.authbase')

@section('title', 'Email Verification')

@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">Verify Your Email Address</h2>

            <p class="text-center mb-2">Thanks for signing up!</p>

            <p class="text-secondary mb-4 text-center">
                Quick step before we get started – just click the verification link we sent to your email. Didn't get it? No
                worries, we can send you another one!
            </p>

            @if (session('status') == 'verification-link-sent')
                <div class="alert alert-success" role="alert">
                    A new verification link has been sent to the email address you provided during registration.
                </div>
            @endif

            <div class="d-flex gap-3 py-3 mt-4">
                <form class="w-100" action="{{ route('verification.send') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-mail me-2"></i>
                        Resend Verification Email
                    </button>
                </form>

                <form class="w-100" method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary w-100">
                        <i class="ti ti-logout me-2"></i>
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
