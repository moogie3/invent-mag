@extends('admin.layouts.base')

@section('title', 'Unit Settings')

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
                            Unit Settings
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
                                                    <i class="ti ti-universe fs-2"></i>
                                                    Total Unit :
                                                    <strong>{{ $totalunit }}</strong>
                                                </h2>
                                                <div class="d-flex justify-content-between">
                                                    <div class="col-auto ms-auto">
                                                        <div class="btn-list">
                                                            <button type="button" class="btn btn-primary"
                                                                data-bs-toggle="modal" data-bs-target="#createUnitModal">
                                                                <i class="ti ti-plus fs-4"></i> Create Unit
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
                                                                <th style="width: 100px;"><button
                                                                        class="table-sort fs-4 py-3" data-sort="sort-no">No
                                                                </th>
                                                                <th style="width: 200px;"><button
                                                                        class="table-sort fs-4 py-3"
                                                                        data-sort="sort-code">Code</th>
                                                                <th><button class="table-sort fs-4 py-3"
                                                                        data-sort="sort-name">Name</th>
                                                                <th style="width:180px;text-align:center" class="fs-4 py-3">
                                                                    Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="invoiceTableBody" class="table-tbody">
                                                            @foreach ($units as $index => $unit)
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
                                                                                Actions
                                                                            </button>
                                                                            <div class="dropdown-menu">
                                                                                <a href="#" class="dropdown-item"
                                                                                    data-bs-toggle="modal"
                                                                                    data-bs-target="#editUnitModal"
                                                                                    data-id="{{ $unit->id }}"
                                                                                    data-symbol="{{ $unit->symbol }}"
                                                                                    data-name="{{ $unit->name }}">
                                                                                    <i class="ti ti-edit me-2"></i> Edit
                                                                                </a>


                                                                                <button type="button"
                                                                                    class="dropdown-item text-danger"
                                                                                    data-bs-toggle="modal"
                                                                                    data-bs-target="#deleteModal"
                                                                                    onclick="setDeleteFormAction('{{ route('admin.setting.unit.destroy', $unit->id) }}')">
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
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body text-center">
                                                            <i
                                                                class="ti ti-alert-circle icon text-danger icon-lg mb-10"></i>
                                                            <p class="mt-3">Are you sure you want to delete this unit?</p>
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

                                            <div class="modal fade" id="createUnitModal" tabindex="-1"
                                                aria-labelledby="createUnitModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="createUnitModalLabel">
                                                                Create Warehouse</h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form id="createUnitForm"
                                                            action="{{ route('admin.setting.unit.store') }}"
                                                            method="POST">
                                                            @csrf
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label for="symbol" class="form-label">Code</label>
                                                                    <input type="text" class="form-control"
                                                                        id="symbol" name="symbol">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="name" class="form-label">Name</label>
                                                                    <input type="text" class="form-control"
                                                                        id="name" name="name">
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit"
                                                                    class="btn btn-primary">Save</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal fade" id="editUnitModal" tabindex="-1"
                                                aria-labelledby="editUnitModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editUnitModalLabel">Edit Unit</h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form id="editUnitForm" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="modal-body">
                                                                <input type="hidden" id="unitId" name="id">
                                                                <div class="mb-3">
                                                                    <label for="unitSymbolEdit"
                                                                        class="form-label">Code</label>
                                                                    <input type="text" class="form-control"
                                                                        id="unitSymbolEdit" name="symbol">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="unitNameEdit"
                                                                        class="form-label">Name</label>
                                                                    <input type="text" class="form-control"
                                                                        id="unitNameEdit" name="name">
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit"
                                                                    class="btn btn-primary">Save</button>
                                                            </div>
                                                        </form>
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
                </div>
            </div>
        </div>
    </div>
@endsection
