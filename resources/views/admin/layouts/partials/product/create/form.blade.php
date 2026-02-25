<form id="product-create-form" enctype="multipart/form-data" method="POST" action="{{ route('admin.product.store') }}">
    @csrf
    @include('admin.layouts.partials.product.create.basic-info')
    @include('admin.layouts.partials.product.create.category-supplier')
    @include('admin.layouts.partials.product.create.stock-info')
    @include('admin.layouts.partials.product.create.pricing')
    @include('admin.layouts.partials.product.create.additional-details')
    @include('admin.layouts.partials.product.create.form-footer')
</form>
