<div class="page-header no-print">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    {{ __('messages.overview') }}
                </div>
                <h2 class="page-title">
                    <i class="ti ti-box me-2"></i> {{ __('messages.product_title') }}
                </h2>
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
                <div class="dropdown d-inline-block">
                    <button class="btn btn-secondary dropdown-toggle" type="button"
                        id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-download me-2"></i> {{ __('messages.export') }}
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                        <li>
                            <a class="dropdown-item" href="#" onclick="exportAllProducts('pdf'); return false;">
                                Export as PDF
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="exportAllProducts('csv'); return false;">
                                Export as CSV
                            </a>
                        </li>
                    </ul>
                </div>
                <a href="{{ route('admin.product.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                    <i class="ti ti-plus fs-4"></i>
                    {{ __('messages.create_new_product') }}
                </a>
            </div>
        </div>
    </div>
</div>
