<div class="mb-3">
    <label class="form-label" for="description">{{ __('messages.description') }}</label>
    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $products->description) }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
