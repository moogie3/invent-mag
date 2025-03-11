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
                                <div class="card-body">
                                    <h4 class="subheader">Business settings</h4>
                                    <div class="list-group list-group-transparent">
                                        <a href="{{ route('admin.setting.notifications') }}"
                                            class="list-group-item list-group-item-action d-flex align-items-center">My
                                            Notifications</a>
                                        <a href="{{ route('admin.setting.profile.edit') }}"
                                            class="list-group-item list-group-item-action d-flex align-items-center">Account
                                            Settings</a>
                                        <a href="{{ route('admin.setting.currency.edit') }}"
                                            class="list-group-item list-group-item-action d-flex align-items-center">Currency
                                            Settings</a>
                                        <a href="{{ route('admin.setting.unit') }}"
                                            class="list-group-item list-group-item-action d-flex align-items-center">Units
                                            Settings</a>
                                        <a href="{{ route('admin.setting.category') }}"
                                            class="list-group-item list-group-item-action d-flex align-items-center active">Category
                                            Settings</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-9 d-flex flex-column">
                                <div class="container-xl">
                                    <div class="row row-deck row-cards">
                                        <div class="col-md-12">
                                            <div class="card card-primary">
                                                <div class="card-body border-bottom py-3">
                                                    <h2 class="mb-4">Category</h2>
                                                    <div class="d-flex justify-content-between">
                                                        <div class="col-md-5">
                                                            <div class="card">
                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <span
                                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                                <i class="ti ti-category fs-2"></i>
                                                                            </span>
                                                                            Total Category :
                                                                            <strong>{{ $totalcategory }}</strong>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="col-auto ms-auto">
                                                            <div class="btn-list">
                                                                <a href="{{ route('admin.setting.category.create') }}"
                                                                    class="btn btn-primary d-none d-sm-inline-block">
                                                                    <i class="ti ti-plus fs-4"></i>
                                                                    Create Category
                                                                </a>
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
                                                                    <th style="width:180px;text-align:center"
                                                                        class="fs-4 py-3">Action</th>
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
                                                                                    <a href="{{ route('admin.setting.category.edit', $category->id) }}"
                                                                                        class="dropdown-item">
                                                                                        <i class="ti ti-zoom-scan me-2"></i>
                                                                                        View
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

                                                {{-- MODAL --}}
                                                <div class="modal fade" id="deleteModal" tabindex="-1"
                                                    aria-labelledby="deleteModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title text-danger" id="deleteModalLabel">
                                                                    Confirm Delete</h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body text-center">
                                                                <i
                                                                    class="ti ti-alert-circle icon text-danger icon-lg mb-10"></i>
                                                                <p class="mt-3">Are you sure you want to delete this
                                                                    category ?</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Cancel</button>
                                                                <form id="deleteForm" method="POST">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit"
                                                                        class="btn btn-danger">Delete</button>
                                                                </form>
                                                            </div>
                                                        </div>
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
    </div>
@endsection
