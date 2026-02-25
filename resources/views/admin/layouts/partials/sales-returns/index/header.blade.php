<div class="page-header no-print">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.overview') }}</div>
                <h2 class="page-title">
                    <i class="ti ti-receipt-refund me-2"></i>
                    {{ __('messages.model_sales_return') }}
                </h2>
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
                <a href="{{ route('admin.sales-returns.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                    <i class="ti ti-plus fs-4"></i>
                    {{ __('messages.new_sales_return') }}
                </a>
            </div>
        </div>
    </div>
</div>
