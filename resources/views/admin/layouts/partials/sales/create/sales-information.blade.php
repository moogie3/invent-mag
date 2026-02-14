<div class="mb-5 border-bottom pb-4">
    <h4 class="card-title mb-4">
        <i class="ti ti-report-money me-2 text-primary"></i> {{ __('messages.sales_information_title') }}
    </h4>
    @include('admin.layouts.partials.sales.create.basic-info')
    @include('admin.layouts.partials.sales.create.product-selection')
    @include('admin.layouts.partials.sales.create.add-product-actions')
    <input type="hidden" name="products" id="productsField">
    <input type="hidden" name="tax_rate" id="taxRateInput" value="{{ $tax->rate ?? 0 }}">
</div>
