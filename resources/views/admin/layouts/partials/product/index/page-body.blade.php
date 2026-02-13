<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row row-deck row-cards">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm rounded-3">
                    @include('admin.layouts.partials.product.index.info-header')
                    @include('admin.layouts.partials.product.index.bulk-actions')
                    @include('admin.layouts.partials.product.index.table')
                    @include('admin.layouts.partials.product.index.pagination')
                </div>
            </div>
        </div>
    </div>
</div>
