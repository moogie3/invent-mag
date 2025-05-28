<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title d-flex align-items-center">
                    <i class="ti ti-dashboard fs-1 me-2"></i> Dashboard
                </h2>
                <div class="text-muted mt-1">Business overview and performance metrics</div>
            </div>
            <div class="col-auto ms-auto">
                <div class="btn-list">
                    <a href="#" class="btn btn-white" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="ti ti-filter me-2"></i> Filter
                    </a>
                    @include('admin.partials.dashboard.export-dropdown')
                    <a href="{{ route('admin.sales.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i> New Sale
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
