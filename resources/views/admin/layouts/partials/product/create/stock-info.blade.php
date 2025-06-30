<div class="mb-4 pb-2">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Stock Quantity</label>
            <input type="number" class="form-control" name="stock_quantity" placeholder="0">
        </div>
        <div class="col-md-4">
            <label class="form-label">Low Stock Threshold</label>
            <input type="number" class="form-control" name="low_stock_threshold" placeholder="Default (10)"
                min="1">
            <small class="form-text text-muted">Leave empty to use system default</small>
        </div>
        <div class="col-md-4">
            <label class="form-label">Unit</label>
            <select class="form-select" name="units_id">
                <option value="">Select Unit</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}">
                        {{ $unit->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-12">
            <label class="form-label">Warehouse</label>
            <select name="warehouse_id" class="form-select" id="warehouse_id">
                <option value="">Select Warehouse</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">
                        {{ $warehouse->name }}
                        {{ $warehouse->is_main ? '(Main)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>
