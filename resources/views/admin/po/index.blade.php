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
                                    <h1>Purchase Order Invoices</h1>
                                    <div class="ms-auto text-secondary">
                                        Search :
                                        <div class="ms-2 d-inline-block">
                                        <input type="text" id="searchInput" class="form-control form-control-sm">
                                        </div>

                                    </div>
                                </div>
                                <div class="ms-auto">
                                    Show
                                    <div class="mx-2 d-inline-block">
                                        <select name="entries" id="entriesSelect" onchange="this.form.submit()">
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                        </select> entries
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
                                            <th><button class="table-sort fs-4 py-3" data-sort="sort-invoice">Invoice</th>
                                            <th><button class="table-sort fs-4 py-3" data-sort="sort-supplier">Supplier</th>
                                            <th><button class="table-sort fs-4 py-3" data-sort="sort-orderdate">Order Date</th>
                                            <th><button class="table-sort fs-4 py-3" data-sort="sort-duedate">Due Date</th>
                                            <th><button class="table-sort fs-4 py-3" data-sort="sort-amount">Amount</th>
                                            <th><button class="table-sort fs-4 py-3" data-sort="sort-payment">Payment Type</th>
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
                                                <td class="sort-duedate" data-date="{{ $po->due_date->format('Y-m-d') }}">
                                                    {{ $po->due_date->format('d F Y') }}
                                                </td>
                                                <td class="sort-amount" data-amount="{{ $po->total }}">{{ \App\Helpers\CurrencyHelper::format($po->total) }}<span class="raw-amount" style="display: none;">{{ $po->total }}</td>
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
                                                        } elseif ($diffDays <= 3 && $diffDays > 0) {
                                                            echo '<span class="badge bg-danger me-1"></span>Due in 3 Days';
                                                        } elseif ($diffDays <= 7 && $diffDays > 3) {
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
                                                        <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                            Actions
                                                        </button>
                                                        <div class="dropdown-menu">
                                                            <!-- View Button -->
                                                            <a href="{{ route('admin.po.edit', $po->id) }}" class="dropdown-item">
                                                                <i class="ti ti-zoom-scan me-2"></i> View
                                                            </a>

                                                            <!-- Delete Form -->
                                                            <form method="POST" action="{{ route('admin.po.destroy', $po->id) }}" onsubmit="return confirm('Are you sure?')" class="m-0">
                                                                @csrf
                                                                @method('delete')
                                                                <button type="submit" class="dropdown-item text-danger">
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
                                    Showing {{ $pos->firstItem() }} to {{ $pos->lastItem() }} of {{ $pos->total() }} entries
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
