<div class="row mb-5 g-3">
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm rounded-3">
            <div class="card-body p-4 bg-body-secondary rounded-3">
                <h5 class="card-title mb-3 d-flex align-items-center text-primary">
                    <i class="ti ti-info-circle me-2"></i>{{ __('messages.quotation_notes') }}
                </h5>
                <div class="mb-3">
                    <label class="form-label" for="notes">{{ __('messages.quotation_notes') }}</label>
                    <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $quotation->notes ?? '') }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-0">
                    <label class="form-label" for="terms_conditions">{{ __('messages.quotation_terms_conditions') }}</label>
                    <textarea name="terms_conditions" id="terms_conditions" class="form-control @error('terms_conditions') is-invalid @enderror" rows="3">{{ old('terms_conditions', $quotation->terms_conditions ?? '') }}</textarea>
                    @error('terms_conditions')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                    <span class="text-muted" id="discountLabel" data-base="{{ __('messages.quotation_discount') }}">{{ __('messages.quotation_discount') }}</span>
                    <div class="d-flex align-items-center">
                        <div class="input-group me-2" style="width: 200px;">
                            <input type="number" name="discount_total" class="form-control text-end @error('discount_total') is-invalid @enderror" step="1" min="0"
                                value="{{ old('discount_total', $quotation->discount_value ?? 0) }}" id="orderDiscount" style="min-width: 80px;">
                            <select name="discount_total_type" class="form-select @error('discount_total_type') is-invalid @enderror" id="orderDiscountType" style="min-width: 70px;">
                                <option value="percentage" {{ (old('discount_total_type', $quotation->discount_type ?? 'percentage')) == 'percentage' ? 'selected' : '' }}>%</option>
                                <option value="fixed" {{ (old('discount_total_type', $quotation->discount_type ?? '')) == 'fixed' ? 'selected' : '' }}>{{ __('messages.fixed') }}</option>
                            </select>
                        </div>
                        @error('discount_total')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @error('discount_total_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <span class="text-danger fw-semibold" id="displayDiscount">0</span>
                    </div>
                </div>
                <div class="d-flex justify-content-between mb-3 pb-2 border-bottom border-primary border-opacity-25">
                    <span class="text-muted" id="taxLabel" data-base="{{ __('messages.quotation_tax') }}">
                        {{ __('messages.quotation_tax') }}
                    </span>
                    <span class="d-flex align-items-center gap-2">
                        <select name="tax_id" class="form-select @error('tax_id') is-invalid @enderror" id="taxSelector" style="width: auto; min-width: 160px;">
                            <option value="" data-name="{{ __('messages.none') }}">{{ __('messages.none') }} (0%)</option>
                            @foreach($taxes as $tax)
                                <option value="{{ $tax->id }}" data-name="{{ $tax->name }}" data-rate="{{ $tax->rate }}" data-type="{{ $tax->type }}"
                                    {{ old('tax_id') ? (old('tax_id') == $tax->id ? 'selected' : '') : (($quotation->tax_id ?? '') == $tax->id ? 'selected' : ($tax->is_default ? 'selected' : '')) }}>
                                    {{ $tax->name }} ({{ $tax->rate }}%)
                                </option>
                            @endforeach
                        </select>
                        @error('tax_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <span class="text-muted fw-semibold" id="displayTax">0</span>
                    </span>
                </div>
                @php $currentTenant = app('currentTenant'); @endphp
                @if($currentTenant->isIndonesian() && $currentTenant->hasFeature('ppn'))
                <div class="d-flex justify-content-between mb-3 pb-2 border-bottom border-primary border-opacity-25">
                    <span class="text-muted">
                        {{ __('messages.withholding_tax') }}
                    </span>
                    <span class="d-flex align-items-center gap-2">
                        <select name="pph_tax_id" class="form-select @error('pph_tax_id') is-invalid @enderror" id="pphSelector" style="width: auto; min-width: 160px;">
                            <option value="">{{ __('messages.none') }}</option>
                            @foreach($taxes->where('is_pph', true) as $pphTax)
                                <option value="{{ $pphTax->id }}" data-rate="{{ $pphTax->rate }}" data-type="{{ $pphTax->type }}"
                                    {{ old('pph_tax_id') ? (old('pph_tax_id') == $pphTax->id ? 'selected' : '') : (($quotation->pph_tax_id ?? '') == $pphTax->id ? 'selected' : '') }}>
                                    {{ $pphTax->name }} ({{ $pphTax->rate }}%)
                                </option>
                            @endforeach
                        </select>
                        @error('pph_tax_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <span class="text-muted fw-semibold" id="displayPph">0</span>
                    </span>
                </div>
                <input type="hidden" id="pphEnabledInput" name="pph_enabled" value="{{ ($quotation->pph_enabled ?? false) ? 1 : 0 }}">
                <input type="hidden" id="pphRateInput" name="pph_rate" value="{{ $quotation->pph_rate ?? 0 }}">
                <input type="hidden" id="pphAmountInput" name="pph_amount" value="{{ $quotation->pph_amount ?? 0 }}">
                @endif
                <div class="mb-3 pb-2 border-bottom border-primary border-opacity-25"></div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold fs-5">{{ __('messages.quotation_total') }}</span>
                    <strong id="displayTotal" class="fs-2 text-primary">0</strong>
                </div>
            </div>
        </div>
    </div>
</div>
