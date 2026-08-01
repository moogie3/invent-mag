<div class="mb-4 pb-2">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="units_id">{{ __('messages.unit') }}</label>
            <select class="form-select @error('units_id') is-invalid @enderror" name="units_id" id="units_id">
                <option value="">{{ __('messages.select_unit') }}</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" {{ old('units_id') == $unit->id ? 'selected' : '' }}>
                        {{ $unit->name }}
                    </option>
                @endforeach
            </select>
            @error('units_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="low_stock_threshold">{{ __('messages.product_low_stock_threshold') }}</label>
            <input type="number" class="form-control @error('low_stock_threshold') is-invalid @enderror" name="low_stock_threshold" id="low_stock_threshold" placeholder="{{ __('messages.pos_default_10') }}"
                min="1" value="{{ old('low_stock_threshold') }}">
            @error('low_stock_threshold')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <small class="form-text text-muted">{{ __('messages.leave_empty_system_default') }}</small>
        </div>
    </div>
</div>
