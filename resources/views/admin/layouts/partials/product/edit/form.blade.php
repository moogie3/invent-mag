<form id="product-edit-form" method="POST" action="{{ route('admin.product.update', $products->id) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card border-0 shadow-sm rounded-3">
        @include('admin.layouts.partials.product.edit.card-header')
        @include('admin.layouts.partials.product.edit.card-body')
        @include('admin.layouts.partials.product.edit.card-footer')
    </div>
</form>
