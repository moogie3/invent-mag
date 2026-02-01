<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.overview') }}</div>
                <h2 class="page-title">
                    <i class="ti ti-receipt-refund me-2"></i>
                    {{ __('messages.sales_return_details') }}
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('admin.sales-returns.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-2"></i>
                    {{ __('messages.back') }}
                </a>
                <a href="{{ route('admin.sales-returns.edit', $salesReturn->id) }}" class="btn btn-info">
                    <i class="ti ti-edit me-2"></i>
                    {{ __('messages.edit') }}
                </a>
                <a href="{{ route('admin.sales-returns.print', $salesReturn->id) }}" target="_blank" class="btn btn-primary">
                    <i class="ti ti-printer me-2"></i>
                    {{ __('messages.print') }}
                </a>
            </div>
        </div>
    </div>
</div>
