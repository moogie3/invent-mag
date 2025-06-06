@extends('admin.layouts.base')

@section('title', 'Product')

@section('content')
    <div class="page-wrapper">
        <div class="page-header no-print">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            Overview
                        </div>
                        <h2 class="page-title">
                            <i class="ti ti-box me-2"></i> Product
                        </h2>
                    </div>
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-secondary d-none d-sm-inline-block"
                            onclick="javascript:window.print();">
                            <i class="ti ti-printer fs-4"></i>
                            Export PDF
                        </button>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="{{ route('admin.product.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                            <i class="ti ti-plus fs-4"></i>
                            Create Product
                        </a>
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
                                                <div class="card-title">Product information</div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i
                                                                    class="ti ti-alert-triangle fs-2 {{ $lowStockCount > 0 ? 'text-danger' : 'text-success' }}"></i>
                                                            </span>
                                                            Low Stock :
                                                            <strong
                                                                class="{{ $lowStockCount > 0 ? 'text-danger' : 'text-success' }}">
                                                                {{ $lowStockCount }}
                                                            </strong>
                                                            @if ($lowStockCount > 0)
                                                                <a href="#" class="ms-2 btn btn-sm btn-outline-danger"
                                                                    id="viewLowStock">
                                                                    View
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-box fs-2"></i>
                                                            </span>
                                                            Total Product : <strong>{{ $totalproduct }}</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i
                                                                    class="ti ti-calendar-time fs-2 {{ $expiringSoonCount > 0 ? 'text-warning' : 'text-success' }}"></i>
                                                            </span>
                                                            Expiring Soon :
                                                            <strong
                                                                class="{{ $expiringSoonCount > 0 ? 'text-warning' : 'text-success' }}">
                                                                {{ $expiringSoonCount }}
                                                            </strong>
                                                            @if ($expiringSoonCount > 0)
                                                                <a href="#"
                                                                    class="ms-2 btn btn-sm btn-outline-warning"
                                                                    id="viewExpiringSoon">
                                                                    View
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-2">
                                                            <span
                                                                class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                                                <i class="ti ti-category fs-2"></i>
                                                            </span>
                                                            Total Category : <strong>{{ $totalcategory }}</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ms-auto text-secondary no-print">
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
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-no">No</th>
                                                <th><button class="table-sort fs-4 py-3">Picture</th>
                                                <th class="no-print"><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-code">Code
                                                </th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-name">Name</th>
                                                <th class="no-print"><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-quantity">QTY
                                                </th>
                                                <th class="no-print"><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-category">CAT
                                                </th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-unit">Unit</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-price">Price</th>
                                                <th><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-sellingprice">Selling Price</th>
                                                <th><button class="table-sort fs-4 py-3" data-sort="sort-supplier">Supplier
                                                </th>
                                                <th class="text-center"><button class="table-sort fs-4 py-3"
                                                        data-sort="sort-expiry">Expiry Date
                                                </th>
                                                <th style="width:100px;text-align:center" class="fs-4 py-3 no-print">Action
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="invoiceTableBody" class="table-tbody">
                                            @foreach ($products as $index => $product)
                                                <tr>
                                                    <td class="sort-no">{{ $products->firstItem() + $index }}</td>
                                                    <td class="sort-image" style="width:120px">
                                                        <img src="{{ asset($product->image) }}" width="80px"
                                                            height="80px">
                                                    </td>
                                                    <td class="sort-code no-print">{{ $product->code }}</td>
                                                    <td class="sort-name">{{ $product->name }}</td>
                                                    <td class="sort-quantity no-print text-center">
                                                        {{ $product->stock_quantity }}
                                                        @if ($product->hasLowStock())
                                                            <span class="badge bg-red-lt">Low Stock</span>
                                                            @if ($product->low_stock_threshold)
                                                                <small class="d-block text-muted">Threshold:
                                                                    {{ $product->low_stock_threshold }}</small>
                                                            @endif
                                                        @endif
                                                    </td>
                                                    <td class="sort-category no-print">{{ $product->category->name }}</td>
                                                    <td class="sort-unit">{{ $product->unit->symbol }}</td>
                                                    <td class="sort-price text-center">
                                                        {{ \App\Helpers\CurrencyHelper::format($product->price) }}</td>
                                                    <td class="sort-sellingprice text-center">
                                                        {{ \App\Helpers\CurrencyHelper::format($product->selling_price) }}
                                                    </td>
                                                    <td class="sort-supplier text-center">{{ $product->supplier->name }}
                                                    </td>
                                                    <td class="sort-expiry text-center">
                                                        @if ($product->has_expiry && $product->expiry_date)
                                                            {{ $product->expiry_date->format('d-m-Y') }}
                                                            @php
                                                                [
                                                                    $badgeClass,
                                                                    $badgeText,
                                                                ] = \App\Helpers\ProductHelper::getExpiryClassAndText(
                                                                    $product->expiry_date,
                                                                );
                                                            @endphp
                                                            @if ($badgeClass)
                                                                <span
                                                                    class="{{ $badgeClass }}">{{ $badgeText }}</span>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td class="no-print" style="text-align:center">
                                                        <div class="dropdown">
                                                            <button class="btn dropdown-toggle align-text-top"
                                                                data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                                Actions
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <a href="javascript:void(0)"
                                                                    onclick="loadProductDetails('{{ $product->id }}')"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#viewProductModal"
                                                                    class="dropdown-item">
                                                                    <i class="ti ti-zoom-scan me-2"></i> View
                                                                </a>

                                                                <a href="{{ route('admin.product.edit', $product->id) }}"
                                                                    class="dropdown-item">
                                                                    <i class="ti ti-edit me-2"></i> Edit
                                                                </a>

                                                                <button type="button" class="dropdown-item text-danger"
                                                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                                    onclick="setDeleteFormAction('{{ route('admin.product.destroy', $product->id) }}')">
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
                                    Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of
                                    {{ $products->total() }}
                                    entries
                                </p>
                                <div class="ms-auto">
                                    {{ $products->appends(request()->query())->links('vendor.pagination.tabler') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('admin.layouts.modals.productmodals')
@endsection
