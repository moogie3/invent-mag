@extends('admin.layouts.base')

@section('title', __('messages.audit_log'))

@section('header')
<meta name="turbo-cache-control" content="no-cache">
@endsection

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.partials.audit-log.index.header')
        @include('admin.layouts.partials.audit-log.index.page-body')
    </div>
    @include('admin.layouts.modals.audit-log.auditlogmodals')
@endsection

@push('scripts')
@vite('resources/js/admin/audit-log.js')
@endpush
