<div class="mb-4 pb-2">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">{{ __('messages.product_low_stock_threshold') }}</label>
            <input type="number" class="form-control" name="low_stock_threshold" placeholder="{{ __('messages.pos_default_10') }}"
                min="1">
            <small class="form-text text-muted">{{ __('messages.leave_empty_system_default') }}</small>
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('messages.unit') }}</label>
            <select class="form-select" name="units_id">
                <option value="">{{ __('messages.select_unit') }}</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}">
                        {{ $unit->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('messages.warehouse') }}</label>
            <select name="warehouse_id" class="form-select" id="warehouse_id">
                <option value="">{{ __('messages.select_warehouse') }}</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">
                        {{ $warehouse->name }}
                        {{ $warehouse->is_main ? __('messages.table_main') : '' }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>
