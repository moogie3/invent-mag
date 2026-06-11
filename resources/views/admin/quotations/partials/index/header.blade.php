<div class="page-header no-print">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.overview') }}</div>
                <h2 class="page-title d-flex align-items-center"><i class="ti ti-file-description me-2"></i> {{ __('messages.quotations') }}
                    <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                        <i class="ti ti-help-circle fs-3"></i>
                    </a>
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <div class="d-flex align-items-center gap-3">
                    <div class="text-muted small d-none d-md-flex align-items-center gap-2">
                        <span class="fw-bold fs-3 text-primary">{{ array_sum($statusCounts) }}</span>
                        <span>{{ __('messages.quotations') }}</span>
                        <span class="mx-1">|</span>
                        <span class="fw-bold fs-3 text-success">{{ \App\Helpers\CurrencyHelper::format($totalAmount) }}</span>
                    </div>
                    <div class="dropdown d-inline-block me-2">
                        <button class="btn btn-secondary dropdown-toggle" type="button"
                            id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-download me-2"></i> {{ __('messages.export') }}
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                            <li><a class="dropdown-item" href="#" data-onclick="exportAllQuotations('pdf'); return false;">{{ __('messages.export_as_pdf') }}</a></li>
                            <li><a class="dropdown-item" href="#" data-onclick="exportAllQuotations('csv'); return false;">{{ __('messages.export_as_csv') }}</a></li>
                        </ul>
                    </div>
                    <a href="{{ route('admin.quotations.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                        <i class="ti ti-plus fs-4"></i> {{ __('messages.create_quotation') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
