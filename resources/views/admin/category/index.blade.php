@extends('admin.layouts.base')

@section('title', __('messages.category_page_title'))

@section('header')
<meta name="turbo-cache-control" content="no-cache">
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="{{ $containerClass ?? "container-xl" }}">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body d-flex justify-content-between align-items-center py-3">
                        <h2 class="mb-0"><i class="ti ti-tag me-2"></i>{{ __('messages.category_settings_title') }}</h2>
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
                                                        <i class="ti ti-tag fs-1 me-3 text-primary"></i>
                                                        <div>
                                                            <h2 class="mb-1">
                                                                {{ __('messages.category_settings_title') }}
                                                            </h2>
                                                            <div class="text-muted">
                                                                {{ __('messages.category_total_category') }} <strong id="totalCategoryCount">{{ $totalcategory }}</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="btn-list">
                                                        <button type="button" class="btn btn-primary"
                                                            data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                                                            <i class="ti ti-plus fs-4"></i> {{ __('messages.category_create_category') }}
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
                                                                <th><button class="table-sort fs-4 py-3"
                                                                        data-sort="sort-no">{{ __('messages.table_no') }}</th>
                                                                <th><button class="table-sort fs-4 py-3"
                                                                        data-sort="sort-name">{{ __('messages.table_name') }}</th>
                                                                <th><button class="table-sort fs-4 py-3"
                                                                        data-sort="sort-description">{{ __('messages.table_description') }}</th>
                                                                <th style="width:180px;text-align:center" class="fs-4 py-3">
                                                                    {{ __('messages.table_action') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="invoiceTableBody" class="table-tbody">
                                                            @forelse ($categories as $index => $category)
                                                                <tr>
                                                                    <td class="sort-no">
                                                                        {{ $categories->firstItem() + $index }}</td>
                                                                    <td class="sort-name">{{ $category->name }}</td>
                                                                    <td class="sort-description">
                                                                        {{ $category->description }}</td>
                                                                    <td style="text-align:center">
                                                                        <div class="btn-list flex-nowrap justify-content-center">
                                                                            <button type="button" class="btn btn-icon btn-ghost-primary"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#editCategoryModal"
                                                                                data-id="{{ $category->id }}"
                                                                                data-name="{{ $category->name }}"
                                                                                data-description="{{ $category->description }}"
                                                                                title="{{ __('messages.edit') }}">
                                                                                <i class="ti ti-edit"></i>
                                                                            </button>

                                                                            <button type="button" class="btn btn-icon btn-ghost-danger"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#deleteModal"
                                                                                data-onclick="setDeleteFormAction('{{ route('admin.setting.category.destroy', $category->id) }}')"
                                                                                title="{{ __('messages.delete') }}">
                                                                                <i class="ti ti-trash"></i>
                                                                            </button>
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
                                                                            <p class="empty-title">{{ __('messages.no_categories_found') }}</p>
                                                                            <p class="empty-subtitle text-muted">
                                                                                {{ __('messages.it_looks_like_you_havent_added_any_categories_yet') }}
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
                                                        'first' => $categories->firstItem(),
                                                        'last' => $categories->lastItem(),
                                                        'total' => $categories->total(),
                                                    ]) }}
                                                </p>
                                                <div class="ms-auto">
                                                    {{ $categories->appends(request()->query())->links('vendor.pagination.tabler') }}
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
    @include('admin.layouts.modals.category.catmodals')
@endsection
