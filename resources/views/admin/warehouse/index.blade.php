@extends('admin.layouts.base')

@section('title', 'Warehouse')

@section('content')
    <div class="page-wrapper">
        <div class="page-header no-print">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Overview</div>
                        <h2 class="page-title">Warehouse</h2>
                    </div>
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-secondary" onclick="javascript:window.print();">
                            Export PDF
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#createWarehouseModal">
                            <i class="ti ti-plus fs-4"></i> Create Warehouse
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
                                                <div class="card-title">Warehouse information</div>
                                                <div class="purchase-info row">
                                                    <div class="col-md-4">
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-building-store fs-2"></i>
                                                            </span>
                                                            User Store : <strong>{{ $shopname }}</strong>
                                                        </div>
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-map fs-2"></i>
                                                            </span>
                                                            Store Address : <strong>{{ $address }}</strong>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-file-invoice fs-2"></i>
                                                            </span>
                                                            Total Warehouse : <strong>{{ $totalwarehouse }}</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ms-auto text-secondary no-print">
                                        <div class="ms-2 mb-2 text-end">
                                            Search :
                                            <div class="ms-2">
                                                <input type="text" id="searchInput" class="form-control form-control-sm">
                                            </div>
                                        </div>
                                        <div class="mb-2 text-end">
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
                                                <th class="no-print"><button class="table-sort fs-4 py-3 no-print"
                                                        data-sort="sort-no">No
                                                </th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-name">Name
                                                </th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-address">Address
                                                </th>
                                                <th><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-description">Description</th>
                                                <th style="width:180px;text-align:center" class="fs-4 py-3 no-print">
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="invoiceTableBody" class="table-tbody">
                                            @foreach ($wos as $index => $wo)
                                                <tr>
                                                    <td class="sort-no no-print">{{ $wos->firstItem() + $index }}</td>
                                                    <td class="sort-name">{{ $wo->name }}</td>
                                                    <td class="sort-address">{{ $wo->address }}</td>
                                                    <td class="sort-description">{{ $wo->description }}</td>
                                                    <td class="no-print" style="text-align:center">
                                                        <div class="dropdown">
                                                            <button class="btn dropdown-toggle align-text-top"
                                                                data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                                Actions
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <a href="#" class="dropdown-item"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#editWarehouseModal"
                                                                    data-id="{{ $wo->id }}"
                                                                    data-name="{{ $wo->name }}"
                                                                    data-address="{{ $wo->address }}"
                                                                    data-description="{{ $wo->description }}">
                                                                    <i class="ti ti-edit me-2"></i> Edit
                                                                </a>

                                                                <button type="button" class="dropdown-item text-danger"
                                                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                                    onclick="setDeleteFormAction('{{ route('admin.warehouse.destroy', $wo->id) }}')">
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
                                            <p class="mt-3">Are you sure you want to delete this Warehouse?</p>
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

                            <div class="modal fade" id="createWarehouseModal" tabindex="-1"
                                aria-labelledby="createWarehouseModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="createWarehouseModalLabel">Create Warehouse</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <form id="createWarehouseForm" action="{{ route('admin.warehouse.store') }}"
                                            method="POST">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label for="warehouseName" class="form-label">Name</label>
                                                    <input type="text" class="form-control" id="warehouseName"
                                                        name="name">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="warehouseAddress" class="form-label">Address</label>
                                                    <input type="text" class="form-control" id="warehouseAddress"
                                                        name="address">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="warehouseDescription"
                                                        class="form-label">Description</label>
                                                    <textarea class="form-control" id="warehouseDescription" name="description" rows="3"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="editWarehouseModal" tabindex="-1"
                                aria-labelledby="editWarehouseModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editWarehouseModalLabel">Edit Warehouse</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <form id="editWarehouseForm" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <input type="hidden" id="warehouseId" name="id">

                                                <div class="mb-3">
                                                    <label for="warehouseNameEdit" class="form-label">Name</label>
                                                    <input type="text" class="form-control" id="warehouseNameEdit"
                                                        name="name">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="warehouseAddressEdit" class="form-label">Address</label>
                                                    <input type="text" class="form-control" id="warehouseAddressEdit"
                                                        name="address">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="warehouseDescriptionEdit"
                                                        class="form-label">Description</label>
                                                    <textarea class="form-control" id="warehouseDescriptionEdit" name="description" rows="3"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- PAGINATION --}}
                            <div class="card-footer d-flex align-items-center">
                                <p class="m-0 text-secondary">
                                    Showing {{ $wos->firstItem() }} to {{ $wos->lastItem() }} of {{ $wos->total() }}
                                    entries
                                </p>
                                <div class="ms-auto">
                                    {{ $wos->appends(request()->query())->links('vendor.pagination.tabler') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const editWarehouseModal = document.getElementById("editWarehouseModal");

        editWarehouseModal.addEventListener("show.bs.modal", function(event) {
            // Get the button that triggered the modal
            const button = event.relatedTarget;

            // Get warehouse data from the button attributes
            const warehouseId = button.getAttribute("data-id");
            const warehouseName = button.getAttribute("data-name");
            const warehouseAddress = button.getAttribute("data-address");
            const warehouseDescription = button.getAttribute("data-description");

            // Populate the form fields inside the modal
            document.getElementById("warehouseId").value = warehouseId;
            document.getElementById("warehouseNameEdit").value = warehouseName;
            document.getElementById("warehouseAddressEdit").value = warehouseAddress;
            document.getElementById("warehouseDescriptionEdit").value = warehouseDescription;

            // Set the form action dynamically
            document.getElementById("editWarehouseForm").action =
                "{{ route('admin.warehouse.update', '') }}/" + warehouseId;
        });
    });
</script>
