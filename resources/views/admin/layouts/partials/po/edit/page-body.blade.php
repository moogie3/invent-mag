<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row row-deck row-cards">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm rounded-3">
                    @include('admin.layouts.partials.po.edit.card-header')
                    @include('admin.layouts.partials.po.edit.form-content')
                </div>
            </div>
        </div>
    </div>
</div>
