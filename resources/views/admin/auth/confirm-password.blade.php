@extends('admin.layouts.authbase')

@section('title', 'Confirm Password')

@section('content')
    <div class="card card-md border-0 shadow-sm rounded-3">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">Confirm Password</h2>
            <p class="text-secondary mb-4">Please confirm your password before continuing.</p>
            <form action="{{ route('password.confirm.store') }}" method="POST" autocomplete="off" novalidate>
                @csrf
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" placeholder="Your Password" required>
                    @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">Confirm</button>
                </div>
            </form>
        </div>
    </div>
@endsection
