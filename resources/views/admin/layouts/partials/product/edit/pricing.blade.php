<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('messages.buying_price') }}</label>
        <input type="text" name="price" class="form-control" value="{{ intval($products->price) }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('messages.product_selling_price') }}</label>
        <input type="text" name="selling_price" class="form-control" value="{{ intval($products->selling_price) }}">
    </div>
</div>
