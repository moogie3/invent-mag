<div class="page-header no-print">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.overview') }}</div>
                <h2 class="page-title"><i class="ti ti-receipt-refund me-2"></i> {{ __('messages.model_purchase_return') }}</h2>
            </div>
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-secondary d-none d-sm-inline-block"
                    onclick="javascript:window.print();">
                    <i class="ti ti-printer fs-4"></i> {{ __('messages.export_pdf') }}
                </button>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('admin.purchase-returns.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                    <i class="ti ti-plus fs-4"></i> {{ __('messages.new_purchase_return') }}
                </a>
            </div>
        </div>
    </div>
</div>
