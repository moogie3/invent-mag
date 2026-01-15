@extends('admin.layouts.authbase')

@section('title', 'Register')

@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">Create New Account</h2>
            <form action="{{ route('admin.register.post') }}" method="POST" autocomplete="off" novalidate>
                @csrf
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Enter name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter email" required
                        value="{{ old('email') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group input-group-flat">
                        <input type="password" name="password" class="form-control" placeholder="Password"
                            autocomplete="off" id="password" required>
                        <span class="input-group-text">
                            <a href="#" class="link-secondary" title="Show password" data-bs-toggle="tooltip"
                                id="toggle-password">
                                <i class="ti ti-eye fs-1"></i>
                            </a>
                        </span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm password"
                        required>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">Create New Account</button>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center text-secondary mt-3">
        Already have an account? <a href="{{ route('admin.login') }}" tabindex="-1">Sign in</a>
    </div>
@endsection
