@extends('admin.layouts.authbase')

@section('title', 'Login')

@section('content')
    <div class="card card-md border-0 shadow-sm rounded-3">
        <div class="card-body">
            <h2 class="h2 text-center mb-5">Login to your account</h2>
            <form action="{{ route('admin.login') }}" method="POST" autocomplete="off" novalidate>
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" placeholder="Your email" autocomplete="off"
                        required value="{{ old('email') }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-2">
                    <label class="form-label" for="password">
                        Password
                    </label>
                    <div class="input-group input-group-flat">
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Your password"
                            autocomplete="off" id="password" required>
                        <span class="input-group-text">
                            <a href="#" class="link-secondary" title="Show password" data-bs-toggle="tooltip"
                                id="toggle-password">
                                <i class="ti ti-eye fs-1"></i>
                            </a>
                        </span>
                    </div>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100" id="loginSubmitBtn">
                        <span class="login-btn-text">Sign in</span>
                        <span class="login-btn-loading d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status" style="width: 14px; height: 14px;"></span>
                            Signing in...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center text-secondary mt-3">
        <a href="{{ route('admin.password.request') }}">Forgot password?</a>
    </div>
@endsection
