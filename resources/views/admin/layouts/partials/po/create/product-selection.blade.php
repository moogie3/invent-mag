<h5 class="text-muted fw-bold mt-2 mb-3">{{ __('messages.add_product_to_order') }}</h5>
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label fw-bold">{{ __('messages.products') }}</label>
        <select class="form-select" name="product_id" id="product_id">
            <option value="">{{ __('messages.select_product') }}</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" data-price="{{ $product->price }}"
                    data-stock="{{ $product->stock_quantity ?? 0 }}"
                    data-has-expiry="{{ $product->has_expiry ? '1' : '0' }}">
                    {{ $product->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-bold">{{ __('messages.product_stock_quantity') }}</label>
        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="form-control d-flex align-items-center" style="width: 50%;">
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
        <label class="form-label fw-bold">{{ __('messages.supplier_price') }}</label>
        <input type="text" class="form-control" name="last_price" id="last_price"
            placeholder="{{ __('messages.autofill') }}" readonly disabled />
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">{{ __('messages.new_price') }}</label>
        <input type="number" min="0" step="0" class="form-control" name="new_price" id="new_price"
            placeholder="0" />
    </div>
    <div class="col-md-2 mb-3" style="display: none;">
        <label class="form-label fw-bold">{{ __('messages.table_expiry_date') }}</label>
        <input type="date" class="form-control" name="expiry_date" id="expiry_date" />
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">{{ __('messages.discount') }}</label>
        <div class="input-group">
            <input type="number" min="0" step="0" class="form-control" id="discount" placeholder="0" />
            <select class="form-select" id="discount_type" style="max-width: 70px;">
                <option value="fixed">{{ __('messages.po_fixed') }}</option>
                <option value="percentage">{{ __('messages.percentage') }}</option>
            </select>
        </div>
    </div>
</div>
