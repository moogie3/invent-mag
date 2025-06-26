<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="ti ti-shopping-cart"></i> Purchase Order Information</h3>
    </div>
    <div class="card-body">
        @include('admin.layouts.partials.po.create.basic-info')
        @include('admin.layouts.partials.po.create.product-selection')
        @include('admin.layouts.partials.po.create.add-product-actions')
        <input type="hidden" name="products" id="productsField">
    </div>
</div>
