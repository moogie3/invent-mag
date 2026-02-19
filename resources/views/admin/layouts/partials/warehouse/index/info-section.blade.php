<div class="card-body border-bottom py-3">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center">
            <div class="bg-primary-lt rounded-3 p-2 me-3">
                <i class="ti ti-building-warehouse fs-1 text-primary"></i>
            </div>
            <div>
                <h2 class="mb-1 fw-bold">
                    {{ __('messages.warehouse_info_title') }}
                </h2>
                <div class="text-muted">
                    {{ __('messages.warehouse_overview') }}
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-light h-100">
                <div class="card-body py-3">
                    <div class="mb-2">
                        <label class="form-label text-muted mb-2 d-block fw-bold">
                            {{ __('messages.warehouse_info_title') }}
                        </label>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                            style="width: 48px; height: 48px;">
                            <i class="ti ti-building-warehouse fs-2 text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small text-muted">{{ __('messages.warehouse_info_total_warehouse') }}
                            </div>
                            <div class="fw-bold fs-3 text-primary" id="totalWarehouseCount">{{ $totalwarehouse }}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                            style="width: 48px; height: 48px;">
                            <i class="ti ti-building-store fs-2 text-success"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small text-muted">{{ __('messages.warehouse_info_user_store') }}</div>
                            <div class="fw-bold fs-4 text-success">{{ $shopname }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-light h-100">
                <div class="card-body py-3">
                    <div class="mb-2">
                        <label class="form-label text-muted mb-2 d-block fw-bold">
                            {{ __('messages.warehouse_details') }}
                        </label>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                            style="width: 48px; height: 48px;">
                            <i class="ti ti-map fs-2 text-info"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small text-muted">{{ __('messages.warehouse_info_store_address') }}
                            </div>
                            <div class="fw-bold fs-4 text-info">{{ $address }}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-3 badge bg-white shadow-sm"
                            style="width: 48px; height: 48px;">
                            <i class="ti ti-container fs-2 text-warning"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small text-muted">{{ __('messages.warehouse_info_main_warehouse') }}
                            </div>
                            <div class="fw-bold fs-4 text-warning">
                                {{ isset($mainWarehouse) ? $mainWarehouse->name : __('messages.warehouse_info_not_set') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 ms-auto">
            <div class="card border-0 shadow-sm rounded-3 bg-light">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="w-100">
                        {{ __('messages.search_label') }}
                        <div class="ms-2 d-inline-block">
                            <input type="text" id="searchInput" class="form-control form-control-sm">
                        </div>
                        <div class="text-end mt-2">
                            {{ __('messages.warehouse_search_show') }}
                            <div class="mx-1 mt-2 d-inline-block">
                                <select name="entries" id="entriesSelect" onchange="window.location.href='?entries=' + this.value;">
                                    <option value="10" {{ $entries == 10 ? 'selected' : '' }}>10</option>
                                    <option value="25" {{ $entries == 25 ? 'selected' : '' }}>25</option>
                                    <option value="50" {{ $entries == 50 ? 'selected' : '' }}>50</option>
                                </select> {{ __('messages.warehouse_search_entries') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
