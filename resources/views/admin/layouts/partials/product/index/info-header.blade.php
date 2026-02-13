<div class="card-body border-bottom py-3">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center">
            <div class="bg-primary-lt rounded-3 p-2 me-3">
                <i class="ti ti-box fs-1 text-primary"></i>
            </div>
            <div>
                <h2 class="mb-1 fw-bold">
                    {{ __('messages.product_information') }}
                </h2>
                <div class="text-muted">
                    {{ __('messages.overview_product_inventory_stock_levels') }}
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-light-lt">
                <div class="card-body py-3">
                    <div class="mb-2">
                        <label class="form-label text-muted mb-2 d-block fw-bold">
                            {{ __('messages.product_details') }}
                        </label>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                            style="width: 40px; height: 40px;">
                            <i class="ti ti-box fs-3 text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small text-muted">{{ __('messages.total_product') }}</div>
                            <div class="fw-bold fs-3" id="totalProductCount">{{ $totalproduct }}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                            style="width: 40px; height: 40px;">
                            <i class="ti ti-category fs-3 text-success"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small text-muted">{{ __('messages.total_category') }}</div>
                            <div class="fw-bold fs-3" id="totalCategoryCount">{{ $totalcategory }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-red-lt">
                <div class="card-body py-3">
                    <div class="mb-2">
                        <label
                            class="form-label {{ $lowStockCount > 0 ? 'text-danger' : 'text-success' }} mb-2 d-block fw-bold">
                            {{ __('messages.stock_status') }}
                        </label>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                            style="width: 48px; height: 48px;">
                            <i
                                class="ti ti-alert-triangle fs-2 {{ $lowStockCount > 0 ? 'text-danger' : 'text-success' }}"></i>
                        </div>
                        <div>
                            <div
                                class="small {{ $lowStockCount > 0 ? 'text-danger' : 'text-success' }}">
                                {{ __('messages.low_stock_items') }}</div>
                            <div class="h3 mb-0 {{ $lowStockCount > 0 ? 'text-danger' : 'text-success' }}"
                                id="lowStockItemsCount">
                                {{ $lowStockCount }}</div>
                            @if ($lowStockCount > 0)
                                <a href="#" class="mt-2 btn btn-sm btn-outline-danger rounded-pill"
                                    id="viewLowStock" data-bs-toggle="modal" data-bs-target="#lowStockModal">
                                    {{ __('messages.view_details') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-orange-lt">
                <div class="card-body py-3">
                    <div class="mb-2">
                        <label
                            class="form-label {{ $expiringSoonCount > 0 ? 'text-warning' : 'text-success' }} mb-2 d-block fw-bold">
                            {{ __('messages.expiry_status') }}
                        </label>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                            style="width: 48px; height: 48px;">
                            <i
                                class="ti ti-calendar-time fs-2 {{ $expiringSoonCount > 0 ? 'text-warning' : 'text-success' }}"></i>
                        </div>
                        <div>
                            <div
                                class="small {{ $expiringSoonCount > 0 ? 'text-warning' : 'text-success' }}">
                                {{ __('messages.expiring_soon') }}</div>
                            <div class="h3 mb-0 {{ $expiringSoonCount > 0 ? 'text-warning' : 'text-success' }}"
                                id="expiringSoonItemsCount">
                                {{ $expiringSoonCount }}</div>
                            @if ($expiringSoonCount > 0)
                                <a href="#" class="mt-2 btn btn-sm btn-outline-warning rounded-pill"
                                    id="viewExpiringSoon" data-bs-toggle="modal"
                                    data-bs-target="#expiringSoonModal">
                                    {{ __('messages.view_details') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-light">
                 <div class="card-body py-3 d-flex align-items-center">
                    <div class="w-100">
                        @include('admin.layouts.partials.product.index.search')
                    </div>
                 </div>
            </div>
        </div>
    </div>
</div>
