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
                        <button type="button" class="btn btn-secondary d-none d-sm-inline-block"
                            onclick="javascript:window.print();">
                            <i class="ti ti-printer fs-4"></i>
                            Export PDF
                        </button>
                        <button type="button" class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal"
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
                                                    <div class="col-md-5">
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-file-invoice fs-2"></i>
                                                            </span>
                                                            Total Warehouse : <strong>{{ $totalwarehouse }}</strong>
                                                        </div>
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-container fs-2"></i>
                                                            </span>
                                                            Main Warehouse :
                                                            <strong>{{ isset($mainWarehouse) ? $mainWarehouse->name : 'Not set' }}</strong>
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
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-is-main">Main</th>
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
                                                    <td class="sort-is-main">
                                                        @if ($wo->is_main)
                                                            <span class="badge bg-green-lt">Main</span>
                                                        @else
                                                            <span class="badge bg-secondary-lt">Sub</span>
                                                        @endif
                                                    </td>
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
                                                                    data-description="{{ $wo->description }}"
                                                                    data-is-main="{{ $wo->is_main }}">
                                                                    <i class="ti ti-edit me-2"></i> Edit
                                                                </a>

                                                                @if (!$wo->is_main)
                                                                    <a href="{{ route('admin.warehouse.set-main', $wo->id) }}"
                                                                        class="dropdown-item">
                                                                        <i class="ti ti-star me-2"></i> Set as Main
                                                                    </a>
                                                                @else
                                                                    <a href="{{ route('admin.warehouse.unset-main', $wo->id) }}"
                                                                        class="dropdown-item">
                                                                        <i class="ti ti-star-off me-2"></i> Unset Main
                                                                    </a>
                                                                @endif

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
    @include('admin.layouts.modals.waremodals')
@endsection
