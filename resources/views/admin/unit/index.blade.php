@extends('admin.layouts.base')

@section('title', 'Unit')

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
                            Unit
                        </h2>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="btn-list">
                            <a href="{{ route('admin.unit.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                                <i class="ti ti-plus fs-4"></i>
                                Create Unit
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
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title">Unit information</div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-universe fs-2"></i>
                                                            </span>
                                                            Total Unit : <strong>{{ $totalunit }}</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ms-auto text-secondary">
                                        Search :
                                        <div class="ms-2 d-inline-block">
                                            <input type="text" id="searchInput" class="form-control form-control-sm">
                                        </div>
                                        <div class="text-end">
                                            Show
                                            <div class="mx-1 mt-2 d-inline-block">
                                                <select name="entries" id="entriesSelect"
                                                    onchange="window.location.href='?entries=' + this.value;">
                                                    <option value="10" {{ $entries == 10 ? 'selected' : '' }}>10
                                                    </option>
                                                    <option value="25" {{ $entries == 25 ? 'selected' : '' }}>25
                                                    </option>
                                                    <option value="50" {{ $entries == 50 ? 'selected' : '' }}>50
                                                    </option>
                                                </select> entries
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
                                                <th style="width: 100px;"><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-no">No</th>
                                                <th style="width: 200px;"><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-code">Code</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-name">Name</th>
                                                <th style="width:180px;text-align:center" class="fs-4 py-3">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="invoiceTableBody" class="table-tbody">
                                            @foreach ($units as $index => $unit)
                                                <tr>
                                                    <td class="sort-no">{{ $units->firstItem() + $index }}</td>
                                                    <td class="sort-code">{{ $unit->symbol }}</td>
                                                    <td class="sort-name">{{ $unit->name }}</td>
                                                    <td style="text-align:center">
                                                        <div class="dropdown">
                                                            <button class="btn dropdown-toggle align-text-top"
                                                                data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                                Actions
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <a href="{{ route('admin.unit.edit', $unit->id) }}"
                                                                    class="dropdown-item">
                                                                    <i class="ti ti-zoom-scan me-2"></i> View
                                                                </a>

                                                                <button type="button" class="dropdown-item text-danger"
                                                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                                    onclick="setDeleteFormAction('{{ route('admin.unit.destroy', $unit->id) }}')">
                                                                    <i class="ti ti-trash me-2"></i> Delete
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
                            <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel"
                                aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title text-danger" id="deleteModalLabel">Confirm Delete</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body text-center">
                                            <i class="ti ti-alert-circle icon text-danger icon-lg mb-10"></i>
                                            <p class="mt-3">Are you sure you want to delete this unit?</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Cancel</button>
                                            <form id="deleteForm" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- PAGINATION --}}
                            <div class="card-footer d-flex align-items-center">
                                <p class="m-0 text-secondary">
                                    Showing {{ $units->firstItem() }} to {{ $units->lastItem() }} of
                                    {{ $units->total() }}
                                    entries
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
@endsection
