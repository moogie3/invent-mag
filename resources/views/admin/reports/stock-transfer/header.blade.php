<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    {{ __('messages.reports') }}
                </div>
                <h2 class="page-title">
                    <i class="ti ti-arrows-transfer me-2"></i> {{ __('messages.stock_transfer') }}
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="{{ route('admin.reports.adjustment-log') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-list me-2"></i>
                        {{ __('messages.view_adjustment_log') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
