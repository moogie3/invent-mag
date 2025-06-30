<div class="card mb-4 border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <label class="form-label">Transaction Date</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="ti ti-calendar"></i>
                        </span>
                        <input type="text" class="form-control" id="transaction_date" name="transaction_date"
                            value="{{ date('d F Y H:i') }}" />
                    </div>
                </div>
                <div class="me-3">
                    <label class="form-label">Customer</label>
                    <select class="form-select" name="customer_id" id="customer_id">
                        <option value="">Select Customer</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" data-payment-terms="{{ $customer->payment_terms }}"
                                {{ $customer->id === $walkInCustomerId ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="button" id="clearCart" class="btn btn-outline-danger">
                <i class="ti ti-trash me-1"></i> Clear Cart
            </button>
        </div>
    </div>
</div>
