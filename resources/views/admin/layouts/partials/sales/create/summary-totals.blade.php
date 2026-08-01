<div class="h-100 p-4 bg-primary-lt rounded-3">
    <h4 class="card-title mb-4">
        <i class="ti ti-report me-2 text-primary"></i> {{ __('messages.order_summary_title') }}
    </h4>
    <div class="d-flex justify-content-between mb-2">
        <span class="text-muted">{{ __('messages.sales_subtotal') }}</span>
        <span id="subtotal" class="fw-bold">Rp 0</span>
    </div>
    <div class="d-flex justify-content-between mb-2">
        <span class="text-muted" id="discountLabel" data-base="{{ __('messages.sales_order_discount') }}">{{ __('messages.sales_order_discount') }}</span>
        <span id="orderDiscountTotal" class="fw-bold">Rp 0</span>
    </div>
    <div class="d-flex justify-content-between mb-2">
        <span class="text-muted" id="taxLabel" data-base="{{ __('messages.tax') }}">{{ __('messages.tax') }}</span>
        <span id="taxTotal" class="fw-bold">Rp 0</span>
    </div>
    @php $currentTenant = app('currentTenant'); @endphp
    @if($currentTenant->isIndonesian() && $currentTenant->hasFeature('ppn'))
    <div class="d-flex justify-content-between mb-2">
        <span class="text-muted">{{ __('messages.withholding_tax') }}</span>
        <div class="d-flex align-items-center">
            <select class="form-select form-select-sm me-2 @error('pph_tax_id') is-invalid @enderror" id="pphSelector" name="pph_tax_id" style="width: auto;">
                <option value="">{{ __('messages.no_pph') }}</option>
                @foreach($taxes->where('is_pph', true) as $pphOption)
                    <option value="{{ $pphOption->id }}"
                            data-rate="{{ $pphOption->rate }}"
                            data-type="{{ $pphOption->type }}"
                            {{ old('pph_tax_id') == $pphOption->id ? 'selected' : '' }}>
                        {{ $pphOption->name }} ({{ $pphOption->rate }}%)
                    </option>
                @endforeach
            </select>
            @error('pph_tax_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <span id="pphTotal" class="fw-bold text-danger">Rp 0</span>
        </div>
    </div>
    <input type="hidden" id="pphEnabledInput" name="pph_enabled" value="0">
    <input type="hidden" id="pphRateInput" name="pph_rate" value="0">
    <input type="hidden" id="pphAmountInput" name="pph_amount" value="0">
    @endif
    <div class="d-flex justify-content-between mb-2 text-info" id="creditUsedRow" style="display: none;">
        <span><i class="ti ti-credit-card me-1"></i>{{ __('messages.credit_used') }}</span>
        <span id="creditUsedAmount" class="fw-bold">Rp 0</span>
    </div>
    <hr class="my-2 border-primary">
    <div class="d-flex justify-content-between">
        <span class="fs-4 fw-bold">{{ __('messages.sales_grand_total') }}</span>
        <span id="finalTotal" class="fs-4 fw-bold text-primary">Rp 0</span>
    </div>
    <input type="hidden" id="taxInput" name="tax_amount" value="0">
    <input type="hidden" id="taxTypeInput" name="tax_type" value="{{ $defaultTax->type ?? 'exclusive' }}">
    <input type="hidden" id="taxRateInput" name="tax_rate" value="{{ $defaultTax->rate ?? 0 }}">
    <input type="hidden" id="creditUsedInput" name="credit_used" value="0">
</div>
