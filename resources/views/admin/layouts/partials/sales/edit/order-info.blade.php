<div class="h-100">
    <h5 class="card-title mb-3 text-secondary">
        <i class="ti ti-calendar-event me-2 text-muted"></i>{{ __('messages.order_information') }}
    </h5>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="mb-2">
                <label class="form-label fw-bold">{{ __('messages.sales_invoice') }}</label>
                <input type="text" class="form-control" name="invoice" value="{{ $sales->invoice }}" required>
            </div>
            <div class="mb-2">
                <label class="form-label fw-bold">{{ __('messages.order_date') }}</label>
                <div class="form-control-plaintext">{{ $sales->order_date->format('d F Y') }}</div>
                <input type="hidden" name="order_date" value="{{ $sales->order_date }}">
            </div>
            <div class="mb-2">
                <label class="form-label fw-bold">{{ __('messages.due_date') }}</label>
                <div class="form-control-plaintext">{{ $sales->due_date->format('d F Y') }}</div>
                <input type="hidden" name="due_date" value="{{ optional($sales->due_date)->format('Y-m-d') }}">
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-2">
                <label class="form-label fw-bold">{{ __('messages.warehouse') }}</label>
                <select class="form-select" name="warehouse_id" id="warehouse_id">
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}"
                            {{ $sales->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label fw-bold">{{ __('messages.po_payment_type') }}</label>
                <select class="form-select" name="payment_type" id="payment_type"
                    {{ $sales->status == __('messages.paid') ? 'disabled' : '' }}>
                    <option value="Cash" {{ $sales->payment_type == 'Cash' ? 'selected' : '' }}>
                        {{ __('messages.payment_method_cash') }}
                    </option>
                    <option value="Transfer" {{ $sales->payment_type == 'Transfer' ? 'selected' : '' }}>
                        {{ __('messages.payment_method_transfer') }}
                    </option>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label fw-bold">{{ __('messages.payment_status') }}</label>
                <select class="form-select" name="status" id="status">
                    <option value="Paid" {{ $sales->status == __('messages.paid') ? 'selected' : '' }}>
                        {{ __('messages.paid') }}
                    </option>
                    <option value="Unpaid" {{ $sales->status != __('messages.paid') ? 'selected' : '' }}>
                        {{ __('messages.unpaid') }}
                    </option>
                </select>
            </div>
            @if ($sales->status === 'Paid' && isset($sales->payment_date))
                <div class="d-flex justify-content-between mt-2">
                    <div><strong>{{ __('messages.payment_date') }}</strong></div>
                    <div>
                        {{ $sales->payment_date->format('d F Y H:i') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
