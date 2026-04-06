<div class="page-header no-print">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.sales') }}</div>
                <h2 class="page-title fw-bold no-print d-flex align-items-center">
                    <i class="ti ti-edit me-2"></i>{{ __('messages.edit_sales_order') }}
                    <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('Tutorial') }}">
                        <i class="ti ti-help-circle fs-3"></i>
                    </a>
                </h2>
            </div>
            <div class="col text-end">
                <a href="{{ route('admin.sales') }}" class="btn btn-outline-primary d-none d-sm-inline-block">
                    <i class="ti ti-arrow-left me-1"></i> {{ __('messages.back_to_sales') }}
                </a>
                <button type="submit" form="edit-sales-form" class="btn btn-success ms-2" id="save-sales-button">
                    <i class="ti ti-device-floppy me-1"></i> {{ __('messages.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</div>
