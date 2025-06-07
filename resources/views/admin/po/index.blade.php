@extends('admin.layouts.base')

@section('title', 'Purchase Order')

@section('content')
    <div class="page-wrapper">
        <div class="page-header no-print">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Overview</div>
                        <h2 class="page-title"><i class="ti ti-shopping-cart me-2"></i> Purchase Order</h2>
                    </div>
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-secondary d-none d-sm-inline-block"
                            onclick="javascript:window.print();">
                            <i class="ti ti-printer fs-4"></i> Export PDF
                        </button>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="{{ route('admin.po.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                            <i class="ti ti-plus fs-4"></i> Create Purchase Order
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-deck row-cards">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-body border-bottom py-3">
                                <div class="d-flex justify-content-between">
                                    <div class="col-md-8">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title">Store information</div>
                                                <div class="purchase-info row">
                                                    <div class="col-md-4">
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-building-store fs-2"></i>
                                                            </span>
                                                            User Store : <strong>{{ $shopname }}</strong>
                                                        </div>
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-map fs-2"></i>
                                                            </span>
                                                            Store Address : <strong>{{ $address }}</strong>
                                                        </div>
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-file-invoice fs-2"></i>
                                                            </span>
                                                            Total Invoice : <strong>{{ $totalinvoice }}</strong>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-step-out fs-2"></i>
                                                            </span>
                                                            Invoice OUT: <strong>{{ $outCount }}</strong>
                                                        </div>
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-basket-dollar fs-2"></i>
                                                            </span>
                                                            Amount OUT:
                                                            <strong>{{ \App\Helpers\CurrencyHelper::format($outCountamount) }}</strong>
                                                        </div>
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-currency fs-2"></i>
                                                            </span>
                                                            This Month PO:
                                                            <strong>{{ \App\Helpers\CurrencyHelper::format($totalMonthly) }}</strong>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-step-into fs-2"></i>
                                                            </span>
                                                            Invoice IN: <strong>{{ $inCount }}</strong>
                                                        </div>
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-basket-dollar fs-2"></i>
                                                            </span>
                                                            Amount IN:
                                                            <strong>{{ \App\Helpers\CurrencyHelper::format($inCountamount) }}</strong>
                                                        </div>
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-credit-card-pay fs-2"></i>
                                                            </span>
                                                            This Month Paid:
                                                            <strong>{{ \App\Helpers\CurrencyHelper::format($paymentMonthly) }}</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ms-auto text-secondary no-print">
                                        <div class="ms-2 mb-2 text-end">
                                            Search :
                                            <div class="ms-2">
                                                <input type="text" id="searchInput" class="form-control form-control-sm">
                                            </div>
                                        </div>
                                        <div class="mb-2 text-end">
                                            Filter by:
                                            <form method="GET" action="{{ route('admin.po') }}" class="d-inline-block">
                                                <select name="month"
                                                    class="form-select form-select-sm d-inline-block w-auto">
                                                    <option value="">Select Month</option>
                                                    @foreach (range(1, 12) as $m)
                                                        <option value="{{ $m }}"
                                                            {{ request('month') == $m ? 'selected' : '' }}>
                                                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <select name="year"
                                                    class="form-select form-select-sm d-inline-block w-auto">
                                                    <option value="">Select Year</option>
                                                    @foreach (range(date('Y') - 5, date('Y')) as $y)
                                                        <option value="{{ $y }}"
                                                            {{ request('year') == $y ? 'selected' : '' }}>
                                                            {{ $y }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                                            </form>
                                        </div>
                                        <div class="mb-2 text-end">
                                            Show
                                            <div class="mx-1 mt-2 d-inline-block">
                                                <select name="entries" id="entriesSelect"
                                                    onchange="window.location.href='?entries=' + this.value;">
                                                    <option value="10" {{ $entries == 10 ? 'selected' : '' }}>10
                                                    </option>
                                                    <option value="25" {{ $entries == 25 ? 'selected' : '' }}>25
                                                    </option>
                                                    <option value="50" {{ $entries == 50 ? 'selected' : '' }}>50
                                                    </option>
                                                </select> entries
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="bulkActionsBar" class="card mt-3" style="display: none;">
                                <div class="card-body py-2">
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted me-3">
                                            <span id="selectedCount">0</span> transactions selected
                                        </span>
                                        <div class="btn-list">
                                            <button onclick="bulkMarkAsPaidPO()" class="btn btn-sm btn-success me-1">
                                                <i class="ti ti-check"></i> Mark as Paid
                                            </button>
                                            <button onclick="bulkDeletePO()" class="btn btn-sm btn-danger me-1">
                                                <i class="ti ti-trash"></i> Delete Selected
                                            </button>
                                            <button onclick="bulkExportPO()" class="btn btn-sm btn-primary me-1">
                                                <i class="ti ti-download"></i> Export Selected
                                            </button>
                                            <button onclick="clearPOSelection()"
                                                class="btn btn-sm btn-outline-secondary me-1">
                                                <i class="ti ti-x"></i> Clear Selection
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- TABLE --}}
                            <div id="invoiceTableContainer">
                                <div class="table-responsive">
                                    <table class="table card-table table-vcenter">
                                        <thead style="font-size: large">
                                            <tr>
                                                <th>
                                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                                </th>
                                                <th class="no-print"><button class="table-sort fs-4 py-3 no-print"
                                                        data-sort="sort-no">No
                                                </th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-invoice">Invoice
                                                </th>
                                                <th><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-supplier">Supplier
                                                </th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-orderdate">Order
                                                        Date</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-duedate">Due Date
                                                </th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-amount">Amount
                                                </th>
                                                <th class="no-print"><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-payment">Payment
                                                        Type</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-status">Status
                                                </th>
                                                <th style="width:180px;text-align:center" class="fs-4 py-3 no-print">
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="invoiceTableBody" class="table-tbody">
                                            @foreach ($pos as $index => $po)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="form-check-input row-checkbox"
                                                            value="{{ $po->id }}">
                                                    </td>
                                                    <td class="sort-no no-print">{{ $pos->firstItem() + $index }}</td>
                                                    <td class="sort-invoice">{{ $po->invoice }}</td>
                                                    <td class="sort-supplier">{{ $po->supplier->name }}</td>
                                                    <td class="sort-orderdate">{{ $po->order_date->format('d F Y') }}</td>
                                                    <td class="sort-duedate"
                                                        data-date="{{ $po->due_date->format('Y-m-d') }}">
                                                        {{ $po->due_date->format('d F Y') }}
                                                    </td>
                                                    <td class="sort-amount" data-amount="{{ $po->total }}">
                                                        {{ \App\Helpers\CurrencyHelper::format($po->total) }}<span
                                                            class="raw-amount" style="display: none;">{{ $po->total }}
                                                    </td>
                                                    <td class="sort-payment no-print">{{ $po->payment_type }}</td>
                                                    <td class="sort-status">
                                                        <span
                                                            class="{{ \App\Helpers\PurchaseHelper::getStatusClass($po->status, $po->due_date) }}">
                                                            {!! \App\Helpers\PurchaseHelper::getStatusText($po->status, $po->due_date) !!}
                                                        </span>
                                                    </td>
                                                    <td class="no-print" style="text-align:center">
                                                        <div class="dropdown">
                                                            <button class="btn dropdown-toggle align-text-top"
                                                                data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                                Actions
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <a href="javascript:void(0)"
                                                                    onclick="loadPoDetails('{{ $po->id }}')"
                                                                    data-bs-toggle="modal" data-bs-target="#viewPoModal"
                                                                    class="dropdown-item">
                                                                    <i class="ti ti-zoom-scan me-2"></i> View
                                                                </a>

                                                                <a href="{{ route('admin.po.edit', $po->id) }}"
                                                                    class="dropdown-item">
                                                                    <i class="ti ti-edit me-2"></i> Edit
                                                                </a>

                                                                <button type="button" class="dropdown-item text-danger"
                                                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                                    onclick="setDeleteFormAction('{{ route('admin.po.destroy', $po->id) }}')">
                                                                    <i class="ti ti-trash me-2"></i> Delete
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- PAGINATION --}}
                            <div class="card-footer d-flex align-items-center">
                                <p class="m-0 text-secondary">
                                    Showing {{ $pos->firstItem() }} to {{ $pos->lastItem() }} of {{ $pos->total() }}
                                    entries
                                </p>
                                <div class="ms-auto">
                                    {{ $pos->appends(request()->query())->links('vendor.pagination.tabler') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal modal-blur fade" id="bulkMarkAsPaidModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Mark as Paid</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to mark <strong id="bulkPaidCount">0</strong> purchase orders as paid?</p>
                    <div class="text-muted small">This action cannot be undone.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmBulkPaidBtn">
                        <i class="ti ti-check"></i> Mark as Paid
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="bulkDeleteModalLabel">
                        <i class="ti ti-trash me-2"></i>
                        Confirm Bulk Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="alert alert-warning d-flex align-items-center w-100 mb-0">
                            <i class="ti ti-alert-circle me-2 fs-4"></i>
                            <div>
                                <strong>Warning!</strong> This action cannot be undone.
                            </div>
                        </div>
                    </div>

                    <p class="mb-3">
                        You are about to permanently delete
                        <strong id="bulkDeleteCount">0</strong>
                        purchase order(s) and all associated data.
                    </p>

                    <div class="bg-light p-3 rounded">
                        <h6 class="mb-2"><i class="ti ti-info-circle me-1"></i> What will be deleted:</h6>
                        <ul class="list-unstyled mb-0 small">
                            <li><i class="ti ti-check text-danger me-1"></i> Purchase order records</li>
                            <li><i class="ti ti-check text-danger me-1"></i> Associated purchase order items</li>
                            <li><i class="ti ti-check text-danger me-1"></i> Related transaction history</li>
                        </ul>
                    </div>

                    <p class="mt-3 mb-1 text-muted small">
                        <i class="ti ti-info-circle me-1"></i>
                        Product stock levels will be adjusted accordingly.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i>
                        Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmBulkDeleteBtn">
                        <i class="ti ti-trash me-1"></i>
                        Delete Selected
                    </button>
                </div>
            </div>
        </div>
    </div>
    @include('admin.layouts.modals.pomodals')
@endsection
