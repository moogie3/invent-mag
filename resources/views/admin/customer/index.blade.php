@extends('admin.layouts.base')

@section('title', __('messages.customer_page_title'))

@section('content')
    <div class="page-wrapper">
        <div class="page-header no-print">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            {{ __('messages.warehouse_overview') }}
                        </div>
                        <h2 class="page-title">
                            <i class="ti ti-users me-2"></i> {{ __('messages.customer_title') }}
                        </h2>
                    </div>
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-secondary d-none d-sm-inline-block"
                            onclick="javascript:window.print();">
                            <i class="ti ti-printer fs-4"></i> {{ __('messages.warehouse_export_pdf') }}
                        </button>
                        <button type="button" class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal"
                            data-bs-target="#createCustomerModal">
                            <i class="ti ti-plus fs-4"></i> {{ __('messages.customer_create_customer') }}
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
                                    <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body py-3">
                                            <div class="mb-2">
                                                <label class="form-label text-muted mb-2 d-block">
                                                    {{ __('messages.customer_info_title') }}
                                                </label>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="me-3 d-flex align-items-center justify-content-center"
                                                    style="width: 32px; height: 32px;">
                                                    <i class="ti ti-users fs-3 text-primary"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="small text-muted">{{ __('messages.customer_info_total') }}</div>
                                                    <div class="fw-bold" id="totalCustomerCount">{{ $totalcustomer }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    <div class="ms-auto text-secondary no-print">
                                        {{ __('messages.search_label') }}
                                        <div class="ms-2 d-inline-block">
                                            <input type="text" id="searchInput" class="form-control form-control-sm">
                                        </div>
                                        <div class="text-end">
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
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-no">{{ __('messages.table_no') }}</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-image">{{ __('messages.table_image') }}</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-name">{{ __('messages.table_name') }}</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-address">{{ __('messages.table_address') }}
                                                </th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-phonenumber">{{ __('messages.supplier_modal_phone_number') }}
                                                        </th>
                                                <th><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-phonenumber">{{ __('messages.table_payment_terms') }}</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-email">{{ __('messages.table_email') }}</th>
                                                <th style="width:180px;text-align:center" class="fs-4 py-3 no-print">{{ __('messages.table_action') }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="invoiceTableBody" class="table-tbody">
    @forelse ($customers as $index => $customer)
        <tr>
            <td class="sort-no">{{ $customers->firstItem() + $index }}</td>
            <td class="sort-image">
                @if ($customer->image == asset('img/default_placeholder.png'))
                    <i class="ti ti-photo fs-1"
                        style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; border-radius: 5px; margin: 0 auto;"></i>
                @else
                    <img src="{{ $customer->image }}" alt="" height="80px"
                        width="80px"
                        style="display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; border-radius: 5px; margin: 0 auto;">
                @endif
            </td>
            <td class="sort-name">{{ $customer->name }}</td>
            <td class="sort-address">{{ $customer->address }}</td>
            <td class="sort-phonenumber">{{ $customer->phone_number }}</td>
            <td class="sort-paymentterms">{{ $customer->payment_terms }}</td>
            <td class="sort-email">{{ $customer->email }}</td>
            <td class="no-print" style="text-align:center">
                <div class="dropdown">
                    <button class="btn dropdown-toggle align-text-top"
                        data-bs-toggle="dropdown" data-bs-boundary="viewport">
                        {{ __('messages.table_action') }}
                    </button>
                    <div class="dropdown-menu">
                        <a href="#" class="dropdown-item crm-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#crmCustomerModal"
                            data-id="{{ $customer->id }}">
                            <i class="ti ti-eye me-2"></i> {{ __('messages.customer_action_view_crm') }}
                        </a>
                        <a href="#" class="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#editCustomerModal"
                            data-id="{{ $customer->id }}"
                            data-name="{{ $customer->name }}"
                            data-address="{{ $customer->address }}"
                            data-phone_number="{{ $customer->phone_number }}"
                            data-payment_terms="{{ $customer->payment_terms }}"
                            data-email="{{ $customer->email }}"
                            data-image="{{ $customer->image }}">
                            <i class="ti ti-edit me-2"></i> {{ __('messages.edit') }}
                        </a>

                        <button type="button" class="dropdown-item text-danger"
                            data-bs-toggle="modal" data-bs-target="#deleteModal"
                            onclick="setDeleteFormAction('{{ route('admin.customer.destroy', $customer->id) }}')">
                            <i class="ti ti-trash me-2"></i> {{ __('messages.delete') }}
                        </button>
                    </div>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="8">
                <div class="empty">
                    <div class="empty-img">
                        <i class="ti ti-mood-sad" style="font-size: 5rem; color: #ccc;"></i>
                    </div>
                    <p class="empty-title">{{ __('messages.no_customers_found') }}</p>
                    <p class="empty-subtitle text-muted">
                        {{ __('messages.it_looks_like_you_havent_added_any_customers_yet') }}
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
                                        'first' => $customers->firstItem(),
                                        'last' => $customers->lastItem(),
                                        'total' => $customers->total(),
                                    ]) }}
                                </p>
                                <div class="ms-auto">
                                    {{ $customers->appends(request()->query())->links('vendor.pagination.tabler') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('admin.layouts.modals.custmodals')
    @include('admin.layouts.modals.crm-modal')
@endsection
