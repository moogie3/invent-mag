<div class="page-header">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    <a href="{{ route('admin.por.index') }}" class="text-muted text-decoration-none">
                        <i class="ti ti-arrow-left"></i> {{ __('messages.back') }}
                    </a>
                </div>
                <h2 class="page-title fw-bold no-print d-flex align-items-center">
                    <i class="ti ti-edit me-2"></i>{{ __('messages.edit_purchase_return') }}
                    <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                        <i class="ti ti-help-circle fs-3"></i>
                    </a>
                </h2>
            </div>
            <div class="col-auto">
                <button type="submit" form="purchase-return-edit-form" class="btn btn-success" id="save-purchase-return-button">
                    <i class="ti ti-device-floppy me-1"></i> {{ __('messages.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</div>