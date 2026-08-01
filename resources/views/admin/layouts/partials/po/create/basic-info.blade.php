<div class="row g-3 mb-4">
    <div class="col-md-2">
        <label class="form-label fw-bold" for="invoice">{{ __('messages.table_invoice') }}</label>
        <input type="text" class="form-control @error('invoice') is-invalid @enderror" name="invoice" id="invoice" placeholder="{{ __('messages.invoice_number') }}" value="{{ old('invoice') }}" />
        @error('invoice')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold" for="supplier_id">{{ __('messages.supplier') }}</label>
        <select class="form-select @error('supplier_id') is-invalid @enderror" name="supplier_id" id="supplier_id" required>
            <option value="">{{ __('messages.select_supplier') }}</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" data-payment-terms="{{ $supplier->payment_terms }}"
                    {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                    {{ $supplier->name }}
                </option>
            @endforeach
        </select>
        @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold" for="warehouse_id">{{ __('messages.warehouse') }}</label>
        <select class="form-select @error('warehouse_id') is-invalid @enderror" name="warehouse_id" id="warehouse_id" required>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}"
                    {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                    {{ $warehouse->name }} {{ $warehouse->is_main ? __('messages.table_main') : '' }}
                </option>
            @endforeach
        </select>
        @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold" for="order_date">{{ __('messages.po_order_date') }}</label>
        <input type="date" class="form-control @error('order_date') is-invalid @enderror" name="order_date" id="order_date" value="{{ old('order_date') }}" required />
        @error('order_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold" for="due_date">{{ __('messages.po_due_date') }}</label>
        <input type="text" class="form-control" name="due_date" id="due_date" placeholder="{{ __('messages.autofill') }}" readonly />
    </div>
</div>
