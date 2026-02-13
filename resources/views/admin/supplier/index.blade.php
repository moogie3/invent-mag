@extends('admin.layouts.base')

@section('title', __('messages.supplier_page_title'))

@section('content')
    <div class="page-wrapper">
        <div class="page-header no-print">
            <div class="{{ $containerClass ?? "container-xl" }}">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">{{ __('messages.warehouse_overview') }}</div>
                        <h2 class="page-title"><i class="ti ti-truck me-2"></i>{{ __('messages.supplier_title') }}</h2>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="btn-group d-none d-sm-inline-block me-2">
                            <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <i class="ti ti-printer fs-4 me-2"></i> {{ __('messages.export') }}
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="exportSuppliers('csv')">Export as
                                        CSV</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportSuppliers('pdf')">Export as
                                        PDF</a></li>
                            </ul>
                        </div>
                        <button type="button" class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal"
                            data-bs-target="#createSupplierModal">
                            <i class="ti ti-plus fs-4"></i> {{ __('messages.supplier_create_supplier') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="{{ $containerClass ?? "container-xl" }}">
                <div class="row row-deck row-cards">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-body border-bottom py-3">
                                <div class="d-flex justify-content-between">
                                    <div class="col-md-8">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <div class="card border-0 bg-light">
                                                    <div class="card-body py-3">
                                                        <div class="mb-2">
                                                            <label class="form-label text-muted mb-2 d-block">
                                                                {{ __('messages.supplier_info_transactions') }}
                                                            </label>
                                                        </div>
                                                        <div class="d-flex align-items-center mb-3">
                                                            <div class="me-3 d-flex align-items-center justify-content-center badge"
                                                                style="width: 40px; height: 40px;">
                                                                <i class="ti ti-step-out fs-3 text-danger"></i>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <div class="small text-muted">
                                                                    {{ __('messages.supplier_info_out') }}</div>
                                                                <div class="fw-bold">{{ $outCount }}</div>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <div class="me-3 d-flex align-items-center justify-content-center badge"
                                                                style="width: 40px; height: 40px;">
                                                                <i class="ti ti-step-into fs-3 text-success"></i>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <div class="small text-muted">
                                                                    {{ __('messages.supplier_info_in') }}</div>
                                                                <div class="fw-bold">{{ $inCount }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card border-0 bg-blue-lt">
                                                    <div class="card-body py-3">
                                                        <div class="mb-2">
                                                            <label class="form-label text-muted mb-2 d-block">
                                                                {{ __('messages.supplier_info_title') }}
                                                            </label>
                                                        </div>
                                                        <div class="d-flex align-items-center mb-">
                                                            <div class="me-3 d-flex align-items-center justify-content-center badge"
                                                                style="width: 40px; height: 40px;">
                                                                <i class="ti ti-truck fs-3 text-primary"></i>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <div class="small text-muted">
                                                                    {{ __('messages.supplier_info_total') }}</div>
                                                                <div class="fw-bold" id="totalSupplierCount">
                                                                    {{ $totalsupplier }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ms-auto text-secondary no-print">
                                        <div class="ms-2 mb-2 text-end">
                                            {{ __('messages.search_label') }}:
                                            <div class="ms-2">
                                                <input type="text" id="searchInput" class="form-control form-control-sm">
                                            </div>
                                        </div>
                                        <div class="mb-2 text-end">
                                            {{ __('messages.warehouse_search_show') }}
                                            <div class="mx-1 mt-2 d-inline-block">
                                                <select name="entries" id="entriesSelect"
                                                    onchange="window.location.href='?entries=' + this.value;">
                                                    <option value="10" {{ $entries == 10 ? 'selected' : '' }}>10
                                                    </option>
                                                    <option value="25" {{ $entries == 25 ? 'selected' : '' }}>25
                                                    </option>
                                                    <option value="50" {{ $entries == 50 ? 'selected' : '' }}>50
                                                    </option>
                                                </select> {{ __('messages.warehouse_search_entries') }}
                                            </div>
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
                                                <th class="no-print"><button class="table-sort fs-4 py-3 no-print"
                                                        data-sort="sort-no">{{ __('messages.table_no') }}</th>
                                                <th><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-image">{{ __('messages.table_image') }}</th>
                                                <th><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-code">{{ __('messages.table_code') }}</th>
                                                <th><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-name">{{ __('messages.table_name') }}</th>
                                                <th><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-address">{{ __('messages.table_address') }}
                                                </th>
                                                <th><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-location">{{ __('messages.table_location') }}
                                                </th>
                                                <th><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-paymentterms">{{ __('messages.table_payment_terms') }}
                                                </th>
                                                <th><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-email">{{ __('messages.table_email') }}</th>
                                                <th style="width:180px;text-align:center" class="fs-4 py-3 no-print">
                                                    {{ __('messages.table_action') }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="invoiceTableBody" class="table-tbody">
                                            @forelse ($suppliers as $index => $supplier)
                                                <tr>
                                                    <td class="sort-no no-print">{{ $suppliers->firstItem() + $index }}
                                                    </td>
                                                    <td class="sort-image">
                                                        @if ($supplier->image == asset('img/default_placeholder.png'))
                                                            <i class="ti ti-photo fs-1"
                                                                style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; border-radius: 5px; margin: 0 auto;"></i>
                                                        @else
                                                            <img src="{{ $supplier->image }}" alt="Supplier Image"
                                                                class="avatar avatar-sm"
                                                                style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; border-radius: 5px; margin: 0 auto;">
                                                        @endif
                                                    </td>
                                                    <td class="sort-code">{{ $supplier->code }}</td>
                                                    <td class="sort-name">{{ $supplier->name }}</td>
                                                    <td class="sort-address">{{ $supplier->address }}</td>
                                                    <td class="sort-location">{{ $supplier->location }}</td>
                                                    <td class="sort-paymentterms">{{ $supplier->payment_terms }}</td>
                                                    <td class="sort-email">{{ $supplier->email }}</td>
                                                    <td class="no-print" style="text-align:center">
                                                        <div class="btn-list flex-nowrap justify-content-center">
                                                            <button type="button" class="btn btn-icon btn-ghost-secondary srm-supplier-btn"
                                                                data-id="{{ $supplier->id }}" data-bs-toggle="modal"
                                                                data-bs-target="#srmSupplierModal"
                                                                title="{{ __('messages.supplier_action_view_srm') }}">
                                                                <i class="ti ti-user-search"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-icon btn-ghost-primary"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#editSupplierModal"
                                                                data-id="{{ $supplier->id }}"
                                                                data-code="{{ $supplier->code }}"
                                                                data-name="{{ $supplier->name }}"
                                                                data-address="{{ $supplier->address }}"
                                                                data-phone_number="{{ $supplier->phone_number }}"
                                                                data-location="{{ $supplier->location }}"
                                                                data-payment_terms="{{ $supplier->payment_terms }}"
                                                                data-image="{{ $supplier->image }}"
                                                                data-email="{{ $supplier->email }}"
                                                                title="{{ __('messages.edit') }}">
                                                                <i class="ti ti-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-icon btn-ghost-danger"
                                                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                                onclick="setDeleteFormAction('{{ route('admin.supplier.destroy', $supplier->id) }}')"
                                                                title="{{ __('messages.delete') }}">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="9">
                                                        <div class="empty">
                                                            <div class="empty-img">
                                                                <i class="ti ti-mood-sad"
                                                                    style="font-size: 5rem; color: #ccc;"></i>
                                                            </div>
                                                            <p class="empty-title">{{ __('messages.no_suppliers_found') }}
                                                            </p>
                                                            <p class="empty-subtitle text-muted">
                                                                {{ __('messages.it_looks_like_you_havent_added_any_suppliers_yet') }}
                                                            </p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            {{-- PAGINATION --}}
                            <div class="card-footer d-flex align-items-center">
                                <p class="m-0 text-secondary">
                                    {{ __('messages.pagination_showing_entries', [
                                        'first' => $suppliers->firstItem(),
                                        'last' => $suppliers->lastItem(),
                                        'total' => $suppliers->total(),
                                    ]) }}
                                </p>
                                <div class="ms-auto">
                                    {{ $suppliers->appends(request()->query())->links('vendor.pagination.tabler') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.layouts.modals.supplier.suppmodals')
    @include('admin.layouts.modals.supplier.srm-modal')
@endsection
