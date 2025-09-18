<div class="card-body border-bottom py-3">
    <div class="d-flex justify-content-between">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="card-title">{{ __('messages.warehouse_info_title') }}</div>
                    <div class="purchase-info row">
                        <div class="col-md-4">
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-building-store fs-2"></i>
                                </span>
                                {{ __('messages.warehouse_info_user_store') }} <strong>{{ $shopname }}</strong>
                            </div>
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-map fs-2"></i>
                                </span>
                                {{ __('messages.warehouse_info_store_address') }} <strong>{{ $address }}</strong>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-file-invoice fs-2"></i>
                                </span>
                                {{ __('messages.warehouse_info_total_warehouse') }} <strong>{{ $totalwarehouse }}</strong>
                            </div>
                            <div class="mb-2">
                                <span class="nav-link-icon d-md-none d-lg-inline-block align-middle">
                                    <i class="ti ti-container fs-2"></i>
                                </span>
                                {{ __('messages.warehouse_info_main_warehouse') }}
                                <strong>{{ isset($mainWarehouse) ? $mainWarehouse->name : __('messages.warehouse_info_not_set') }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('admin.layouts.partials.warehouse.index.search')
    </div>
</div>
