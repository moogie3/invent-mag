<div class="page-header">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.purchasing') }}</div>
                <h2 class="page-title fw-bold">{{ __('messages.new_purchase_return') }}</h2>
            </div>
            <div class="col-auto ms-auto">
                <div class="btn-list">
                    <a href="{{ route('admin.por.index') }}" class="btn btn-outline-primary">
                        <i class="ti ti-arrow-left me-1"></i> {{ __('messages.back_to_returns') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
