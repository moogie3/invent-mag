<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body">
                        <form enctype="multipart/form-data" method="POST" action="{{ route('admin.po.store') }}" id="invoiceForm">
                            @csrf
                            @include('admin.layouts.partials.po.create.order-information')
                            @include('admin.layouts.partials.po.create.order-items')
                            @include('admin.layouts.partials.po.create.order-summary')
                            @include('admin.layouts.partials.po.create.form-actions')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
