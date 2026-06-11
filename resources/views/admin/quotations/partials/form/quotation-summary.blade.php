<div class="row mb-5 g-3">
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm rounded-3">
            <div class="card-body p-4 bg-body-secondary rounded-3">
                <h5 class="card-title mb-3 d-flex align-items-center text-primary">
                    <i class="ti ti-info-circle me-2"></i>{{ __('messages.quotation_notes') }}
                </h5>
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.quotation_notes') }}</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $quotation->notes ?? '') }}</textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label">{{ __('messages.quotation_terms_conditions') }}</label>
                    <textarea name="terms_conditions" class="form-control" rows="3">{{ old('terms_conditions', $quotation->terms_conditions ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm rounded-3">
            <div class="card-body p-4 bg-primary-lt rounded-3">
                <h5 class="card-title mb-3 d-flex align-items-center text-primary">
                    <i class="ti ti-receipt me-2"></i>{{ __('messages.amount_summary_title') }}
                </h5>
                <div class="d-flex justify-content-between mb-3 pb-2 border-bottom border-primary border-opacity-25">
                    <span class="text-muted">{{ __('messages.quotation_subtotal') }}</span>
                    <strong id="displaySubtotal" class="fs-5">0</strong>
                </div>
                <div class="d-flex justify-content-between mb-3 align-items-center pb-2 border-bottom border-primary border-opacity-25">
                    <span class="text-muted">{{ __('messages.quotation_discount') }}</span>
                    <div class="d-flex align-items-center">
                        <div class="input-group me-2" style="width: 200px;">
                            <input type="number" name="discount_total" class="form-control text-end" step="1" min="0"
                                value="{{ old('discount_total', $quotation->discount_value ?? 0) }}" id="orderDiscount" style="min-width: 80px;">
                            <select name="discount_total_type" class="form-select" id="orderDiscountType" style="min-width: 70px;">
                                <option value="percentage" {{ (old('discount_total_type', $quotation->discount_type ?? 'percentage')) == 'percentage' ? 'selected' : '' }}>%</option>
                                <option value="fixed" {{ (old('discount_total_type', $quotation->discount_type ?? '')) == 'fixed' ? 'selected' : '' }}>{{ __('messages.fixed') }}</option>
                            </select>
                        </div>
                        <span class="text-danger fw-semibold" id="displayDiscount">0</span>
                    </div>
                </div>
                <div class="d-flex justify-content-between mb-3 pb-2 border-bottom border-primary border-opacity-25">
                    <span class="text-muted">
                        {{ __('messages.quotation_tax') }}
                        <select name="tax_type" class="form-select d-inline-block ms-1" style="width: auto; min-width: 100px;" id="taxType">
                            <option value="none" {{ (old('tax_type', $quotation->tax_type ?? 'none')) == 'none' ? 'selected' : '' }}>{{ __('messages.none') }}</option>
                            <option value="exclusive" {{ (old('tax_type', $quotation->tax_type ?? '')) == 'exclusive' ? 'selected' : '' }}>{{ __('messages.exclusive') }}</option>
                            <option value="inclusive" {{ (old('tax_type', $quotation->tax_type ?? '')) == 'inclusive' ? 'selected' : '' }}>{{ __('messages.inclusive') }}</option>
                        </select>
                    </span>
                    <span class="d-flex align-items-center gap-2">
                        <input type="number" name="tax_rate" class="form-control text-end" step="1" min="0" max="100"
                            value="{{ old('tax_rate', $quotation->tax_rate ?? 0) }}" id="taxRate" placeholder="%" style="width: 80px;">
                        <span class="text-muted fw-semibold" id="displayTax">0</span>
                    </span>
                </div>
                <div class="mb-3 pb-2 border-bottom border-primary border-opacity-25"></div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold fs-5">{{ __('messages.quotation_total') }}</span>
                    <strong id="displayTotal" class="fs-2 text-primary">0</strong>
                </div>
            </div>
        </div>
    </div>
</div>
