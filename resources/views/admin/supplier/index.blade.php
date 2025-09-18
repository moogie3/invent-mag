@extends('admin.layouts.base')

@section('title', __('messages.supplier_page_title'))

@section('content')
    <div class="page-wrapper">
        <div class="page-header no-print">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">{{ __('messages.warehouse_overview') }}</div>
                        <h2 class="page-title"><i class="ti ti-truck me-2"></i>{{ __('messages.supplier_title') }}</h2>
                    </div>
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-secondary d-none d-sm-inline-block"
                            onclick="javascript:window.print();">
                            <i class="ti ti-printer fs-4"></i> {{ __('messages.warehouse_export_pdf') }}
                        </button>
                        <button type="button" class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal"
                            data-bs-target="#createSupplierModal">
                            <i class="ti ti-plus fs-4"></i> {{ __('messages.supplier_create_supplier') }}
                        </button>
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
                                                <div class="card-title">{{ __('messages.supplier_info_title') }}</div>
                                                <div class="purchase-info row">
                                                    <div class="col-md-3">
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-step-out fs-2"></i>
                                                            </span>
                                                            {{ __('messages.supplier_info_out') }} <strong>{{ $outCount }}</strong>
                                                        </div>
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-step-into fs-2"></i>
                                                            </span>
                                                            {{ __('messages.supplier_info_in') }} <strong>{{ $inCount }}</strong>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-building fs-2"></i>
                                                            </span>
                                                            {{ __('messages.supplier_info_total') }} <strong>{{ $totalsupplier }}</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ms-auto text-secondary no-print">
                                        <div class="ms-2 mb-2 text-end">
                                            {{ __('messages.warehouse_search_label') }}:
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
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-image">{{ __('messages.table_image') }}</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-code">{{ __('messages.table_code') }}</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-name">{{ __('messages.table_name') }}</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-address">{{ __('messages.table_address') }}
                                                </th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-location">{{ __('messages.table_location') }}
                                                </th>
                                                <th><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-paymentterms">{{ __('messages.table_payment_terms') }}</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-email">{{ __('messages.table_email') }}</th>
                                                <th style="width:180px;text-align:center" class="fs-4 py-3 no-print">{{ __('messages.table_action') }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="invoiceTableBody" class="table-tbody">
                                            @foreach ($suppliers as $index => $supplier)
                                                <tr>
                                                    <td class="sort-no no-print">{{ $suppliers->firstItem() + $index }}</td>
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
                                                        <div class="dropdown">
                                                            <button class="btn dropdown-toggle align-text-top"
                                                                data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                                {{ __('messages.table_action') }}
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <a href="#" class="dropdown-item srm-supplier-btn"
                                                                    data-id="{{ $supplier->id }}" data-bs-toggle="modal"
                                                                    data-bs-target="#srmSupplierModal">
                                                                    <i class="ti ti-user-search me-2"></i> {{ __('messages.supplier_action_view_srm') }}
                                                                </a>
                                                                <a href="#" class="dropdown-item"
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
                                                                    data-email="{{ $supplier->email }}">
                                                                    <i class="ti ti-edit me-2"></i> {{ __('messages.edit') }}
                                                                </a>
                                                                <button type="button" class="dropdown-item text-danger"
                                                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                                    onclick="setDeleteFormAction('{{ route('admin.supplier.destroy', $supplier->id) }}')">
                                                                    <i class="ti ti-trash me-2"></i> {{ __('messages.delete') }}
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

    @include('admin.layouts.modals.suppmodals')
    @include('admin.layouts.modals.srm-modal')
@endsection
