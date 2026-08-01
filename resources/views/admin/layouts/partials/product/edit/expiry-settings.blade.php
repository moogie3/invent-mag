<div class="row g-3">
    <div class="col-md-6">
        <div class="form-check form-switch mt-3">
            <input class="form-check-input @error('has_expiry') is-invalid @enderror" type="checkbox" id="has_expiry" name="has_expiry" value="1"
                {{ old('has_expiry', $products->has_expiry) ? 'checked' : '' }}>
            <label class="form-check-label" for="has_expiry">{{ __('messages.pos_product_has_expiry') }}</label>
            @error('has_expiry')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-md-6 expiry-date-field" style="{{ $products->has_expiry ? '' : 'display: none;' }}">

    </div>
</div>
