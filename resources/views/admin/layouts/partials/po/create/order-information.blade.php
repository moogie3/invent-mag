<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white border-bottom">
        <h3 class="card-title"><i class="ti ti-shopping-cart"></i> {{ __('messages.po_order_information_title') }}</h3>
    </div>
    <div class="card-body">
        @include('admin.layouts.partials.po.create.basic-info')
        @include('admin.layouts.partials.po.create.product-selection')
        @include('admin.layouts.partials.po.create.add-product-actions')
        <input type="hidden" name="products" id="productsField">
    </div>
</div>
