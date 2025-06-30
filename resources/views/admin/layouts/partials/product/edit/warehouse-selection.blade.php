<div class="form-group">
    <label class="form-label">Warehouse</label>
    <select name="warehouse_id" class="form-select" id="edit_warehouse_id">
        <option value="">Select Warehouse</option>
        @foreach ($warehouses as $warehouse)
            <option value="{{ $warehouse->id }}"
                {{ isset($products) && $products->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                {{ $warehouse->name }} {{ $warehouse->is_main ? '(Main)' : '' }}
            </option>
        @endforeach
    </select>
</div>
