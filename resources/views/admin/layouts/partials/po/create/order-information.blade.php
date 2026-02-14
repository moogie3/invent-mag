<div class="mb-5 border-bottom pb-4">
    <h4 class="card-title mb-4">
        <i class="ti ti-shopping-cart me-2 text-primary"></i> {{ __('messages.po_order_information_title') }}
    </h4>
    @include('admin.layouts.partials.po.create.basic-info')
    @include('admin.layouts.partials.po.create.product-selection')
    @include('admin.layouts.partials.po.create.add-product-actions')
    <input type="hidden" name="products" id="productsField">
</div>
