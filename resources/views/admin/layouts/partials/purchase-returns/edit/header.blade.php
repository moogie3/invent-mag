<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.overview') }}</div>
                <h2 class="page-title">
                    <i class="ti ti-receipt-refund me-2"></i>
                    {{ __('messages.edit_purchase_return') }}
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('admin.purchase-returns.index') }}" class="btn btn-secondary d-none d-sm-inline-block">
                    <i class="ti ti-arrow-left fs-4"></i>
                    {{ __('messages.back') }}
                </a>
            </div>
        </div>
    </div>
</div>