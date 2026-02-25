<form id="product-edit-form" method="POST" action="{{ route('admin.product.update', $products->id) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card">
        @include('admin.layouts.partials.product.edit.card-header')
        @include('admin.layouts.partials.product.edit.card-body')
        @include('admin.layouts.partials.product.edit.card-footer')
    </div>
</form>
