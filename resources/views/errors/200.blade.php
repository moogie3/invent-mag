@extends('admin.layouts.errorbase')

@section('title', 'Error 200')

@section('content')
    <div class="page">
        <div class="container-tight py-4">
            <div class="empty">
                <div class="mb-5">
                    <a class="h2 navbar-brand navbar-brand-autodark"><i
                            class="ti ti-brand-minecraft fs-1 me-2"></i>Invent-MAG</a>
                </div>
                <div class="empty-header">200</div>
                <p class="empty-title">Success! (But you're on an error page?)</p>
                <p class="empty-subtitle">
                    The request was successful, but this page is not meant to be seen directly.
                </p>
            </div>
        </div>
    </div>
@endsection
