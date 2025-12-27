@extends('admin.layouts.errorbase')

@section('title', 'Error 500')

@section('content')
    <div class="page">
        <div class="container-tight py-4">
            <div class="empty">
                <div class="mb-5">
                    <a class="h2 navbar-brand navbar-brand-autodark"><i
                            class="ti ti-brand-minecraft fs-1 me-2"></i>Invent-MAG</a>
                </div>
                <div class="empty-header">500</div>
                <p class="empty-title">Oops… You just found an error page</p>
                <p class="empty-subtitle">
                    We are sorry but our server encountered an internal error
                </p>
            </div>
        </div>
    </div>
@endsection