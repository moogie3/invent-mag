<div class="card border-0 h-100">
    <div class="card-body p-3">
        <h4 class="card-title mb-3">
            <i class="ti ti-calendar-event me-2 text-primary"></i>Order Information
        </h4>
        <div class="d-flex justify-content-between mb-2">
            <div><strong>Order Date:</strong></div>
            <div>{{ $sales->order_date->format('d F Y') }}</div>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <div><strong>Due Date:</strong></div>
            <div>{{ $sales->due_date->format('d F Y') }}</div>
        </div>

        <div class="d-flex justify-content-between mb-2">
            <div><strong>Payment Type:</strong></div>
            <div>
                <select class="form-select form-select-sm" name="payment_type" id="payment_type"
                    {{ $sales->status == 'Paid' ? 'disabled' : '' }}>
                    <option value="Cash" {{ $sales->payment_type == 'Cash' ? 'selected' : '' }}>
                        Cash
                    </option>
                    <option value="Transfer" {{ $sales->payment_type == 'Transfer' ? 'selected' : '' }}>
                        Transfer
                    </option>
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <div><strong>Payment Status:</strong></div>
            <div>
                <select class="form-select form-select-sm" name="status" id="status"
                    {{ $sales->status == 'Paid' ? 'disabled' : '' }}>
                    <option value="Paid" {{ $sales->status == 'Paid' ? 'selected' : '' }}>
                        Paid
                    </option>
                    <option value="Unpaid" {{ $sales->status == 'Unpaid' ? 'selected' : '' }}>
                        Unpaid
                    </option>
                </select>
            </div>
        </div>

        @if ($sales->status === 'Paid' && isset($sales->payment_date))
            <div class="d-flex justify-content-between mt-2">
                <div><strong>Payment Date:</strong></div>
                <div>
                    {{ $sales->payment_date->format('d F Y H:i') }}
                </div>
            </div>
        @endif
    </div>
</div>
