<div class="card-body border-bottom py-3">
    <div class="d-flex justify-content-between">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-body border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-box fs-1 me-3 text-primary"></i>
                            <div>
                                <h2 class="mb-1">
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
                            <div class="card border-0 bg-light">
                                <div class="card-body py-3">
                                    <div class="mb-2">
                                        <label class="form-label text-muted mb-2 d-block">
                                            {{ __('messages.product_details') }}
                                        </label>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3 d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;">
                                            <i class="ti ti-box fs-3 text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted">{{ __('messages.total_product') }}</div>
                                            <div class="fw-bold" id="totalProductCount">{{ $totalproduct }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3 d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;">
                                            <i class="ti ti-category fs-3 text-success"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted">{{ __('messages.total_category') }}</div>
                                            <div class="fw-bold" id="totalCategoryCount">{{ $totalcategory }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-white">
                                <div class="card-body py-3">
                                    <div class="mb-2">
                                        <label
                                            class="form-label {{ $lowStockCount > 0 ? 'text-danger' : 'text-success' }} mb-2 d-block">
                                            {{ __('messages.stock_status') }}
                                        </label>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i
                                                class="ti ti-alert-triangle fs-2 {{ $lowStockCount > 0 ? 'text-danger' : 'text-success' }}"></i>
                                        </div>
                                        <div>
                                            <div
                                                class="small {{ $lowStockCount > 0 ? 'text-danger' : 'text-success' }}">
                                                {{ __('messages.low_stock_items') }}</div>
                                            <div
                                                    class="h4 mb-0 {{ $lowStockCount > 0 ? 'text-danger' : 'text-success' }}" id="lowStockItemsCount">
                                                    {{ $lowStockCount }}</div>
                                            @if ($lowStockCount > 0)
                                                <a href="#" class="mt-2 btn btn-sm btn-outline-danger"
                                                    id="viewLowStock">
                                                    {{ __('messages.view_details') }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-white">
                                <div class="card-body py-3">
                                    <div class="mb-2">
                                        <label
                                            class="form-label {{ $expiringSoonCount > 0 ? 'text-warning' : 'text-success' }} mb-2 d-block">
                                            {{ __('messages.expiry_status') }}
                                        </label>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i
                                                class="ti ti-calendar-time fs-2 {{ $expiringSoonCount > 0 ? 'text-warning' : 'text-success' }}"></i>
                                        </div>
                                        <div>
                                            <div
                                                class="small {{ $expiringSoonCount > 0 ? 'text-warning' : 'text-success' }}">
                                                {{ __('messages.expiring_soon') }}</div>
                                            <div
                                                    class="h4 mb-0 {{ $expiringSoonCount > 0 ? 'text-warning' : 'text-success' }}" id="expiringSoonItemsCount">
                                                    {{ $expiringSoonCount }}</div>
                                            @if ($expiringSoonCount > 0)
                                                <a href="#" class="mt-2 btn btn-sm btn-outline-warning"
                                                    id="viewExpiringSoon" data-bs-toggle="modal" data-bs-target="#expiringSoonModal">
                                                    {{ __('messages.view_details') }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            @include('admin.layouts.partials.product.index.search')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
