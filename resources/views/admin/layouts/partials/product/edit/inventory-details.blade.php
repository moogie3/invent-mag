<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label" for="category_id">{{ __('messages.category') }}</label>
        <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror">
            <option value="{{ $products->category_id }}">{{ $products->category->name }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" {{ old('category_id', $products->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="units_id">{{ __('messages.unit') }}</label>
        <select name="units_id" id="units_id" class="form-select @error('units_id') is-invalid @enderror">
            <option value="{{ $products->units_id }}">{{ $products->unit->name }}</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" {{ old('units_id', $products->units_id) == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
            @endforeach
        </select>
        @error('units_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">{{ __('messages.product_stock_quantity') }} <i class="ti ti-info-circle text-muted" data-bs-toggle="tooltip" title="Total stock across all warehouses. Use Adjust Stock to change."></i></label>
        <input type="text" class="form-control" value="{{ $products->total_stock }}" readonly disabled>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="low_stock_threshold">{{ __('messages.product_low_stock_threshold') }}</label>
        <input type="number" class="form-control @error('low_stock_threshold') is-invalid @enderror" name="low_stock_threshold" id="low_stock_threshold"
            value="{{ old('low_stock_threshold', $products->low_stock_threshold) }}" placeholder="{{ __('messages.pos_default_10') }}" min="1">
        @error('low_stock_threshold')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="form-text text-muted">{{ __('messages.leave_empty_system_default_10') }}</small>
    </div>
</div>
