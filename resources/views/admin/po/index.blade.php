@extends('admin.layouts.base')

@section('title', 'Purchase Order')

@section('content')
    <div class="page-wrapper">
        <div class="page-header">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Overview</div>
                        <h2 class="page-title">Purchase Order</h2>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="{{ route('admin.po.create') }}" class="btn btn-primary">
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
                                    <div class="col-md-9">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title">Invoice information</div>
                                                <div class="row">
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
                                                            This Month Purchase:
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
                                    <div class="ms-auto text-secondary">
                                        Search :
                                        <div class="ms-2 d-inline-block">
                                            <input type="text" id="searchInput" class="form-control form-control-sm">
                                        </div>
                                        <div class="text-end">
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

                            <!-- Table -->
                            <div id="invoiceTableContainer">
                                <div class="table-responsive">
                                    <table class="table card-table table-vcenter">
                                        <thead style="font-size: large">
                                            <tr>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-no">No</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-invoice">Invoice
                                                </th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-supplier">Supplier
                                                </th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-orderdate">Order
                                                        Date</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-duedate">Due Date
                                                </th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-amount">Amount</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-payment">Payment
                                                        Type</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-status">Status</th>
                                                <th style="width:180px;text-align:center" class="fs-4 py-3">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="invoiceTableBody" class="table-tbody">
                                            @foreach ($pos as $index => $po)
                                                <tr>
                                                    <td class="sort-no">{{ $pos->firstItem() + $index }}</td>
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
                                                    <td class="sort-payment">{{ $po->payment_type }}</td>
                                                    <td class="sort-status">
                                                        @php
                                                            $today = now();
                                                            $dueDate = $po->due_date;
                                                            $paymentDate = $po->payment_date;
                                                            $diffDays = $today->diffInDays($dueDate, false);

                                                            if ($po->status === 'Paid') {
                                                                if ($paymentDate && $today->isSameDay($paymentDate)) {
                                                                    echo '<span class="badge bg-success me-1"></span>Paid Today';
                                                                } else {
                                                                    echo '<span class="badge bg-success me-1"></span>Paid';
                                                                }
                                                            } elseif ($diffDays == 0) {
                                                                echo '<span class="badge bg-danger me-1"></span>Due Today';
                                                            } elseif ($diffDays > 0 && $diffDays <= 3) {
                                                                echo '<span class="badge bg-danger me-1"></span>Due in 3 Days';
                                                            } elseif ($diffDays > 3 && $diffDays <= 7) {
                                                                echo '<span class="badge bg-warning me-1"></span>Due in 1 Week';
                                                            } elseif ($diffDays < 0) {
                                                                echo '<span class="badge bg-secondary me-1"></span>Overdue';
                                                            } else {
                                                                echo '<span class="badge bg-info me-1"></span>Pending';
                                                            }
                                                        @endphp
                                                    </td>
                                                    <td style="text-align:center">
                                                        <div class="dropdown">
                                                            <button class="btn dropdown-toggle align-text-top"
                                                                data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                                Actions
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <!-- View Button -->
                                                                <a href="{{ route('admin.po.edit', $po->id) }}"
                                                                    class="dropdown-item">
                                                                    <i class="ti ti-zoom-scan me-2"></i> View
                                                                </a>

                                                                <!-- Delete Form -->
                                                                <form method="POST"
                                                                    action="{{ route('admin.po.destroy', $po->id) }}"
                                                                    onsubmit="return confirm('Are you sure?')"
                                                                    class="m-0">
                                                                    @csrf
                                                                    @method('delete')
                                                                    <button type="submit"
                                                                        class="dropdown-item text-danger">
                                                                        <i class="ti ti-trash me-2"></i> Delete
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Pagination -->
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
@endsection
