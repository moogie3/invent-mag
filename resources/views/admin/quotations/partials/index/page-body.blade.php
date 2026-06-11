<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row row-deck row-cards">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm rounded-3">
                    @include('admin.quotations.partials.index.store-info')
                    @include('admin.quotations.partials.index.quotation-table')
                    @include('admin.quotations.partials.index.pagination')
                </div>
            </div>
        </div>
    </div>
</div>
