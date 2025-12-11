<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.overview') }}</div>
                <h2 class="page-title">
                    <i class="ti ti-receipt-refund me-2"></i>
                    {{ __('messages.purchase_return_details') }}
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('admin.por.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-2"></i>
                    {{ __('messages.back') }}
                </a>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="ti ti-printer me-2"></i>
                    {{ __('messages.print') }}
                </button>
            </div>
        </div>
    </div>
</div>
