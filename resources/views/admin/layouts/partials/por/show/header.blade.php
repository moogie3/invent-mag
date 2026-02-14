<div class="page-header">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.purchasing') }}</div>
                <h2 class="page-title fw-bold">{{ __('messages.purchase_return_details') }}</h2>
            </div>
            <div class="col-auto ms-auto">
                <div class="btn-list">
                    <a href="{{ route('admin.por.index') }}" class="btn btn-outline-primary d-none d-sm-inline-block">
                        <i class="ti ti-arrow-left me-1"></i> {{ __('messages.back_to_returns') }}
                    </a>
                    <a href="{{ route('admin.por.edit', $por->id) }}" class="btn btn-info">
                        <i class="ti ti-edit me-1"></i> {{ __('messages.edit') }}
                    </a>
                    <a href="{{ route('admin.por.print', $por->id) }}" target="_blank" class="btn btn-primary">
                        <i class="ti ti-printer me-1"></i> {{ __('messages.print') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
