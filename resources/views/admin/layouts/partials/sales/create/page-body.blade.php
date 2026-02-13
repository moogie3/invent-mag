<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <form enctype="multipart/form-data" method="POST" action="{{ route('admin.sales.store') }}" id="invoiceForm">
            @csrf
            @include('admin.layouts.partials.sales.create.sales-information')
            @include('admin.layouts.partials.sales.create.order-items')
            @include('admin.layouts.partials.sales.create.order-summary')
            @include('admin.layouts.partials.sales.create.form-actions')
        </form>
    </div>
</div>
