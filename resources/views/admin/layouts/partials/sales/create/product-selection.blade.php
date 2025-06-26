<h5 class="text-muted fw-bold mt-2 mb-3">Add Product to Order</h5>
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label fw-bold">Product</label>
        <select class="form-select" name="product_id" id="product_id">
            <option value="">Select Product</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" data-price="{{ $product->price }}"
                    data-selling-price="{{ $product->selling_price }}" data-stock="{{ $product->stock_quantity ?? 0 }}">
                    {{ $product->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">Stock & Quantity</label>
        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="form-control bg-light d-flex align-items-center" style="width: 50%;">
                <i class="ti ti-package me-2 text-muted"></i>
                <span id="stock_available" class="fw-bold text-primary">-</span>
            </div>
            <input type="number" min="1" class="form-control" name="quantity" id="quantity" placeholder="0" />
        </div>
        <small id="quantity_warning" class="text-danger d-none mt-1">
            <i class="ti ti-alert-triangle"></i> Exceeds available stock!
        </small>
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">Customer Price</label>
        <input type="text" class="form-control bg-light" name="past_price" id="past_price" placeholder="AUTOFILL"
            readonly disabled />
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">New Price</label>
        <input type="text" class="form-control" name="customer_price" id="customer_price" placeholder="0" />
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">Discount</label>
        <div class="input-group">
            <input type="number" min="0" class="form-control" id="discount" placeholder="0" />
            <select class="form-select" id="discount_type" style="max-width: 70px;">
                <option value="fixed">Rp</option>
                <option value="percentage">%</option>
            </select>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-md-5">
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">Supplier Price</label>
        <input type="text" class="form-control bg-light" name="price" id="price" placeholder="AUTOFILL"
            readonly disabled />
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">Retail Price</label>
        <input type="text" class="form-control bg-light" name="selling_price" id="selling_price"
            placeholder="AUTOFILL" readonly disabled />
    </div>
</div>
