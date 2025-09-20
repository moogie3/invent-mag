<div class="form-group">
    <label class="form-label">{{ __('messages.warehouse') }}</label>
    <select name="warehouse_id" class="form-select" id="edit_warehouse_id">
        <option value="">{{ __('messages.select_warehouse') }}</option>
        @foreach ($warehouses as $warehouse)
            <option value="{{ $warehouse->id }}"
                {{ isset($products) && $products->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                {{ $warehouse->name }} {{ $warehouse->is_main ? __('messages.table_main') : '' }}
            </option>
        @endforeach
    </select>
</div>
