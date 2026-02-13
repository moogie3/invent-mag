<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row row-deck row-cards">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    @include('admin.layouts.partials.sales.view.invoice-card')
                </div>
            </div>
        </div>
    </div>
</div>
