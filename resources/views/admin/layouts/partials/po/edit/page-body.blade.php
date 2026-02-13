<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row row-deck row-cards">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    @include('admin.layouts.partials.po.edit.card-header')
                    @include('admin.layouts.partials.po.edit.form-content')
                </div>
            </div>
        </div>
    </div>
</div>
