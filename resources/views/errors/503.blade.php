@extends('admin.layouts.errorbase')

@section('title', 'Maintenance')

@section('content')
    <div class="page">
        <div class="container-tight py-4">
            <div class="empty">
                <div class="mb-5">
                    <a class="h2 navbar-brand navbar-brand-autodark"><i
                            class="ti ti-brand-minecraft fs-1 me-2"></i>Invent-MAG</a>
                </div>
                <div class="empty-header">503</div>
                <p class="empty-title">Temporarily down for maintenance</p>
                <p class="empty-subtitle">
                    Sorry for the inconvenience but we’re performing some maintenance at the moment. We’ll be back online
                    shortly!
                </p>
            </div>
        </div>
    </div>
@endsection
