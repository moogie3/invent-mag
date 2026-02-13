@extends('admin.layouts.authbase')

@section('title', 'Reset Password')

@section('content')
    <div class="card card-md border-0 shadow-sm rounded-3">
        <div class="card-body">
            <h2 class="h2 text-center mb-5">Forgot Password</h2>
            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="{{ old('email', request()->email) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" id="password" required>
                    <!-- Real-time strength validation feedback -->
                    <div class="invalid-feedback" id="password-strength-error" style="display: none;">
                        {{ __('messages.password_strength_requirements') }}
                    </div>
                    @error('password')
                        <div class="invalid-feedback" style="display: block;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" required>
                    <!-- Password mismatch feedback -->
                    <div class="invalid-feedback" id="password-mismatch-error" style="display: none;">
                        {{ __('messages.passwords_do_not_match') }}
                    </div>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100" id="updatePasswordBtn">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
@endsection
