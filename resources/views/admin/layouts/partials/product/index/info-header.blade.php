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
                                    {{ __('Product Information') }}
                                </h2>
                                <div class="text-muted">
                                    {{ __('Overview of your product inventory and stock levels') }}
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
                                            {{ __('Product Details') }}
                                        </label>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3 d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;">
                                            <i class="ti ti-box fs-3 text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted">{{ __('Total Product') }}</div>
                                            <div class="fw-bold" id="totalProductCount">{{ $totalproduct }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3 d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;">
                                            <i class="ti ti-category fs-3 text-success"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted">{{ __('Total Category') }}</div>
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
                                            {{ __('Stock Status') }}
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
                                                {{ __('Low Stock Items') }}</div>
                                            <div
                                                    class="h4 mb-0 {{ $lowStockCount > 0 ? 'text-danger' : 'text-success' }}" id="lowStockItemsCount">
                                                    {{ $lowStockCount }}</div>
                                            @if ($lowStockCount > 0)
                                                <a href="#" class="mt-2 btn btn-sm btn-outline-danger"
                                                    id="viewLowStock">
                                                    {{ __('View Details') }}
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
                                            {{ __('Expiry Status') }}
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
                                                {{ __('Expiring Soon') }}</div>
                                            <div
                                                    class="h4 mb-0 {{ $expiringSoonCount > 0 ? 'text-warning' : 'text-success' }}" id="expiringSoonItemsCount">
                                                    {{ $expiringSoonCount }}</div>
                                            @if ($expiringSoonCount > 0)
                                                <a href="#" class="mt-2 btn btn-sm btn-outline-warning"
                                                    id="viewExpiringSoon" data-bs-toggle="modal" data-bs-target="#expiringSoonModal">
                                                    {{ __('View Details') }}
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
