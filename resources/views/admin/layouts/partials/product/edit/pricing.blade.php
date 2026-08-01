<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="price">{{ __('messages.buying_price') }}</label>
        <input type="text" name="price" id="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', intval($products->price)) }}">
        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="selling_price">{{ __('messages.product_selling_price') }}</label>
        <input type="text" name="selling_price" id="selling_price" class="form-control @error('selling_price') is-invalid @enderror" value="{{ old('selling_price', intval($products->selling_price)) }}">
        @error('selling_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
