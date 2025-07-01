@extends('admin.layouts.errorbase')

@section('title', 'Error 403')

@section('content')
    <div class="page page-center">
        <div class="container-tight py-4">
            <div class="empty">
                <div class="mb-5">
                    <a class="h2 navbar-brand navbar-brand-autodark">
                        <i class="ti ti-brand-minecraft fs-1 me-2"></i>Invent-MAG
                    </a>
                </div>
                <div class="empty-header">403</div>
                <p class="empty-title">Forbidden</p>
                <p class="empty-subtitle text-secondary">
                    You do not have permission to access this resource.
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
