<div class="page-header no-print">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    {{ __('Overview') }}
                </div>
                <h2 class="page-title">
                    <i class="ti ti-box me-2"></i> {{ __('Product') }}
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-secondary d-none d-sm-inline-block"
                    onclick="javascript:window.print();">
                    <i class="ti ti-printer fs-4"></i>
                    {{ __('Export PDF') }}
                </button>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('admin.product.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                    <i class="ti ti-plus fs-4"></i>
                    {{ __('Create Product') }}
                </a>
            </div>
        </div>
    </div>
</div>
