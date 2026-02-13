<div class="page-header no-print">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.warehouse_overview') }}</div>
                <h2 class="page-title"><i class="ti ti-building-warehouse me-2"></i>{{ __('messages.warehouse_title') }}
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <div class="dropdown d-inline-block me-2">
                    <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="ti ti-download fs-4 me-2"></i> {{ __('messages.export') }}
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); exportWarehouses('csv')">Export as CSV</a>
                        </li>
                        <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); exportWarehouses('pdf')">Export as PDF</a>
                        </li>
                    </ul>
                </div>
                <button type="button" class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal"
                    data-bs-target="#createWarehouseModal">
                    <i class="ti ti-plus fs-4"></i> {{ __('messages.warehouse_create_warehouse') }}
                </button>
            </div>
        </div>
    </div>
</div>
