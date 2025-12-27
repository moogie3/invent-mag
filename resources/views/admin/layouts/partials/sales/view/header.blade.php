<div class="page-header no-print">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.overview') }}</div>
                <h2 class="page-title no-print">{{ __('messages.view_sales_invoice') }}</h2>
            </div>
            <div class="col text-end">
                <button type="button" class="btn btn-secondary me-2" onclick="javascript:window.print();">
                    <i class="ti ti-printer me-1"></i> {{ __('messages.print_invoice') }}
                </button>
                <a href="{{ route('admin.sales.edit', $sales->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> {{ __('messages.edit_invoice') }}
                </a>
            </div>
        </div>
    </div>
</div>
