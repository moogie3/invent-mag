<div class="card-body border-bottom py-3">
    <div class="d-flex justify-content-between">
        <div class="col-md-8">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 bg-light">
                        <div class="card-body py-3">
                            <div class="mb-2">
                                <label class="form-label text-muted mb-2 d-block">
                                    {{ __('messages.warehouse_info_title') }}
                                </label>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3 d-flex align-items-center justify-content-center badge"
                                    style="width: 40px; height: 40px;">
                                    <i class="ti ti-building-warehouse fs-3 text-primary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small text-muted">{{ __('messages.warehouse_info_total_warehouse') }}
                                    </div>
                                    <div class="fw-bold" id="totalWarehouseCount">{{ $totalwarehouse }}</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3 d-flex align-items-center justify-content-center badge"
                                    style="width: 40px; height: 40px;">
                                    <i class="ti ti-building-store fs-3 text-success"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small text-muted">{{ __('messages.warehouse_info_user_store') }}</div>
                                    <div class="fw-bold">{{ $shopname }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 bg-light">
                        <div class="card-body py-3">
                            <div class="mb-2">
                                <label class="form-label text-muted mb-2 d-block">
                                    {{ __('messages.warehouse_details') }}
                                </label>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3 d-flex align-items-center justify-content-center badge"
                                    style="width: 40px; height: 40px;">
                                    <i class="ti ti-map fs-3 text-info"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small text-muted">{{ __('messages.warehouse_info_store_address') }}
                                    </div>
                                    <div class="fw-bold">{{ $address }}</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3 d-flex align-items-center justify-content-center badge"
                                    style="width: 40px; height: 40px;">
                                    <i class="ti ti-container fs-3 text-warning"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small text-muted">{{ __('messages.warehouse_info_main_warehouse') }}
                                    </div>
                                    <div class="fw-bold">
                                        {{ isset($mainWarehouse) ? $mainWarehouse->name : __('messages.warehouse_info_not_set') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('admin.layouts.partials.warehouse.index.search')
    </div>
</div>
