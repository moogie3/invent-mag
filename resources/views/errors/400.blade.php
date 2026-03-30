@extends('admin.layouts.errorbase')

@section('title', 'Error 400')

@section('content')
    <div class="page">
        <div class="container-tight py-4">
            <div class="empty">
                <div class="mb-5">
                    <a class="h2 navbar-brand navbar-brand-autodark"><i
                            class="ti ti-brand-minecraft fs-1 me-2"></i>Invent-MAG</a>
                </div>
                <div class="empty-header">400</div>
                <p class="empty-title">Bad Request</p>
                <p class="empty-subtitle">
                    The server could not understand the request due to invalid syntax.
                </p>
                <div class="empty-action">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                        <i class="ti ti-arrow-left me-2"></i>
                        Go back
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
