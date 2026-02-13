<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row row-deck row-cards">
            <div class="col-md-12">
                <div class="card card-primary">
                    @include('admin.layouts.partials.sales.index.store-info')
                    @include('admin.layouts.partials.sales.index.bulk-actions')
                    @include('admin.layouts.partials.sales.index.sales-table')
                    @include('admin.layouts.partials.sales.index.pagination')
                </div>
            </div>
        </div>
    </div>
</div>
