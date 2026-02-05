<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">{{ __('messages.category') }}</label>
        <select name="category_id" class="form-select">
            <option value="{{ $products->category_id }}">{{ $products->category->name }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">{{ __('messages.unit') }}</label>
        <select name="units_id" class="form-select">
            <option value="{{ $products->units_id }}">{{ $products->unit->name }}</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">{{ __('messages.product_stock_quantity') }} <i class="ti ti-info-circle text-muted" data-bs-toggle="tooltip" title="Total stock across all warehouses. Use Adjust Stock to change."></i></label>
        <input type="text" class="form-control" value="{{ $products->total_stock }}" readonly disabled>
    </div>

    <div class="col-md-3">
        <label class="form-label">{{ __('messages.product_low_stock_threshold') }}</label>
        <input type="number" class="form-control" name="low_stock_threshold"
            value="{{ $products->low_stock_threshold }}" placeholder="{{ __('messages.pos_default_10') }}" min="1">
        <small class="form-text text-muted">{{ __('messages.leave_empty_system_default_10') }}</small>
    </div>
</div>
