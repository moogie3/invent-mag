<div class="page-header">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.purchasing') }}</div>
                <h2 class="page-title fw-bold d-flex align-items-center">
                    <i class="ti ti-plus me-2"></i>{{ __('messages.create_purchase_order') }}
                    <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                        <i class="ti ti-help-circle fs-3"></i>
                    </a>
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <div class="btn-list">
                    <a href="{{ route('admin.po') }}" class="btn btn-outline-primary">
                        <i class="ti ti-arrow-left me-1"></i> {{ __('messages.back_to_po_list') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
