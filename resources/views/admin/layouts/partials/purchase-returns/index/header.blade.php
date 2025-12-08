<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    {{ __('messages.model_purchase_return') }}
                </h2>
                <div class="text-muted mt-1">
                    {{ __('messages.purchase_returns_overview') }}
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="{{ route('admin.purchase-returns.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                        <i class="ti ti-plus me-2"></i>
                        {{ __('messages.new_purchase_return') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
