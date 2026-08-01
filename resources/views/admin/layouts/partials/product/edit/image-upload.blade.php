<div class="row g-3 align-items-end">
    <div class="col-md-6">
        <label class="form-label">{{ __('messages.current_image') }}</label>
        <div class="mb-2">
            @if ($products->image == asset('img/default_placeholder.png'))
                <i class="ti ti-photo fs-1"
                    style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; border-radius: 5px; margin: 0 auto;"></i>
            @else
                <img src="{{ asset($products->image) }}" alt="{{ __('messages.pos_product_image') }}" class="rounded border shadow-sm" width="200">
            @endif
        </div>
        <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror">
        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">{{ __('messages.upload_new_image_replace_current') }}</small>
    </div>
</div>
