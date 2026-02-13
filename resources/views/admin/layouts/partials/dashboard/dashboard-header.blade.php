<div class="page-header d-print-none" style="position: relative; z-index: 99;">
    <div class="{{ $containerClass ?? 'container-xl' }}">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title d-flex align-items-center">
                    <i class="ti ti-dashboard fs-2 me-2"></i> {{ __('messages.dashboard') }}
                </h2>
                <div class="text-muted mt-1">{{ __('messages.business_overview_performance_metrics') }}</div>
            </div>
            <div class="col-auto ms-auto">
                <div class="btn-list">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle d-flex align-items-center" type="button"
                            data-bs-toggle="dropdown">
                            <i class="ti ti-plus me-2"></i> {{ __('messages.new_invoice') }}
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="{{ route('admin.sales.create') }}">
                                <i class="ti ti-report-money me-2"></i> {{ __('messages.new_sales') }}
                            </a>
                            <a class="dropdown-item" href="{{ route('admin.po.create') }}">
                                <i class="ti ti-shopping-cart me-2"></i> {{ __('messages.new_po') }}
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
