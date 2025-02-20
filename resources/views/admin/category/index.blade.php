@extends('admin.layouts.base')

@section('title', 'Category')

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
                            Category
                        </h2>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="btn-list">
                            <a href="{{ route('admin.category.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                                <i class="ti ti-plus fs-4"></i>
                                Create Category
                            </a>
                        </div>
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
                                        <div class="col">
                                            <h2 class="page-title">
                                                Category Information
                                            </h2>
                                        </div>
                                        <div class="ms-auto text-secondary">
                                            Search :
                                            <div class="ms-2 d-inline-block">
                                                <input type="text" id="searchInput" class="form-control form-control-sm">
                                            </div>
                                            <div class="text-end">
                                                Show
                                                <div class="mx-1 mt-2 d-inline-block">
                                                    <select name="entries" id="entriesSelect" onchange="window.location.href='?entries=' + this.value;">
                                                    <option value="10" {{ $entries == 10 ? 'selected' : '' }}>10</option>
                                                    <option value="25" {{ $entries == 25 ? 'selected' : '' }}>25</option>
                                                    <option value="50" {{ $entries == 50 ? 'selected' : '' }}>50</option>
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
                                                    <th><button class="table-sort fs-4 py-3" data-sort="sort-name">Name</th>
                                                    <th><button class="table-sort fs-4 py-3" data-sort="sort-description">Description</th>
                                                    <th style="width:180px;text-align:center" class="fs-4 py-3">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="invoiceTableBody" class="table-tbody">
                                                @foreach ($categories as $index => $category)
                                                    <tr>
                                                        <td class="sort-no">{{ $categories->firstItem() + $index }}</td>
                                                        <td class="sort-name">{{ $category->name }}</td>
                                                        <td class="sort-description">{{ $category->description }}</td>
                                                        <td style="text-align:center">
                                                            <div class="dropdown">
                                                                <button class="btn dropdown-toggle align-text-top"
                                                                    data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                                    Actions
                                                                </button>
                                                                <div class="dropdown-menu">
                                                                    <!-- View Button -->
                                                                    <a href="{{ route('admin.category.edit', $category->id) }}"
                                                                        class="dropdown-item">
                                                                        <i class="ti ti-zoom-scan me-2"></i> View
                                                                    </a>

                                                                    <!-- Delete Form -->
                                                                    <form method="POST" action="{{ route('admin.category.destroy', $category->id) }}"
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
                                        Showing {{ $categories->firstItem() }} to {{ $categories->lastItem() }} of {{ $categories->total() }}
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
@endsection
