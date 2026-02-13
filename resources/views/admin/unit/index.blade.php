@extends('admin.layouts.base')

@section('title', __('messages.unit_page_title'))

@section('content')
    <div class="page-wrapper">
        <div class="page-wrapper">
            <div class="page-body">
                <div class="{{ $containerClass ?? "container-xl" }}">
                    <div class="card">
                        <div class="card-body">
                            <h2><i class="ti ti-ruler me-2"></i>{{ __('messages.unit_settings_title') }}</h2>
                        </div>
                        <hr class="my-0">
                        <div class="row g-0">
                            <div class="col-12 col-md-3 border-end">
                                @include('admin.layouts.menu')
                            </div>
                            <div class="col-12 col-md-9 d-flex flex-column">
                                <div class="row row-deck row-cards">
                                    <div class="col-md-12">
                                        <div class="card card-primary">
                                            <div class="card-body border-bottom py-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <i class="ti ti-ruler fs-1 me-3 text-primary"></i>
                                                        <div>
                                                            <h2 class="mb-1">
                                                                {{ __('messages.unit_settings_title') }}
                                                            </h2>
                                                            <div class="text-muted">
                                                                {{ __('messages.unit_total_unit') }} <strong id="totalUnitCount">{{ $totalunit }}</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="btn-list">
                                                        <button type="button"
                                                            class="btn btn-primary d-none d-sm-inline-block"
                                                            data-bs-toggle="modal" data-bs-target="#createUnitModal">
                                                            <i class="ti ti-plus fs-4"></i> {{ __('messages.unit_create_unit') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- TABLE --}}
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
                                                                        data-sort="sort-code">{{ __('messages.table_code') }}</th>
                                                                <th><button class="table-sort fs-4 py-3"
                                                                        data-sort="sort-name">{{ __('messages.table_name') }}</th>
                                                                <th style="width:180px;text-align:center" class="fs-4 py-3">
                                                                    {{ __('messages.table_action') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="invoiceTableBody" class="table-tbody">
                                                            @forelse ($units as $index => $unit)
                                                                <tr>
                                                                    <td class="sort-no">
                                                                        {{ $units->firstItem() + $index }}</td>
                                                                    <td class="sort-code">{{ $unit->symbol }}</td>
                                                                    <td class="sort-name">{{ $unit->name }}</td>
                                                                    <td style="text-align:center">
                                                                        <div class="dropdown">
                                                                            <button
                                                                                class="btn dropdown-toggle align-text-top"
                                                                                data-bs-toggle="dropdown"
                                                                                data-bs-boundary="viewport">
                                                                                {{ __('messages.table_action') }}
                                                                            </button>
                                                                            <div class="dropdown-menu">
                                                                                <a href="#" class="dropdown-item"
                                                                                    data-bs-toggle="modal"
                                                                                    data-bs-target="#editUnitModal"
                                                                                    data-id="{{ $unit->id }}"
                                                                                    data-symbol="{{ $unit->symbol }}"
                                                                                    data-name="{{ $unit->name }}">
                                                                                    <i class="ti ti-edit me-2"></i> {{ __('messages.edit') }}
                                                                                </a>


                                                                                <button type="button"
                                                                                    class="dropdown-item text-danger"
                                                                                    data-bs-toggle="modal"
                                                                                    data-bs-target="#deleteModal"
                                                                                    onclick="setDeleteFormAction('{{ route('admin.setting.unit.destroy', $unit->id) }}')">
                                                                                    <i class="ti ti-trash me-2"></i>
                                                                                    {{ __('messages.delete') }}
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @empty
                                                                <tr>
                                                                    <td colspan="4">
                                                                        <div class="empty">
                                                                            <div class="empty-img">
                                                                                <i class="ti ti-mood-sad" style="font-size: 5rem; color: #ccc;"></i>
                                                                            </div>
                                                                            <p class="empty-title">{{ __('messages.no_units_found') }}</p>
                                                                            <p class="empty-subtitle text-muted">
                                                                                {{ __('messages.it_looks_like_you_havent_added_any_units_yet') }}
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
                                                        'first' => $units->firstItem(),
                                                        'last' => $units->lastItem(),
                                                        'total' => $units->total(),
                                                    ]) }}
                                                </p>
                                                <div class="ms-auto">
                                                    {{ $units->appends(request()->query())->links('vendor.pagination.tabler') }}
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
    @include('admin.layouts.modals.unit.unitmodals')
@endsection
