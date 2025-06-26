<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="ti ti-report-money"></i> Sales Information</h3>
    </div>
    <div class="card-body">
        @include('admin.layouts.partials.sales.create.basic-info')
        @include('admin.layouts.partials.sales.create.product-selection')
        @include('admin.layouts.partials.sales.create.add-product-actions')
        <input type="hidden" name="products" id="productsField">
        <input type="hidden" name="tax_rate" id="taxRateInput" value="{{ $tax->rate ?? 0 }}">
    </div>
</div>
