@extends('admin.layouts.authbase')

@section('title', 'Forgot Password')

@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-5">Forgot Password</h2>
            <form action="{{ route('admin.password.email') }}" method="POST" autocomplete="off" novalidate>
                @csrf
                <p class="text-secondary mb-4">Enter your email address and password reset form will be emailed to you.
                </p>
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input type="email" class="form-control" name="email" placeholder="Enter email" required>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-mail fs-2 me-2"></i>
                        Send me new password
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center text-secondary mt-3">
        <a href="{{ route('admin.login') }}">Send me back</a> to the sign-in screen.
    </div>
@endsection
