<div class="page-header no-print">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.sales') }}</div>
                <h2 class="page-title fw-bold no-print">{{ __('messages.edit_sales_return') }}</h2>
            </div>
            <div class="col-auto ms-auto">
                <div class="btn-list">
                    <a href="{{ route('admin.sales-returns.index') }}" class="btn btn-outline-primary d-none d-sm-inline-block">
                        <i class="ti ti-arrow-left me-1"></i> {{ __('messages.back_to_returns') }}
                    </a>
                    <button type="submit" form="sales-return-edit-form" class="btn btn-success" id="save-sales-return-button">
                        <i class="ti ti-device-floppy me-1"></i> {{ __('messages.save_changes') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
