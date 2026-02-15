<div class="page-header no-print">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.overview') }}</div>
                <h2 class="page-title"><i class="ti ti-report-money me-2"></i> {{ __('messages.model_sales') }}</h2>
            </div>
            <div class="col-auto ms-auto">
                <div class="dropdown d-inline-block me-2">
                    <button class="btn btn-secondary dropdown-toggle" type="button"
                        id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-download me-2"></i> {{ __('messages.export') }}
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                        <li>
                            <a class="dropdown-item" href="#" onclick="exportAllSales('pdf'); return false;">
                                {{ __('messages.export_as_pdf') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="exportAllSales('csv'); return false;">
                                {{ __('messages.export_as_csv') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <a href="{{ route('admin.sales.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                    <i class="ti ti-plus fs-4"></i> {{ __('messages.create_sales_order') }}
                </a>
            </div>
        </div>
    </div>
</div>
