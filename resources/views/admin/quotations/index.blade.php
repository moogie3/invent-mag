@extends('admin.layouts.base')
@section('title', __('messages.quotations'))
@section('header')
<meta name="turbo-cache-control" content="no-cache">
@endsection
@section('content')
    <div class="page-wrapper">
        @include('admin.quotations.partials.index.header')
        @include('admin.quotations.partials.index.page-body')
    </div>
    @include('admin.layouts.modals.quotations.quotationsmodals')
    @include('admin.layouts.modals.quotations.quotationsmodals-view')
@endsection

@push('scripts')
<script>
document.addEventListener('turbo:load', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const entriesSelect = document.getElementById('entriesSelect');

    function applyFilters() {
        const params = new URLSearchParams(window.location.search);
        if (searchInput?.value) params.set('search', searchInput.value);
        else params.delete('search');
        if (statusFilter?.value) params.set('status', statusFilter.value);
        else params.delete('status');
        if (dateFrom?.value) params.set('date_from', dateFrom.value);
        else params.delete('date_from');
        if (dateTo?.value) params.set('date_to', dateTo.value);
        else params.delete('date_to');
        if (entriesSelect?.value) params.set('entries', entriesSelect.value);
        else params.delete('entries');
        params.delete('page');
        window.location.href = window.location.pathname + '?' + params.toString();
    }

    let searchTimeout;
    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });
    statusFilter?.addEventListener('change', applyFilters);
    dateFrom?.addEventListener('change', applyFilters);
    dateTo?.addEventListener('change', applyFilters);
    entriesSelect?.addEventListener('change', applyFilters);
});
</script>
@endpush
