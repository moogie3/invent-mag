<div class="h-100">
    <h5 class="card-title mb-3 text-secondary">
        <i class="ti ti-calendar-event me-2 text-muted"></i>{{ __('messages.po_order_information_title') }}
    </h5>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('messages.po_invoice') }}</label>
                <input type="text" class="form-control" name="invoice" value="{{ $pos->invoice }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('messages.po_order_date') }}</label>
                <div class="form-control-plaintext">{{ $pos->order_date->format('d F Y') }}</div>
                <input type="hidden" name="order_date" value="{{ $pos->order_date }}">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('messages.po_due_date') }}</label>
                <div class="form-control-plaintext">{{ $pos->due_date->format('d F Y') }}</div>
                <input type="hidden" name="due_date" value="{{ $pos->due_date }}">
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('messages.warehouse') }}</label>
                <select class="form-select" name="warehouse_id" id="warehouse_id">
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}"
                            {{ $pos->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('messages.po_payment_type') }}</label>
                <select class="form-select" name="payment_type" id="payment_type"
                    {{ $pos->status == __('messages.paid') ? 'disabled' : '' }}>
                    <option value="Cash" {{ $pos->payment_type == 'Cash' ? 'selected' : '' }}>
                        {{ __('messages.payment_method_cash') }}
                    </option>
                    <option value="Transfer" {{ $pos->payment_type == 'Transfer' ? 'selected' : '' }}>
                        {{ __('messages.payment_method_transfer') }}
                    </option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('messages.payment_status') }}</label>
                <select class="form-select" name="status" id="status">
                    <option value="Paid" {{ $pos->status == __('messages.paid') ? 'selected' : '' }}>
                        {{ __('messages.paid') }}
                    </option>
                    <option value="Unpaid" {{ $pos->status != __('messages.paid') ? 'selected' : '' }}>
                        {{ __('messages.unpaid') }}
                    </option>
                </select>
            </div>
        </div>
    </div>
</div>
