<div class="h-100 p-4 bg-body-secondary rounded-3">
    <h4 class="card-title mb-4">
        <i class="ti ti-percentage me-2 text-primary"></i> {{ __('messages.order_discount') }}
    </h4>
    <label class="form-label fw-bold" for="discountTotalValue">{{ __('messages.apply_order_discount') }}</label>
    <div class="input-group mb-2">
        <input type="number" min="0" class="form-control @error('discount_total') is-invalid @enderror" id="discountTotalValue" name="discount_total"
            placeholder="0" value="{{ old('discount_total') }}" />
        <select class="form-select @error('discount_total_type') is-invalid @enderror" id="discountTotalType" name="discount_total_type" style="max-width: 110px;">
            <option value="fixed" {{ old('discount_total_type') == 'fixed' ? 'selected' : '' }}>{{ __('messages.sales_fixed') }}</option>
            <option value="percentage" {{ old('discount_total_type') == 'percentage' ? 'selected' : '' }}>{{ __('messages.percentage') }}</option>
        </select>
        <button type="button" id="applyTotalDiscount" class="btn btn-secondary d-none d-sm-inline-block">
            <i class="ti ti-discount-check"></i> {{ __('messages.apply') }}
        </button>
    </div>
    @error('discount_total')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @error('discount_total_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    <small class="text-muted">{{ __('messages.discount_hint') }}</small>
    <input type="hidden" id="totalDiscountInput" name="total_discount" value="0">
    <input type="hidden" id="orderDiscountInput" name="order_discount" value="0">
    <input type="hidden" id="orderDiscountTypeInput" name="order_discount_type" value="fixed">

    <hr class="my-3">
    <h4 class="card-title mb-3">
        <i class="ti ti-receipt-tax me-2 text-primary"></i> {{ __('messages.tax') }}
    </h4>
    <div class="mb-2">
        <select class="form-select @error('tax_id') is-invalid @enderror" id="taxSelector" name="tax_id">
            @foreach($taxes as $taxOption)
                <option value="{{ $taxOption->id }}"
                        data-name="{{ $taxOption->name }}"
                        data-rate="{{ $taxOption->rate }}"
                        data-type="{{ $taxOption->type }}"
                        {{ old('tax_id') && old('tax_id') == $taxOption->id ? 'selected' : ($defaultTax && $defaultTax->id === $taxOption->id ? 'selected' : '') }}>
                    {{ $taxOption->name }} ({{ $taxOption->rate }}%{{ $taxOption->type !== 'none' ? ', ' . ucfirst($taxOption->type) : '' }})
                </option>
            @endforeach
        </select>
        @error('tax_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
