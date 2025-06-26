<div class="row g-3 mb-4">
    <div class="col-md-2">
        <label class="form-label fw-bold">Invoice</label>
        <input type="text" class="form-control" name="invoice" id="invoice" placeholder="Invoice Number" />
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">Supplier</label>
        <select class="form-select" name="supplier_id" id="supplier_id">
            <option value="">Select Supplier</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" data-payment-terms="{{ $supplier->payment_terms }}">
                    {{ $supplier->name }}
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
        <input type="text" class="form-control" name="due_date" id="due_date" placeholder="AUTOFILL" readonly />
    </div>
</div>
