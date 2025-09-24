<h5 class="text-muted fw-bold mt-2 mb-3">{{ __('messages.add_product_to_order') }}</h5>
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label fw-bold">{{ __('messages.product') }}</label>
        <select class="form-select" name="product_id" id="product_id">
            <option value="">{{ __('messages.select_product') }}</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" data-price="{{ $product->price }}"
                    data-selling-price="{{ $product->selling_price }}" data-stock="{{ $product->stock_quantity ?? 0 }}">
                    {{ $product->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">{{ __('messages.pos_stock_quantity') }}</label>
        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="form-control bg-light d-flex align-items-center" style="width: 50%;">
                <i class="ti ti-package me-2 text-muted"></i>
                <span id="stock_available" class="fw-bold text-primary">-</span>
            </div>
            <input type="number" min="1" class="form-control" name="quantity" id="quantity" placeholder="0" />
        </div>
        <small id="quantity_warning" class="text-danger d-none mt-1">
            <i class="ti ti-alert-triangle"></i> {{ __('messages.exceeds_available_stock') }}
        </small>
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">{{ __('messages.pos_selling_price') }}</label>
        <input type="text" class="form-control bg-light" name="past_price" id="past_price" placeholder="AUTOFILL"
            readonly disabled />
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">{{ __('messages.new_price') }}</label>
        <input type="text" class="form-control" name="customer_price" id="customer_price" placeholder="0" />
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">{{ __('messages.discount') }}</label>
        <div class="input-group">
            <input type="number" min="0" class="form-control" id="discount" placeholder="0" />
            <select class="form-select" id="discount_type" style="max-width: 70px;">
                <option value="fixed">{{ __('messages.po_fixed') }}</option>
                <option value="percentage">%</option>
            </select>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-md-5">
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">{{ __('messages.supplier_price') }}</label>
        <input type="text" class="form-control bg-light" name="price" id="price"
            placeholder="{{ __('messages.autofill') }}" readonly disabled />
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">{{ __('messages.pos_selling_price') }}</label>
        <input type="text" class="form-control bg-light" name="selling_price" id="selling_price"
            placeholder="{{ __('messages.autofill') }}" readonly disabled />
    </div>
</div>
