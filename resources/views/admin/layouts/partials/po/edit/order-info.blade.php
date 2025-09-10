<div class="card bg-light border-0 h-100">
    <div class="card-body p-3">
        <h4 class="card-title mb-3">
            <i class="ti ti-calendar-event me-2 text-primary"></i>Order Information
        </h4>
        <div class="d-flex justify-content-between mb-2">
            <div><strong>Order Date:</strong></div>
            <div>{{ $pos->order_date->format('d F Y') }}</div>
            <input type="hidden" name="order_date" value="{{ $pos->order_date }}">
        </div>
        <div class="d-flex justify-content-between mb-2">
            <div><strong>Due Date:</strong></div>
            <div>{{ $pos->due_date->format('d F Y') }}</div>
            <input type="hidden" name="due_date" value="{{ $pos->due_date }}">
        </div>

        <div class="d-flex justify-content-between mb-2">
            <div><strong>Payment Type:</strong></div>
            <div>
                <select class="form-select form-select-sm" name="payment_type" id="payment_type">
                    <option value="Cash" {{ $pos->payment_type == 'Cash' ? 'selected' : '' }}>
                        Cash
                    </option>
                    <option value="Transfer" {{ $pos->payment_type == 'Transfer' ? 'selected' : '' }}>
                        Transfer
                    </option>
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <div><strong>Payment Status:</strong></div>
            <div>
                <select class="form-select form-select-sm" name="status" id="status">
                    <option value="Paid" {{ $pos->status == 'Paid' ? 'selected' : '' }}>
                        Paid
                    </option>
                    <option value="Unpaid" {{ $pos->status != 'Paid' ? 'selected' : '' }}>
                        Unpaid
                    </option>
                </select>
            </div>
        </div>

        @if ($pos->status === 'Paid' && isset($pos->payment_date))
            <div class="d-flex justify-content-between mt-2">
                <div><strong>Payment Date:</strong></div>
                <div>
                    {{ $pos->payment_date->format('d F Y H:i') }}
                </div>
            </div>
        @endif
    </div>
</div>
