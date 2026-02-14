<div class="page-header">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.purchasing') }}</div>
                <h2 class="page-title fw-bold">{{ __('messages.edit_purchase_return') }}</h2>
            </div>
            <div class="col-auto ms-auto">
                <div class="btn-list">
                    <a href="{{ route('admin.por.index') }}" class="btn btn-outline-primary d-none d-sm-inline-block">
                        <i class="ti ti-arrow-left me-1"></i> {{ __('messages.back_to_returns') }}
                    </a>
                    <button type="submit" form="purchase-return-edit-form" class="btn btn-success" id="save-purchase-return-button">
                        <i class="ti ti-device-floppy me-1"></i> {{ __('messages.save_changes') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>