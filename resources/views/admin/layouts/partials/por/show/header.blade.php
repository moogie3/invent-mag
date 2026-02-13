<div class="page-header d-print-none">
    <div class="{{ $containerClass ?? "container-xl" }}">
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
                <a href="{{ route('admin.por.edit', $por->id) }}" class="btn btn-info">
                    <i class="ti ti-edit me-2"></i>
                    {{ __('messages.edit') }}
                </a>
                <a href="{{ route('admin.por.print', $por->id) }}" target="_blank" class="btn btn-primary">
                    <i class="ti ti-printer me-2"></i>
                    {{ __('messages.print') }}
                </a>
            </div>
        </div>
    </div>
</div>
