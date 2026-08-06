<div class="h-100 p-4 bg-primary-lt rounded-3">
    <h4 class="card-title mb-4">
        <i class="ti ti-report me-2 text-primary"></i> {{ __('messages.po_order_summary_title') }}
    </h4>
    <div class="d-flex justify-content-between mb-2">
        <span class="text-muted">{{ __('messages.po_subtotal') }}</span>
        <span id="subtotal" class="fw-bold">Rp 0</span>
    </div>
    <div class="d-flex justify-content-between mb-2">
        <span class="text-muted" id="discountLabel" data-base="{{ __('messages.po_order_discount') }}">{{ __('messages.po_order_discount') }}</span>
        <span id="orderDiscountTotal" class="fw-bold">Rp 0</span>
    </div>
    @php $currentTenant = app('currentTenant'); @endphp
    @if($currentTenant->isIndonesian() && $currentTenant->hasFeature('ppn'))
    <div class="d-flex justify-content-between mb-2" id="ppnMasukanRow" style="{{ ($defaultTax?->is_ppn ?? false) ? '' : 'display:none!important' }}">
        <span class="text-muted">{{ __('messages.ppn_masukan') }} (<span id="ppnMasukanRateLabel">{{ ($defaultTax?->is_ppn ?? false) ? $defaultTax->rate : 0 }}</span>%)</span>
        <span id="ppnMasukanTotal" class="fw-bold text-info">Rp 0</span>
    </div>
    <input type="hidden" id="ppnEnabledInput" name="ppn_enabled" value="{{ ($defaultTax?->is_ppn ?? false) ? 1 : 0 }}">
    <input type="hidden" id="ppnRateInput" name="ppn_rate" value="{{ ($defaultTax?->is_ppn ?? false) ? $defaultTax->rate : 0 }}">
    <input type="hidden" id="ppnAmountInput" name="ppn_amount" value="0">
    <div class="d-flex justify-content-between mb-2">
        <span class="text-muted">{{ __('messages.withholding_tax') }}</span>
        <div class="d-flex align-items-center">
            <select class="form-select form-select-sm me-2" id="pphSelector" name="pph_tax_id" style="width: auto;">
                <option value="">{{ __('messages.no_pph') }}</option>
                @foreach($taxes->where('is_pph', true) as $pphOption)
                    <option value="{{ $pphOption->id }}"
                            data-rate="{{ $pphOption->rate }}"
                            data-type="{{ $pphOption->type }}">
                        {{ $pphOption->name }} ({{ $pphOption->rate }}%)
                    </option>
                @endforeach
            </select>
            <span id="pphTotal" class="fw-bold text-danger">Rp 0</span>
        </div>
    </div>
    <input type="hidden" id="pphEnabledInput" name="pph_enabled" value="0">
    <input type="hidden" id="pphRateInput" name="pph_rate" value="0">
    <input type="hidden" id="pphAmountInput" name="pph_amount" value="0">
    @endif
    <hr class="my-2 border-primary">
    <div class="d-flex justify-content-between">
        <span class="fs-4 fw-bold">{{ __('messages.po_grand_total') }}</span>
        <span id="finalTotal" class="fs-4 fw-bold text-primary">Rp 0</span>
    </div>
    <input type="hidden" id="taxInput" name="tax_amount" value="0">
    <input type="hidden" id="taxRateInput" name="tax_rate" value="0">
    <input type="hidden" id="taxTypeInput" name="tax_type" value="none">
</div>
