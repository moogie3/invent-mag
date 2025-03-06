@extends('admin.layouts.errorbase')

@section('title', 'Maintenance')

@section('content')
    <div class="page page-center">
        <div class="container-tight py-4">
            <div class="empty">
                <div class="empty-img"><img src="./static/illustrations/undraw_quitting_time_dm8t.svg" height="128"
                        alt="">
                </div>
                <div class="mb-5">
                    <a class="h2 navbar-brand navbar-brand-autodark"><i
                            class="ti ti-brand-minecraft fs-1 me-2"></i>Invent-MAG</a>
                </div>
                <p class="empty-title mb-3">Temporarily down for maintenance</p>
                <p class="empty-subtitle text-secondary">
                    Sorry for the inconvenience but we’re performing some maintenance at the moment. We’ll be back online
                    shortly!
                </p>
            </div>
        </div>
    </div>
@endsection
