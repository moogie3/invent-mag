@extends('admin.layouts.base')

@section('title', __('messages.tax_page_title'))

@section('header')
<meta name="turbo-cache-control" content="no-cache">
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="{{ $containerClass ?? "container-xl" }}">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body d-flex justify-content-between align-items-center py-3">
                        <h2 class="mb-0"><i class="ti ti-receipt-tax me-2"></i>{{ __('messages.tax_settings_title') }}</h2>
                        <a href="#" class="text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                            <i class="ti ti-help-circle fs-3"></i>
                        </a>
                    </div>
                    <hr class="my-0">
                        <div class="row g-0">
                            <div class="col-12 col-md-3 border-end">
                                @include('admin.layouts.menu')
                            </div>
                            <div class="col-12 col-md-9 d-flex flex-column">
                                <div class="row row-deck row-cards">
                                <div class="col-md-12">
                                    <div class="card border-0 shadow-none rounded-3">
                                        <div class="card-body border-bottom py-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <i class="ti ti-receipt-tax fs-1 me-3 text-primary"></i>
                                                        <div>
                                                            <h2 class="mb-1">
                                                                {{ __('messages.tax_settings_title') }}
                                                            </h2>
                                                            <div class="text-muted">
                                                                {{ __('messages.tax_total_taxes') }} <strong id="totalTaxCount">{{ $totalTaxes }}</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="btn-list">
                                                        <button type="button"
                                                            class="btn btn-primary d-none d-sm-inline-block"
                                                            data-bs-toggle="modal" data-bs-target="#createTaxModal">
                                                            <i class="ti ti-plus fs-4"></i> {{ __('messages.tax_create') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div id="invoiceTableContainer">
                                                <div class="table-responsive">
                                                    <table class="table card-table table-vcenter">
                                                        <thead style="font-size: large">
                                                            <tr>
                                                                <th style="width: 100px;"><button
                                                                        class="table-sort fs-4 py-3" data-sort="sort-no">{{ __('messages.table_no') }}
                                                                </th>
                                                                <th style="width: 200px;"><button
                                                                        class="table-sort fs-4 py-3"
                                                                        data-sort="sort-name">{{ __('messages.table_name') }}</th>
                                                                <th style="width: 120px;"><button
                                                                        class="table-sort fs-4 py-3"
                                                                        data-sort="sort-rate">{{ __('messages.tax_rate') }}</th>
                                                                <th style="width: 120px;"><button
                                                                        class="table-sort fs-4 py-3"
                                                                        data-sort="sort-type">{{ __('messages.tax_type') }}</th>
                                                                <th style="width: 100px;">{{ __('messages.tax_status') }}</th>
                                                                <th style="width: 100px;">{{ __('messages.tax_default') }}</th>
                                                                <th style="width:180px;text-align:center" class="fs-4 py-3">
                                                                    {{ __('messages.table_action') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="invoiceTableBody" class="table-tbody">
                                                            @forelse ($taxes as $index => $tax)
                                                                <tr>
                                                                    <td class="sort-no">
                                                                        {{ $taxes->firstItem() + $index }}</td>
                                                                    <td class="sort-name">{{ $tax->name }}</td>
                                                                    <td class="sort-rate">{{ $tax->rate }}%</td>
                                                                    <td class="sort-type">
                                                                        @if ($tax->type === 'exclusive')
                                                                            <span class="badge bg-info text-white rounded-pill">{{ __('messages.tax_type_exclusive') }}</span>
                                                                        @elseif ($tax->type === 'inclusive')
                                                                            <span class="badge bg-warning text-white rounded-pill">{{ __('messages.tax_type_inclusive') }}</span>
                                                                        @else
                                                                            <span class="badge bg-secondary text-white rounded-pill">{{ __('messages.tax_type_none') }}</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @if ($tax->is_active)
                                                                            <span class="badge bg-success text-white rounded-pill">{{ __('messages.active') }}</span>
                                                                        @else
                                                                            <span class="badge bg-danger text-white rounded-pill">{{ __('messages.inactive') }}</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @if ($tax->is_default)
                                                                            <span class="badge bg-primary text-white rounded-pill">{{ __('messages.default') }}</span>
                                                                        @endif
                                                                    </td>
                                                                    <td style="text-align:center">
                                                                        <div class="btn-list flex-nowrap justify-content-center">
                                                                            <button type="button" class="btn btn-icon btn-ghost-primary"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#editTaxModal"
                                                                                data-id="{{ $tax->id }}"
                                                                                data-name="{{ $tax->name }}"
                                                                                data-rate="{{ $tax->rate }}"
                                                                                data-type="{{ $tax->type }}"
                                                                                data-description="{{ $tax->description }}"
                                                                                data-is-active="{{ $tax->is_active ? '1' : '0' }}"
                                                                                data-is-default="{{ $tax->is_default ? '1' : '0' }}"
                                                                                title="{{ __('messages.edit') }}">
                                                                                <i class="ti ti-edit"></i>
                                                                            </button>

                                                                            @if (!$tax->is_default)
                                                                             <button type="button" class="btn btn-icon btn-ghost-success"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#defaultTaxModal"
                                                                                data-onclick="setSetDefaultTaxFormAction('{{ route('admin.setting.tax.default', $tax->id) }}', '{{ $tax->name }} ({{ $tax->rate }}%)')"
                                                                                title="{{ __('messages.tax_set_default') }}">
                                                                                <i class="ti ti-star"></i>
                                                                            </button>
                                                                            @endif

                                                                            <button type="button" class="btn btn-icon btn-ghost-danger"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#deleteTaxModal"
                                                                                data-onclick="setDeleteTaxFormAction('{{ route('admin.setting.tax.destroy', $tax->id) }}', '{{ $tax->name }}')"
                                                                                title="{{ __('messages.delete') }}">
                                                                                <i class="ti ti-trash"></i>
                                                                            </button>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @empty
                                                                <tr>
                                                                    <td colspan="7">
                                                                        <div class="empty">
                                                                            <div class="empty-img">
                                                                                <i class="ti ti-mood-sad" style="font-size: 5rem; color: #ccc;"></i>
                                                                            </div>
                                                                            <p class="empty-title">{{ __('messages.no_taxes_found') }}</p>
                                                                            <p class="empty-subtitle text-muted">
                                                                                {{ __('messages.tax_no_taxes_description') }}
                                                                            </p>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="card-footer d-flex align-items-center">
                                                <p class="m-0 text-secondary">
                                                    {{ __('messages.pagination_showing_entries', [
                                                        'first' => $taxes->firstItem(),
                                                        'last' => $taxes->lastItem(),
                                                        'total' => $taxes->total(),
                                                    ]) }}
                                                </p>
                                                <div class="ms-auto">
                                                    {{ $taxes->appends(request()->query())->links('vendor.pagination.tabler') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('admin.layouts.modals.tax.taxmodals')
@endsection
