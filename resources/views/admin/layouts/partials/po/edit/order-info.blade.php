<div class="card bg-light border-0 h-100">
    <div class="card-body p-3">
        <h4 class="card-title mb-3">
            <i class="ti ti-calendar-event me-2 text-primary"></i>{{ __('messages.po_order_information_title') }}
        </h4>
        <div class="d-flex justify-content-between mb-2">
            <div><strong>{{ __('messages.po_invoice') }}</strong></div>
            <div>
                <input type="text" class="form-control form-control-sm" name="invoice" value="{{ $pos->invoice }}" required>
            </div>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <div><strong>{{ __('messages.po_order_date') }}</strong></div>
            <div>{{ $pos->order_date->format('d F Y') }}</div>
            <input type="hidden" name="order_date" value="{{ $pos->order_date }}">
        </div>
        <div class="d-flex justify-content-between mb-2">
            <div><strong>{{ __('messages.po_due_date') }}</strong></div>
            <div>{{ $pos->due_date->format('d F Y') }}</div>
            <input type="hidden" name="due_date" value="{{ $pos->due_date }}">
        </div>

        <div class="d-flex justify-content-between mb-2">
            <div><strong>{{ __('messages.warehouse') }}</strong></div>
            <div>
                <select class="form-select form-select-sm" name="warehouse_id" id="warehouse_id">
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}"
                            {{ $pos->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-between mb-2">
            <div><strong>{{ __('messages.po_payment_type') }}</strong></div>
            <div>
                <select class="form-select form-select-sm" name="payment_type" id="payment_type"
                    {{ $pos->status == __('messages.paid') ? 'disabled' : '' }}>
                    <option value="Cash" {{ $pos->payment_type == 'Cash' ? 'selected' : '' }}>
                        {{ __('messages.payment_method_cash') }}
                    </option>
                    <option value="Transfer" {{ $pos->payment_type == 'Transfer' ? 'selected' : '' }}>
                        {{ __('messages.payment_method_transfer') }}
                    </option>
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <div><strong>{{ __('messages.payment_status') }}</strong></div>
            <div>
                <select class="form-select form-select-sm" name="status" id="status">
                    <option value="Paid" {{ $pos->status == __('messages.paid') ? 'selected' : '' }}>
                        {{ __('messages.paid') }}
                    </option>
                    <option value="Unpaid" {{ $pos->status != __('messages.paid') ? 'selected' : '' }}>
                        {{ __('messages.unpaid') }}
                    </option>
                </select>
            </div>
        </div>

        @if ($pos->status === __('messages.paid') && isset($pos->payment_date))
            <div class="d-flex justify-content-between mt-2">
                <div><strong>{{ __('messages.po_payment_date') }}</strong></div>
                <div>
                    {{ $pos->payment_date->format('d F Y H:i') }}
                </div>
            </div>
        @endif
    </div>
</div>
