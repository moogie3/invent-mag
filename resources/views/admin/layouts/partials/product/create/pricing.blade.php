<div class="mb-4 pb-2">
    <h4 class="fw-semibold mb-3 text-secondary d-flex align-items-center">
        <i class="ti ti-cash me-2 text-muted"></i> {{ __('messages.pricing_information') }}
    </h4>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="price">{{ __('messages.buying_price') }}</label>
            <div class="input-group">
                <span class="input-group-text"><i class="ti ti-currency"></i></span>
                <input type="number" step="0" class="form-control @error('price') is-invalid @enderror" name="price" id="price" placeholder="0" value="{{ old('price') }}">
            </div>
            @error('price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="selling_price">{{ __('messages.product_selling_price') }}</label>
            <div class="input-group">
                <span class="input-group-text"><i class="ti ti-currency"></i></span>
                <input type="number" step="0" class="form-control @error('selling_price') is-invalid @enderror" name="selling_price" id="selling_price" placeholder="0" value="{{ old('selling_price') }}">
            </div>
            @error('selling_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
