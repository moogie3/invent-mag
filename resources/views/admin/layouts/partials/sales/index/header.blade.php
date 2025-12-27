<div class="page-header no-print">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">{{ __('messages.overview') }}</div>
                <h2 class="page-title"><i class="ti ti-report-money me-2"></i>{{ __('messages.model_sales') }}</h2>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('admin.sales.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                    <i class="ti ti-plus fs-4"></i> {{ __('messages.create_sales_order') }}
                </a>
            </div>
        </div>
    </div>
</div>
