<div class="row g-3 mb-4">
    <div class="col-md-2">
        <label class="form-label fw-bold">{{ __('messages.table_invoice') }}</label>
        <input type="text" class="form-control" name="invoice" id="invoice" placeholder="{{ __('messages.invoice_number') }}" />
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">{{ __('messages.supplier') }}</label>
        <select class="form-select" name="supplier_id" id="supplier_id">
            <option value="">{{ __('messages.select_supplier') }}</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" data-payment-terms="{{ $supplier->payment_terms }}">
                    {{ $supplier->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">{{ __('messages.po_order_date') }}</label>
        <input type="date" class="form-control" name="order_date" id="order_date" />
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">{{ __('messages.po_due_date') }}</label>
        <input type="text" class="form-control" name="due_date" id="due_date" placeholder="{{ __('messages.autofill') }}" readonly />
    </div>
</div>
