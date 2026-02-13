<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row row-deck row-cards">
            <div class="col-md-12">
                <div class="card card-primary">
                    @include('admin.layouts.partials.warehouse.index.info-section')
                    @include('admin.layouts.partials.warehouse.index.table')
                    @include('admin.layouts.partials.warehouse.index.pagination')
                </div>
            </div>
        </div>
    </div>
</div>
