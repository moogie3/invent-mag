<div class="page-header no-print">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    {{ __('messages.overview') }}
                </div>
                <h2 class="page-title d-flex align-items-center">
                    <i class="ti ti-box me-2"></i> {{ __('messages.product_title') }}
                    <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                        <i class="ti ti-help-circle fs-3"></i>
                    </a>
                </h2>
            </div>
            <div class="col-auto ms-auto d-flex gap-2 align-items-center">
                <form action="{{ route('admin.product') }}" method="GET" class="d-inline-flex gap-2">
                    <select name="warehouse_id" class="form-select" style="min-width: 200px;">
                        <option value="">{{ __('messages.all_warehouses') }}</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-secondary">
                        <i class="ti ti-filter"></i>
                    </button>
                </form>
                <div class="dropdown d-inline-block">
                    <button class="btn btn-secondary dropdown-toggle" type="button"
                        id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-download me-2"></i> {{ __('messages.export') }}
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                        <li>
                            <a class="dropdown-item" href="#" data-onclick="exportAllProducts('pdf'); return false;">
                                Export as PDF
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" data-onclick="exportAllProducts('csv'); return false;">
                                Export as CSV
                            </a>
                        </li>
                    </ul>
                </div>
                @can('import-products')
                    @planHas('shopee_import')
                        <button class="btn btn-shopee d-none d-sm-inline-block" type="button"
                            data-bs-toggle="modal" data-bs-target="#shopeeImportModal">
                            <i class="ti ti-file-import me-2"></i> {{ __('messages.import_shopee') }}
                        </button>
                    @endplanHas
                @endcan
                <a href="{{ route('admin.product.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                    <i class="ti ti-plus fs-4"></i>
                    {{ __('messages.create_new_product') }}
                </a>
            </div>
        </div>
    </div>
</div>
