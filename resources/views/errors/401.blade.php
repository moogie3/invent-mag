@extends('admin.layouts.errorbase')

@section('title', 'Error 401')

@section('content')
    @php
        $baseDomain = env('APP_URL_BASE', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?? 'localhost');
        $loginRoute = request()->getHost() === 'super.' . $baseDomain
            ? route('superadmin.login')
            : route('admin.login');
    @endphp
    <div class="page">
        <div class="container-tight py-4">
            <div class="empty">
                <div class="mb-5">
                    <a class="h2 navbar-brand navbar-brand-autodark"><i
                            class="ti ti-brand-minecraft fs-1 me-2"></i>Invent-MAG</a>
                </div>
                <div class="empty-header">401</div>
                <p class="empty-title">Unauthorized</p>
                <p class="empty-subtitle">
                    You need to authenticate to access this resource.
                </p>
                <div class="empty-action">
                    <a href="{{ $loginRoute }}" class="btn btn-primary">
                        <i class="ti ti-arrow-left me-2"></i>
                        Go to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
