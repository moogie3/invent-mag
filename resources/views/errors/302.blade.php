@extends('admin.layouts.errorbase')

@section('title', 'Error 302')

@section('content')
    <div class="page">
        <div class="container-tight py-4">
            <div class="empty">
                <div class="mb-5">
                    <a class="h2 navbar-brand navbar-brand-autodark"><i
                            class="ti ti-brand-minecraft fs-1 me-2"></i>Invent-MAG</a>
                </div>
                <div class="empty-header">302</div>
                <p class="empty-title">Oops… You've been redirected!</p>
                <p class="empty-subtitle">
                    The page you requested has been temporarily moved.
                </p>
            </div>
        </div>
    </div>
@endsection
