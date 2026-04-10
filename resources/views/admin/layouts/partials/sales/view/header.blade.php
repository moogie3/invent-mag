<div class="page-header no-print">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.sales') }}</div>
                <h2 class="page-title no-print fw-bold d-flex align-items-center">
                    <i class="ti ti-eye me-2"></i>{{ __('messages.view_sales_invoice') }}
                    <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('Tutorial') }}">
                        <i class="ti ti-help-circle fs-3"></i>
                    </a>
                </h2>
            </div>
            <div class="col text-end">
                <a href="{{ route('admin.sales') }}" class="btn btn-outline-primary d-none d-sm-inline-block me-2">
                    <i class="ti ti-arrow-left me-1"></i> {{ __('messages.back_to_sales') }}
                </a>
                <a href="{{ route('admin.sales.print', $sales->id) }}" target="_blank" class="btn btn-secondary me-2">
                    <i class="ti ti-printer me-1"></i> {{ __('messages.print_invoice') }}
                </a>
                <a href="{{ route('admin.sales.edit', $sales->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> {{ __('messages.edit_invoice') }}
                </a>
            </div>
        </div>
        @if($sales->customer_credit_used > 0)
        <div class="row mt-3">
            <div class="col-12">
                <div class="alert alert-info mb-0">
                    <i class="ti ti-credit-card me-2"></i>
                    {{ __('messages.credit_applied') }}: {{ \App\Helpers\CurrencyHelper::format($sales->customer_credit_used) }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
