<div class="row g-3 align-items-end">
    <div class="col-md-6">
        <label class="form-label">{{ __('Current Image') }}</label>
        <div class="mb-2">
            @if ($products->image == asset('img/default_placeholder.png'))
                <i class="ti ti-photo fs-1"
                    style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; border-radius: 5px; margin: 0 auto;"></i>
            @else
                <img src="{{ asset($products->image) }}" alt="{{ __('Product Image') }}" class="rounded border shadow-sm" width="200">
            @endif
        </div>
        <input type="file" name="image" class="form-control">
        <small class="text-muted">{{ __('Upload a new image to replace the current one.') }}</small>
    </div>
</div>
