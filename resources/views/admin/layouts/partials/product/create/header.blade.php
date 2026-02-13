<div class="page-header">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.product_management') }}</div>
                <h2 class="page-title fw-bold">{{ __('messages.create_product') }}</h2>
            </div>
            <div class="col-auto ms-auto">
                <div class="btn-list">
                    <a href="{{ route('admin.product') }}" class="btn btn-outline-primary">
                        <i class="ti ti-arrow-left me-1"></i> {{ __('messages.back_to_products') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
