@extends('admin.layouts.base') @section('title', 'All Transactions')

 @section('content')
    <div class="page-wrapper">
        <!-- Page Header -->
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            Transaction Management
                        </div>
                        <h2 class="page-title">
                            <i class="ti ti-history fs-3 me-2 text-primary"></i>
                            All Transactions
                        </h2>
                    </div>
                    <div class="col-auto ms-auto d-print-none">
                        <div class="btn-list">
                            <div class="dropdown">
                                <button class="btn btn-secondary" onclick="exportTransactions()">
                                    <i class="ti ti-printer me-1"></i>
                                    Export PDF
                                </button>
                                <button class="btn btn-outline-primary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown">
                                    <i class="ti ti-filter me-1"></i>
                                    Filter
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 300px;">
                                    <form id="filterForm" method="GET">
                                        <div class="mb-3">
                                            <label class="form-label">Transaction Type</label>
                                            <select name="type" class="form-select">
                                                <option value="">All Types</option>
                                                <option value="sale" {{ request('type') == 'sale' ? 'selected' : '' }}>
                                                    Sales</option>
                                                <option value="purchase"
                                                    {{ request('type') == 'purchase' ? 'selected' : '' }}>Purchases</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Payment Status</label>
                                            <select name="status" class="form-select">
                                                <option value="">All Status</option>
                                                <option value="Paid" {{ request('status') == 'Paid' ? 'selected' : '' }}>
                                                    Paid</option>
                                                <option value="Partial"
                                                    {{ request('status') == 'Partial' ? 'selected' : '' }}>Partial</option>
                                                <option value="Unpaid"
                                                    {{ request('status') == 'Unpaid' ? 'selected' : '' }}>Unpaid</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Date Range</label>
                                            <select name="date_range" class="form-select">
                                                <option value="all"
                                                    {{ request('date_range') == 'all' ? 'selected' : '' }}>All Time</option>
                                                <option value="today"
                                                    {{ request('date_range') == 'today' ? 'selected' : '' }}>Today</option>
                                                <option value="this_week"
                                                    {{ request('date_range') == 'this_week' ? 'selected' : '' }}>This Week
                                                </option>
                                                <option value="this_month"
                                                    {{ request('date_range') == 'this_month' ? 'selected' : '' }}>This Month
                                                </option>
                                                <option value="last_month"
                                                    {{ request('date_range') == 'last_month' ? 'selected' : '' }}>Last Month
                                                </option>
                                                <option value="custom"
                                                    {{ request('date_range') == 'custom' ? 'selected' : '' }}>Custom Range
                                                </option>
                                            </select>
                                        </div>
                                        <div id="customDateRange" class="mb-3"
                                            style="display: {{ request('date_range') == 'custom' ? 'block' : 'none' }};">
                                            <div class="row">
                                                <div class="col">
                                                    <label class="form-label">From</label>
                                                    <input type="date" name="start_date" class="form-control"
                                                        value="{{ request('start_date') }}">
                                                </div>
                                                <div class="col">
                                                    <label class="form-label">To</label>
                                                    <input type="date" name="end_date" class="form-control"
                                                        value="{{ request('end_date') }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary flex-fill">
                                                <i class="ti ti-search me-1"></i>
                                                Apply
                                            </button>
                                            <a href="{{ route('admin.transactions') }}" class="btn btn-outline-secondary">
                                                <i class="ti ti-x me-1"></i>
                                                Clear
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
                                    <div class="subheader">Total Transactions</div>
                                    <div class="ms-auto">
                                        <div class="avatar avatar-sm bg-primary-lt">
                                            <i class="ti ti-receipt-2 fs-3"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="h2 mb-2">{{ number_format($summary['total_count'] ?? 0) }}</div>
                                <div class="d-flex mb-2">
                                    <div class="text-muted">Sales: {{ number_format($summary['sales_count'] ?? 0) }}</div>
                                    <div class="ms-2 text-muted">Purchases:
                                        {{ number_format($summary['purchases_count'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Total Amount</div>
                                    <div class="ms-auto">
                                        <div class="avatar avatar-sm bg-success-lt">
                                            <i class="ti ti-currency-dollar fs-3"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="h2 mb-2">
                                    {{ \App\Helpers\CurrencyHelper::format($summary['total_amount'] ?? 0) }}</div>
                                <div class="d-flex mb-2">
                                    <div class="text-success">Revenue:
                                        {{ \App\Helpers\CurrencyHelper::format($summary['sales_amount'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Paid Transactions</div>
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
                                    <div class="subheader">Outstanding</div>
                                    <div class="ms-auto">
                                        <div class="avatar avatar-sm bg-warning">
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
                                                <span class="text-muted">transactions selected</span>
                                            </div>
                                            <div class="selection-subtext">Choose an action to apply to selected items
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12">
                                    <div
                                        class="d-flex flex-wrap justify-content-lg-end justify-content-center gap-2 mt-lg-0 mt-2">
                                        <button onclick="bulkMarkAsPaid()"
                                            class="btn btn-success action-btn d-flex align-items-center">
                                            <i class="ti ti-check me-2"></i> Mark as Paid
                                        </button>
                                        <button onclick="bulkExport()"
                                            class="btn btn-secondary action-btn d-flex align-items-center">
                                            <i class="ti ti-download me-2"></i> Export
                                        </button>
                                        <button onclick="clearSelection()"
                                            class="btn btn-outline-secondary action-btn d-flex align-items-center">
                                            <i class="ti ti-x me-2"></i> Clear Selection
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-list me-2"></i>
                            Transaction History
                        </h3>
                        <div class="card-actions">
                            <div class="input-group input-group-md" style="max-width: 300px;">
                                <input type="text" class="form-control" placeholder="Search transactions..."
                                    id="searchInput" value="{{ request('search') }}">
                                <button class="btn btn-outline-secondary" type="button" onclick="searchTransactions()">
                                    <i class="ti ti-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Active Filters Display -->
                    @if (request()->hasAny(['type', 'status', 'date_range', 'search']))
                        <div class="card-body border-bottom py-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="text-muted small">Active filters:</span>
                                @if (request('type'))
                                    <span class="badge bg-primary-lt">
                                        Type: {{ ucfirst(request('type')) }}
                                        <a href="{{ request()->fullUrlWithQuery(['type' => null]) }}"
                                            class="btn-close ms-1" style="font-size: 0.75em;"></a>
                                    </span>
                                @endif
                                @if (request('status'))
                                    <span class="badge bg-info-lt">
                                        Status: {{ request('status') }}
                                        <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}"
                                            class="btn-close ms-1" style="font-size: 0.75em;"></a>
                                    </span>
                                @endif
                                @if (request('date_range') && request('date_range') != 'all')
                                    <span class="badge bg-warning-lt">
                                        Period: {{ ucfirst(str_replace('_', ' ', request('date_range')))}}
                                        <a href="{{ request()->fullUrlWithQuery(['date_range' => null, 'start_date' => null, 'end_date' => null]) }}"
                                            class="btn-close ms-1" style="font-size: 0.75em;"></a>
                                    </span>
                                @endif
                                @if (request('search'))
                                    <span class="badge bg-secondary-lt">
                                        Search: "{{ request('search') }}"
                                        <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}"
                                            class="btn-close ms-1" style="font-size: 0.75em;"></a>
                                    </span>
                                @endif
                                <a href="{{ route('admin.transactions') }}"
                                    class="btn btn-sm btn-outline-secondary ms-2">
                                    <i class="ti ti-x me-1"></i>
                                    Clear filters
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
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'type', 'direction' => request('sort') == 'type' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                            class="table-sort {{ request('sort') == 'type' ? 'table-sort-' . request('direction', 'asc') : '' }}">
                                            Type
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'invoice', 'direction' => request('sort') == 'invoice' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                            class="table-sort {{ request('sort') == 'invoice' ? 'table-sort-' . request('direction', 'asc') : '' }}">
                                            Invoice
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'customer_supplier', 'direction' => request('sort') == 'customer_supplier' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                            class="table-sort {{ request('sort') == 'customer_supplier' ? 'table-sort-' . request('direction', 'asc') : '' }}">
                                            Customer/Supplier
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'date', 'direction' => request('sort') == 'date' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                            class="table-sort {{ request('sort') == 'date' ? 'table-sort-' . request('direction', 'asc') : '' }}">
                                            Date
                                        </a>
                                    </th>
                                    <th class="text-end">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'amount', 'direction' => request('sort') == 'amount' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                            class="table-sort {{ request('sort') == 'amount' ? 'table-sort-' . request('direction', 'asc') : '' }}">
                                            Amount
                                        </a>
                                    </th>
                                    <th class="text-center">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => request('sort') == 'status' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                            class="table-sort {{ request('sort') == 'status' ? 'table-sort-' . request('direction', 'asc') : '' }}">
                                            Status
                                        </a>
                                    </th>
                                    <th class="w-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transactions as $transaction)
                                    <tr>
                                        <td>
                                            <input class="form-check-input row-checkbox" type="checkbox"
                                                value="{{ $transaction->id ?? '' }}">
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
                                                {{ $transaction->type == 'sale' ? 'Sales' : 'Purchase' }}
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
                                                {{ \Carbon\Carbon::parse($transaction->date)->format('M d, Y') }}
                                            </div>
                                            <div class="small text-muted">
                                                {{ \Carbon\Carbon::parse($transaction->date)->format('h:i A') }}
                                            </div>
                                        </td>
                                        <td class="text-end fw-medium">
                                            {{ \App\Helpers\CurrencyHelper::format($transaction->amount) }}
                                            @if (isset($transaction->due_amount) && $transaction->due_amount > 0)
                                                <div class="small text-danger">
                                                    Due:
                                                    {{ \App\Helpers\CurrencyHelper::format($transaction->due_amount) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="badge {{ $transaction->status == 'Paid' ? 'bg-success' : ($transaction->status == 'Partial' ? 'bg-warning' : 'bg-danger') }}-lt">
                                                {{ $transaction->status }}
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
                                                        View Details
                                                    </a>
                                                    @if (isset($transaction->edit_url))
                                                        <a class="dropdown-item" href="{{ $transaction->edit_url }}">
                                                            <i class="ti ti-edit me-2"></i>
                                                            Edit
                                                        </a>
                                                    @endif
                                                    @if (isset($transaction->print_url))
                                                        <a class="dropdown-item" href="{{ $transaction->print_url }}"
                                                            target="_blank">
                                                            <i class="ti ti-printer me-2"></i>
                                                            Print
                                                        </a>
                                                    @endif
                                                    @if ($transaction->status != 'Paid')
                                                        <a class="dropdown-item text-success" href="#"
                                                            onclick="showMarkAsPaidModal('{{ $transaction->id ?? '' }}', '{{ $transaction->type }}', '{{ $transaction->invoice }}', '{{ $transaction->customer_supplier }}', '{{ \App\Helpers\CurrencyHelper::format($transaction->amount) }}')">
                                                            <i class="ti ti-check me-2"></i>
                                                            Mark as Paid
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
                                                <p class="empty-title">No transactions found</p>
                                                <p class="empty-subtitle text-muted">
                                                    @if (request()->hasAny(['type', 'status', 'date_range', 'search']))
                                                        Try adjusting your search criteria or filters.
                                                    @else
                                                        No transactions have been recorded yet.
                                                    @endif
                                                </p>
                                                @if (request()->hasAny(['type', 'status', 'date_range', 'search']))
                                                    <div class="empty-action">
                                                        <a href="{{ route('admin.transactions') }}"
                                                            class="btn btn-primary">
                                                            <i class="ti ti-x me-1"></i>
                                                            Clear filters
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
                                Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of
                                {{ $transactions->total() }} entries
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
    @include('admin.layouts.modals.recentmodals') @endsection