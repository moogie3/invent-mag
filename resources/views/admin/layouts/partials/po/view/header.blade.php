<div class="page-header no-print">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.overview') }}</div>
                <h2 class="page-title no-print">{{ __('messages.view_po_invoice') }}</h2>
            </div>
            <div class="col text-end">
                <a href="{{ route('admin.po.print', $pos->id) }}" target="_blank" class="btn btn-secondary me-2">
                    <i class="ti ti-printer me-1"></i> {{ __('messages.print_invoice') }}
                </a>
                <a href="{{ route('admin.po.edit', $pos->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> {{ __('messages.edit_invoice') }}
                </a>
            </div>
        </div>
    </div>
</div>
