<div class="page-header no-print">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.overview') }}</div>
                <h2 class="page-title no-print fw-bold">{{ __('messages.sales_return_details') }}</h2>
            </div>
            <div class="col text-end">
                <a href="{{ route('admin.sales-returns.index') }}" class="btn btn-outline-primary d-none d-sm-inline-block me-2">
                    <i class="ti ti-arrow-left me-1"></i> {{ __('messages.back_to_returns') }}
                </a>
                <a href="{{ route('admin.sales-returns.edit', $salesReturn->id) }}" class="btn btn-info me-2">
                    <i class="ti ti-edit me-1"></i> {{ __('messages.edit') }}
                </a>
                <a href="{{ route('admin.sales-returns.print', $salesReturn->id) }}" target="_blank" class="btn btn-primary">
                    <i class="ti ti-printer me-1"></i> {{ __('messages.print') }}
                </a>
            </div>
        </div>
    </div>
</div>
