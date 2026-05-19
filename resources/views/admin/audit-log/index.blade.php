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
<script nonce="{{ $cspNonce ?? '' }}">
    window.auditLogTranslations = {
        timestamp: '{{ __("messages.timestamp") }}',
        user: '{{ __("messages.user") }}',
        module: '{{ __("messages.module") }}',
        action: '{{ __("messages.action") }}',
        description: '{{ __("messages.description") }}',
        ip_address: '{{ __("messages.ip_address") }}',
        user_agent: '{{ __("messages.user_agent") }}',
        before: '{{ __("messages.before") }}',
        after: '{{ __("messages.after") }}',
        error_loading_details: '{{ __("messages.error_loading_details") }}',
        close: '{{ __("messages.close") }}'
    };
</script>
@vite('resources/js/admin/audit-log.js')
@endpush
