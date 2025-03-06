@extends('admin.layouts.errorbase')

@section('title', 'Error 500')

@section('content')
    <div class="page page-center">
        <div class="container-tight py-4">
            <div class="empty">
                <div class="empty-header">500</div>
                <p class="empty-title">Oops… You just found an error page</p>
                <p class="empty-subtitle text-secondary">
                    We are sorry but our server encountered an internal error
                </p>
            </div>
        </div>
    </div>
@endsection
