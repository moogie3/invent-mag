<div class="page-header no-print">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.overview') }}</div>
                <h2 class="page-title no-print">{{ __('messages.edit_po_invoice') }}</h2>
            </div>
            <div class="col text-end">
                <button type="submit" form="edit-po-form" class="btn btn-success" id="save-po-button">
                    <i class="ti ti-device-floppy me-1"></i> {{ __('messages.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</div>
