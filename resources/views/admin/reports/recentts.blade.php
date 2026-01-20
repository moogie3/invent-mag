@extends('admin.layouts.base') @section('title', __('messages.all_transactions'))

@section('content')
    <div class="page-wrapper">
        <!-- Page Header -->
        <div class="page-header d-print-none" style="position: relative; z-index: 20;">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            {{ __('messages.transaction_management') }}
                        </div>
                        <h2 class="page-title">
                            <i class="ti ti-history fs-3 me-2 text-primary"></i>
                            {{ __('messages.all_transactions') }}
                        </h2>
                    </div>
                    <div class="col-auto ms-auto d-print-none">
                        <div class="btn-list">
                            <div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle" type="button"
                                    id="exportRecentTransactionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-download me-2"></i> {{ __('messages.export') }}
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="exportRecentTransactionsDropdown">
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="exportRecentTransactions('pdf')">
                                            Export as PDF
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="exportRecentTransactions('csv')">
                                            Export as CSV
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-outline-primary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown">
                                    <i class="ti ti-filter me-1"></i>
                                    {{ __('messages.filter') }}
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 300px;">
                                    <form id="filterForm" method="GET">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('messages.transaction_type') }}</label>
                                            <select name="type" class="form-select">
                                                <option value="">{{ __('messages.all_types') }}</option>
                                                <option value="sale" {{ $type == 'sale' ? 'selected' : '' }}>
                                                    {{ __('messages.sales') }}</option>
                                                <option value="purchase" {{ $type == 'purchase' ? 'selected' : '' }}>
                                                    {{ __('messages.purchasing') }}</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('messages.payment_status') }}</label>
                                            <select name="status" class="form-select">
                                                <option value="">{{ __('messages.all_status') }}</option>
                                                <option value="Paid" {{ $status == 'Paid' ? 'selected' : '' }}>
                                                    {{ __('messages.paid') }}</option>
                                                <option value="Partial" {{ $status == 'Partial' ? 'selected' : '' }}>
                                                    {{ __('messages.partial') }}</option>
                                                <option value="Unpaid" {{ $status == 'Unpaid' ? 'selected' : '' }}>
                                                    {{ __('messages.unpaid') }}</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('messages.date_range') }}</label>
                                            <select name="date_range" class="form-select">
                                                <option value="all" {{ $date_range == 'all' ? 'selected' : '' }}>
                                                    {{ __('messages.all_time') }}</option>
                                                <option value="today" {{ $date_range == 'today' ? 'selected' : '' }}>
                                                    {{ __('messages.today') }}</option>
                                                <option value="this_week"
                                                    {{ $date_range == 'this_week' ? 'selected' : '' }}>
                                                    {{ __('messages.this_week') }}
                                                </option>
                                                <option value="this_month"
                                                    {{ $date_range == 'this_month' ? 'selected' : '' }}>
                                                    {{ __('messages.this_month') }}
                                                </option>
                                                <option value="last_month"
                                                    {{ $date_range == 'last_month' ? 'selected' : '' }}>
                                                    {{ __('messages.last_month') }}
                                                </option>
                                                <option value="custom" {{ $date_range == 'custom' ? 'selected' : '' }}>
                                                    {{ __('messages.custom_range') }}
                                                </option>
                                            </select>
                                        </div>
                                        <div id="customDateRange" class="mb-3"
                                            style="display: {{ $date_range == 'custom' ? 'block' : 'none' }};">
                                            <div class="row">
                                                <div class="col">
                                                    <label class="form-label">{{ __('messages.from') }}</label>
                                                    <input type="date" name="start_date" class="form-control"
                                                        value="{{ $start_date }}">
                                                </div>
                                                <div class="col">
                                                    <label class="form-label">{{ __('messages.to') }}</label>
                                                    <input type="date" name="end_date" class="form-control"
                                                        value="{{ $end_date }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary flex-fill">
                                                <i class="ti ti-search me-1"></i>
                                                {{ __('messages.apply') }}
                                            </button>
                                            <a href="{{ route('admin.reports.recent-transactions') }}"
                                                class="btn btn-outline-secondary">
                                                <i class="ti ti-x me-1"></i>
                                                {{ __('messages.clear') }}
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Body -->
        <div class="page-body">
            <div class="container-xl">
                <!-- Summary Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">{{ __('messages.total_transactions') }}</div>
                                    <div class="ms-auto">
                                        <div class="avatar avatar-sm bg-primary-lt">
                                            <i class="ti ti-receipt-2 fs-3"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="h2 mb-2">{{ number_format($summary['total_count'] ?? 0) }}</div>
                                <div class="d-flex mb-2">
                                    <div class="text-muted">{{ __('messages.sales') }}:
                                        {{ number_format($summary['sales_count'] ?? 0) }}</div>
                                    <div class="ms-2 text-muted">{{ __('messages.purchasing') }}:
                                        {{ number_format($summary['purchases_count'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">{{ __('messages.total_amount') }}</div>
                                    <div class="ms-auto">
                                        <div class="avatar avatar-sm bg-success-lt">
                                            <i class="ti ti-currency-dollar fs-3"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="h2 mb-2">
                                    {{ \App\Helpers\CurrencyHelper::format($summary['total_amount'] ?? 0) }}</div>
                                <div class="d-flex mb-2">
                                    <div class="text-success">{{ __('messages.revenue') }}:
                                        {{ \App\Helpers\CurrencyHelper::format($summary['sales_amount'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">{{ __('messages.paid_transactions') }}</div>
                                    <div class="ms-auto">
                                        <div class="avatar avatar-sm bg-success-lt">
                                            <i class="ti ti-check fs-3"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="h2 mb-2">{{ number_format($summary['paid_count'] ?? 0) }}</div>
                                <div class="d-flex mb-2">
                                    <div class="text-success">
                                        {{ \App\Helpers\CurrencyHelper::format($summary['paid_amount'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">{{ __('messages.outstanding') }}</div>
                                    <div class="ms-auto">
                                        <div class="avatar avatar-sm bg-red-lt">
                                            <i class="ti ti-clock fs-3"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="h2 mb-2">{{ number_format($summary['unpaid_count'] ?? 0) }}</div>
                                <div class="d-flex mb-2">
                                    <div class="text-warning">
                                        {{ \App\Helpers\CurrencyHelper::format($summary['unpaid_amount'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="card">
                    <div id="bulkActionsBar" class="bulk-actions-bar border-bottom sticky-top" style="display: none;">
                        <div class="px-4 py-3">
                            <div class="row align-items-center">
                                <div class="col-lg-6 col-md-12">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="selection-indicator rounded-circle d-flex align-items-center justify-content-center me-3">
                                            <i class="ti ti-checklist text-white" style="font-size: 16px;"></i>
                                        </div>
                                        <div>
                                            <div class="selection-text">
                                                <span id="selectedCount" class="text-primary">0</span>
                                                <span class="text-muted">{{ __('messages.transactions_selected') }}</span>
                                            </div>
                                            <div class="selection-subtext">
                                                {{ __('messages.choose_action_apply_selected') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12">
                                    <div
                                        class="d-flex flex-wrap justify-content-lg-end justify-content-center gap-2 mt-lg-0 mt-2">
                                        <button onclick="bulkMarkAsPaid()"
                                            class="btn btn-success action-btn d-flex align-items-center">
                                            <i class="ti ti-check me-2"></i> {{ __('messages.mark_as_paid') }}
                                        </button>
                                        <div class="dropdown">
                                            <button class="btn btn-secondary dropdown-toggle" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-download me-2"></i> {{ __('messages.export') }}
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="#"
                                                        onclick="bulkExport('pdf')">Export as PDF
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#"
                                                        onclick="bulkExport('csv')">Export as CSV
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                        <button onclick="clearSelection()"
                                            class="btn btn-outline-secondary action-btn d-flex align-items-center">
                                            <i class="ti ti-x me-2"></i> {{ __('messages.clear_selection') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-list me-2"></i>
                            {{ __('messages.transaction_history') }}
                        </h3>
                        <div class="card-actions">
                            <div class="input-group input-group-md" style="max-width: 300px;">
                                <input type="text" class="form-control"
                                    placeholder="{{ __('messages.search_transactions') }}" id="searchInput"
                                    value="{{ $search }}">
                                <button class="btn btn-outline-secondary" type="button" onclick="searchTransactions()">
                                    <i class="ti ti-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Active Filters Display -->
                    @if ($type || $status || ($date_range && $date_range != 'all') || $search)
                        <div class="card-body border-bottom py-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="text-muted small">{{ __('messages.active_filters') }}</span>
                                @if ($type)
                                    <span class="badge bg-primary-lt">
                                        {{ __('messages.type') }}: {{ ucfirst($type) }}
                                        <a href="{{ request()->fullUrlWithQuery(['type' => null]) }}"
                                            class="btn-close ms-1" style="font-size: 0.75em;"></a>
                                    </span>
                                @endif
                                @if ($status)
                                    <span class="badge bg-info-lt">
                                        {{ __('messages.status') }}: {{ $status }}
                                        <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}"
                                            class="btn-close ms-1" style="font-size: 0.75em;"></a>
                                    </span>
                                @endif
                                @if ($date_range && $date_range != 'all')
                                    <span class="badge bg-warning-lt">
                                        {{ __('messages.period') }}:
                                        {{ ucfirst(str_replace('_', ' ', $date_range)) }}
                                        <a href="{{ request()->fullUrlWithQuery(['date_range' => null, 'start_date' => null, 'end_date' => null]) }}"
                                            class="btn-close ms-1" style="font-size: 0.75em;"></a>
                                    </span>
                                @endif
                                @if ($search)
                                    <span class="badge bg-secondary-lt">
                                        {{ __('messages.search_label') }} "{{ $search }}"
                                        <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}"
                                            class="btn-close ms-1" style="font-size: 0.75em;"></a>
                                    </span>
                                @endif
                                <a href="{{ route('admin.reports.recent-transactions') }}"
                                    class="btn btn-sm btn-outline-secondary ms-2">
                                    <i class="ti ti-x me-1"></i>
                                    {{ __('messages.clear_filters') }}
                                </a>
                            </div>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-vcenter table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="w-1">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'type', 'direction' => $sort == 'type' && $direction == 'asc' ? 'desc' : 'asc']) }}"
                                            class="table-sort {{ $sort == 'type' ? 'table-sort-' . $direction : '' }}">
                                            {{ __('messages.type') }}
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'invoice', 'direction' => $sort == 'invoice' && $direction == 'asc' ? 'desc' : 'asc']) }}"
                                            class="table-sort {{ $sort == 'invoice' ? 'table-sort-' . $direction : '' }}">
                                            {{ __('messages.invoice') }}
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'customer_supplier', 'direction' => $sort == 'customer_supplier' && $direction == 'asc' ? 'desc' : 'asc']) }}"
                                            class="table-sort {{ $sort == 'customer_supplier' ? 'table-sort-' . $direction : '' }}">
                                            {{ __('messages.customer_supplier') }}
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'date', 'direction' => $sort == 'date' && $direction == 'asc' ? 'desc' : 'asc']) }}"
                                            class="table-sort {{ $sort == 'date' ? 'table-sort-' . $direction : '' }}">
                                            {{ __('messages.date') }}
                                        </a>
                                    </th>
                                    <th class="text-end">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'amount', 'direction' => $sort == 'amount' && $direction == 'asc' ? 'desc' : 'asc']) }}"
                                            class="table-sort {{ $sort == 'amount' ? 'table-sort-' . $direction : '' }}">
                                            {{ __('messages.amount') }}
                                        </a>
                                    </th>
                                    <th class="text-center">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => $sort == 'status' && $direction == 'asc' ? 'desc' : 'asc']) }}"
                                            class="table-sort {{ $sort == 'status' ? 'table-sort-' . $direction : '' }}">
                                            {{ __('messages.status') }}
                                        </a>
                                    </th>
                                    <th class="w-1">{{ __('messages.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transactions as $transaction)
                                    <tr data-id="{{ $transaction->id ?? '' }}">
                                        <td>
                                            <input class="form-check-input row-checkbox" type="checkbox"
                                                value="{{ $transaction->id ?? '' }}" data-type="{{ $transaction->type }}">
                                        </td>
                                        <td>
                                            <span
                                                class="avatar avatar-sm {{ $transaction->type == 'sale' ? 'bg-success' : 'bg-info' }}-lt">
                                                <i
                                                    class="ti {{ $transaction->type == 'sale' ? 'ti-arrow-up' : 'ti-arrow-down' }}"></i>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $transaction->invoice }}</div>
                                            <div class="small text-muted">
                                                {{ $transaction->type == 'sale' ? __('messages.sales') : __('messages.purchasing') }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $transaction->customer_supplier }}</div>
                                            @if (isset($transaction->contact_info) && $transaction->contact_info)
                                                <div class="small text-muted">{{ $transaction->contact_info }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold">
                                                {{ \Carbon\Carbon::parse($transaction->date)->translatedFormat('M d, Y') }}
                                            </div>
                                            <div class="small text-muted">
                                                {{ \Carbon\Carbon::parse($transaction->date)->translatedFormat('h:i') }}
                                            </div>
                                        </td>
                                        <td class="text-end fw-medium">
                                            {{ \App\Helpers\CurrencyHelper::format($transaction->amount) }}
                                            @if (isset($transaction->due_amount) && $transaction->due_amount > 0)
                                                <div class="small text-danger">
                                                    {{ __('messages.due') }}:
                                                    {{ \App\Helpers\CurrencyHelper::format($transaction->due_amount) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="badge {{ $transaction->status == 'Paid' ? 'bg-success' : ($transaction->status == 'Partial' ? 'bg-warning' : 'bg-danger') }}-lt">
                                                {{ __('messages.' . strtolower($transaction->status)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <a href="#" class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="{{ $transaction->view_url ?? '#' }}">
                                                        <i class="ti ti-eye me-2"></i>
                                                        {{ __('messages.view_details') }}
                                                    </a>
                                                    @if (isset($transaction->edit_url))
                                                        <a class="dropdown-item" href="{{ $transaction->edit_url }}">
                                                            <i class="ti ti-edit me-2"></i>
                                                            {{ __('messages.edit') }}
                                                        </a>
                                                    @endif
                                                    @if (isset($transaction->print_url))
                                                        <a class="dropdown-item" href="{{ $transaction->print_url }}"
                                                            target="_blank">
                                                            <i class="ti ti-printer me-2"></i>
                                                            {{ __('messages.print') }}
                                                        </a>
                                                    @endif
                                                    @if ($transaction->status != 'Paid')
                                                        <a class="dropdown-item text-success" href="#"
                                                            onclick="showMarkAsPaidModal('{{ $transaction->id ?? '' }}', '{{ $transaction->type }}', '{{ $transaction->invoice }}', '{{ $transaction->customer_supplier }}', '{{ \App\Helpers\CurrencyHelper::format($transaction->amount) }}')">
                                                            <i class="ti ti-check me-2"></i>
                                                            {{ __('messages.mark_as_paid') }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <div class="empty">
                                                <div class="empty-img">
                                                    <i class="ti ti-receipt-off" style="font-size: 3rem;"></i>
                                                </div>
                                                <p class="empty-title">{{ __('messages.no_transactions_found') }}</p>
                                                <p class="empty-subtitle text-muted">
                                                    @if (request()->hasAny(['type', 'status', 'date_range', 'search']))
                                                        {{ __('messages.try_adjusting_search_criteria') }}
                                                    @else
                                                        {{ __('messages.no_transactions_recorded') }}
                                                    @endif
                                                </p>
                                                @if (request()->hasAny(['type', 'status', 'date_range', 'search']))
                                                    <div class="empty-action">
                                                        <a href="{{ route('admin.reports.recent-transactions') }}"
                                                            class="btn btn-primary">
                                                            <i class="ti ti-x me-1"></i>
                                                            {{ __('messages.clear_filters') }}
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if (method_exists($transactions, 'links') && $transactions->hasPages())
                        <div class="card-footer d-flex align-items-center">
                            <p class="m-0 text-muted">
                                {{ __('messages.pagination_showing_entries', ['first' => $transactions->firstItem(), 'last' => $transactions->lastItem(), 'total' => $transactions->total()]) }}
                            </p>
                            <div class="ms-auto">
                                {{ $transactions->appends(request()->query())->links('vendor.pagination.tabler') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@include('admin.layouts.modals.recentts.recentmodals') @endsection
