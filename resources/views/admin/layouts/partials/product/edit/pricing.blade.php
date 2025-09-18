<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('Buying Price') }}</label>
        <input type="text" name="price" class="form-control" value="{{ intval($products->price) }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('Selling Price') }}</label>
        <input type="text" name="selling_price" class="form-control" value="{{ intval($products->selling_price) }}">
    </div>
</div>
