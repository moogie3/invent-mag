<div class="form-group">
    <label class="form-label">{{ __('warehouse') }}</label>
    <select name="warehouse_id" class="form-select" id="edit_warehouse_id">
        <option value="">{{ __('Select Warehouse') }}</option>
        @foreach ($warehouses as $warehouse)
            <option value="{{ $warehouse->id }}"
                {{ isset($products) && $products->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                {{ $warehouse->name }} {{ $warehouse->is_main ? __('(Main)') : '' }}
            </option>
        @endforeach
    </select>
</div>
