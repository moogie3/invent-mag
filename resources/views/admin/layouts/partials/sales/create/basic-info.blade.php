<div class="row g-3 mb-4">
    <div class="col-md-2">
        <label class="form-label fw-bold">Invoice</label>
        <input type="text" class="form-control" name="invoice" id="invoice" placeholder="Invoice Number" />
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">Customer</label>
        <select class="form-select" name="customer_id" id="customer_id">
            <option value="">Select Customer</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" data-payment-terms="{{ $customer->payment_terms }}">
                    {{ $customer->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">Order Date</label>
        <input type="date" class="form-control" name="order_date" id="order_date" />
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">Due Date</label>
        <input type="date" class="form-control bg-light" name="due_date" id="due_date" placeholder="AUTOFILL"
            readonly />
    </div>
</div>
