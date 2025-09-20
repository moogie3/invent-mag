<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h4 class="mb-0"><i class="ti ti-percentage"></i> {{ __('messages.po_order_discount') }}</h4>
    </div>
    <div class="card-body">
        <label class="form-label fw-bold">{{ __('messages.apply_order_discount') }}</label>
        <div class="input-group mb-2">
            <input type="number" min="0" class="form-control" id="discountTotalValue" name="discount_total"
                placeholder="0" />
            <select class="form-select" id="discountTotalType" name="discount_total_type" style="max-width: 80px;">
                <option value="fixed">{{ __('messages.po_fixed') }}</option>
                <option value="percentage">%</option>
            </select>
            <button type="button" id="applyTotalDiscount" class="btn btn-secondary d-none d-sm-inline-block">
                <i class="ti ti-discount-check"></i> {{ __('messages.apply') }}
            </button>
        </div>
        <small class="text-muted">{{ __('messages.discount_hint') }}</small>
        <input type="hidden" id="totalDiscountInput" name="total_discount" value="0">
    </div>
</div>
