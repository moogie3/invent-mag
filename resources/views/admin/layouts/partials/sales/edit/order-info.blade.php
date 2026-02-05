<div class="card border-0 h-100">
    <div class="card-body p-3">
        <h4 class="card-title mb-3">
            <i class="ti ti-calendar-event me-2 text-primary"></i>{{ __('messages.order_information') }}
        </h4>
        <div class="d-flex justify-content-between mb-2">
            <div><strong>{{ __('messages.order_date') }}:</strong></div>
            <div>{{ $sales->order_date->format('d F Y') }}</div>
            <input type="hidden" name="order_date" value="{{ optional($sales->order_date)->format('Y-m-d') }}">
        </div>
        <div class="d-flex justify-content-between mb-2">
            <div><strong>{{ __('messages.due_date') }}:</strong></div>
            <div>{{ $sales->due_date->format('d F Y') }}</div>
            <input type="hidden" name="due_date" value="{{ optional($sales->due_date)->format('Y-m-d') }}">
        </div>

        <div class="d-flex justify-content-between mb-2">
            <div><strong>{{ __('messages.warehouse') }}:</strong></div>
            <div>
                <select class="form-select form-select-sm" name="warehouse_id" id="warehouse_id">
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}"
                            {{ $sales->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-between mb-2">
            <div><strong>{{ __('messages.payment_type') }}:</strong></div>
            <div>
                <select class="form-select form-select-sm" name="payment_type" id="payment_type"
                    {{ $sales->status == 'Paid' ? 'disabled' : '' }}>
                    <option value="Cash" {{ $sales->payment_type == 'Cash' ? 'selected' : '' }}>
                        {{ __('messages.pos_payment_method_cash') }}
                    </option>
                    <option value="Transfer" {{ $sales->payment_type == 'Transfer' ? 'selected' : '' }}>
                        {{ __('messages.pos_payment_method_transfer') }}
                    </option>
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <div><strong>{{ __('messages.payment_status') }}:</strong></div>
            <div>
                <select class="form-select form-select-sm" name="status" id="status">
                    <option value="Paid" {{ $sales->status == 'Paid' ? 'selected' : '' }}>
                        {{ __('messages.paid') }}
                    </option>
                    <option value="Unpaid" {{ $sales->status != 'Paid' ? 'selected' : '' }}>
                        {{ __('messages.unpaid') }}
                    </option>
                </select>
            </div>
        </div>

        @if ($sales->status === 'Paid' && isset($sales->payment_date))
            <div class="d-flex justify-content-between mt-2">
                <div><strong>{{ __('messages.payment_date') }}:</strong></div>
                <div>
                    {{ $sales->payment_date->format('d F Y H:i') }}
                </div>
            </div>
        @endif
    </div>
</div>
