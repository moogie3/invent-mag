<div class="card-body border-bottom py-3">
    <div class="d-flex justify-content-between">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="card-title">Product information</div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i
                                        class="ti ti-alert-triangle fs-2 {{ $lowStockCount > 0 ? 'text-danger' : 'text-success' }}"></i>
                                </span>
                                Low Stock :
                                <strong class="{{ $lowStockCount > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $lowStockCount }}
                                </strong>
                                @if ($lowStockCount > 0)
                                    <a href="#" class="ms-2 btn btn-sm btn-outline-danger" id="viewLowStock">
                                        View
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-box fs-2"></i>
                                </span>
                                Total Product : <strong>{{ $totalproduct }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i
                                        class="ti ti-calendar-time fs-2 {{ $expiringSoonCount > 0 ? 'text-warning' : 'text-success' }}"></i>
                                </span>
                                Expiring Soon :
                                <strong class="{{ $expiringSoonCount > 0 ? 'text-warning' : 'text-success' }}">
                                    {{ $expiringSoonCount }}
                                </strong>
                                @if ($expiringSoonCount > 0)
                                    <a href="#" class="ms-2 btn btn-sm btn-outline-warning" id="viewExpiringSoon">
                                        View
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-category fs-2"></i>
                                </span>
                                Total Category : <strong>{{ $totalcategory }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('admin.layouts.partials.product.index.search')
    </div>
</div>
