<div class="page-body">
    <div class="container-xl">
        <form enctype="multipart/form-data" method="POST" action="{{ route('admin.po.store') }}" id="invoiceForm">
            @csrf
            @include('admin.layouts.partials.po.create.order-information')
            @include('admin.layouts.partials.po.create.order-items')
            @include('admin.layouts.partials.po.create.order-summary')
            @include('admin.layouts.partials.po.create.form-actions')
        </form>
    </div>
</div>
