<form enctype="multipart/form-data" method="POST" action="{{ route('admin.pos.store') }}" id="invoiceForm">
    @csrf
    <input type="hidden" name="products" id="productsField">
    <input type="hidden" id="taxRateInput" name="tax_rate" value="0">
    <input type="hidden" name="invoice" value="auto-generated">

    @include('admin.layouts.partials.pos.index.transaction-info')

    <div class="row">
        @include('admin.layouts.partials.pos.index.product-catalog')
        @include('admin.layouts.partials.pos.index.shopping-cart')
    </div>
</form>
