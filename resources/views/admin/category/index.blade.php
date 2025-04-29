@extends('admin.layouts.base')

@section('title', 'Category Settings')

@section('content')
    <div class="page-wrapper">
        <div class="page-header">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            Overview
                        </div>
                        <h2 class="page-title">
                            Category Settings
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    <div class="card">
                        <div class="row g-0">
                            <div class="col-12 col-md-3 border-end">
                                @include('admin.layouts.menu')
                            </div>
                            <div class="col-12 col-md-9 d-flex flex-column">
                                <div class="row row-deck row-cards">
                                    <div class="col-md-12">
                                        <div class="card card-primary">
                                            <div class="card-body border-bottom py-3">
                                                <h2 class="mb-4">
                                                    <i class="ti ti-category fs-2"></i>
                                                    Total Category :
                                                    <strong>{{ $totalcategory }}</strong>
                                                </h2>
                                                <div class="d-flex justify-content-between">
                                                    <div class="col-auto ms-auto">
                                                        <div class="btn-list">
                                                            <button type="button" class="btn btn-primary"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#createCategoryModal">
                                                                <i class="ti ti-plus fs-4"></i> Create Category
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
                                                                <th><button class="table-sort fs-4 py-3"
                                                                        data-sort="sort-no">No</th>
                                                                <th><button class="table-sort fs-4 py-3"
                                                                        data-sort="sort-name">Name</th>
                                                                <th><button class="table-sort fs-4 py-3"
                                                                        data-sort="sort-description">Description</th>
                                                                <th style="width:180px;text-align:center" class="fs-4 py-3">
                                                                    Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="invoiceTableBody" class="table-tbody">
                                                            @foreach ($categories as $index => $category)
                                                                <tr>
                                                                    <td class="sort-no">
                                                                        {{ $categories->firstItem() + $index }}</td>
                                                                    <td class="sort-name">{{ $category->name }}</td>
                                                                    <td class="sort-description">
                                                                        {{ $category->description }}</td>
                                                                    <td style="text-align:center">
                                                                        <div class="dropdown">
                                                                            <button
                                                                                class="btn dropdown-toggle align-text-top"
                                                                                data-bs-toggle="dropdown"
                                                                                data-bs-boundary="viewport">
                                                                                Actions
                                                                            </button>
                                                                            <div class="dropdown-menu">
                                                                                <a href="#" class="dropdown-item"
                                                                                    data-bs-toggle="modal"
                                                                                    data-bs-target="#editCategoryModal"
                                                                                    data-id="{{ $category->id }}"
                                                                                    data-name="{{ $category->name }}"
                                                                                    data-description="{{ $category->description }}">
                                                                                    <i class="ti ti-edit me-2"></i> Edit
                                                                                </a>

                                                                                <button type="button"
                                                                                    class="dropdown-item text-danger"
                                                                                    data-bs-toggle="modal"
                                                                                    data-bs-target="#deleteModal"
                                                                                    onclick="setDeleteFormAction('{{ route('admin.setting.category.destroy', $category->id) }}')">
                                                                                    <i class="ti ti-trash me-2"></i>
                                                                                    Delete
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
                                                    Showing {{ $categories->firstItem() }} to
                                                    {{ $categories->lastItem() }} of
                                                    {{ $categories->total() }}
                                                    entries
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
    @include('admin.layouts.modals.catmodals')
@endsection
