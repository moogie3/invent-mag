<div class="page-header no-print">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.overview') }}</div>
                <h2 class="page-title d-flex align-items-center"><i class="ti ti-receipt-refund me-2"></i> {{ __('messages.model_sales_return') }}
                    <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                        <i class="ti ti-help-circle fs-3"></i>
                    </a>
                </h2>
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
                <div class="dropdown d-inline-block">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button"
                        id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-download me-2"></i> {{ __('messages.export') }}
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                        <li>
                            <a class="dropdown-item" href="#" data-onclick="exportAllSalesReturns('pdf'); return false;">
                                {{ __('messages.export_as_pdf') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" data-onclick="exportAllSalesReturns('csv'); return false;">
                                {{ __('messages.export_as_csv') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <a href="{{ route('admin.sales-returns.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                    <i class="ti ti-plus fs-4 me-2"></i>
                    {{ __('messages.new_sales_return') }}
                </a>
            </div>
        </div>
    </div>
</div>
