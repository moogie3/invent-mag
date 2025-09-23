<div class="card border">
    <div class="card-body p-3">
        <h5 class="mb-3 card-title">{{ __('messages.amount_summary') }}</h5>
        <div class="d-flex justify-content-between mb-2">
            <div>{{ __('messages.subtotal') }}:</div>
            <div id="subtotal">
                {{ \App\Helpers\CurrencyHelper::format($summary['subtotal']) }}
            </div>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <div>
                <span>{{ __('messages.order_discount') }}:</span>
            </div>
            <div class="d-flex align-items-center">
                <div class="input-group me-2" style="width: 200px;">
                    <input type="number" name="order_discount" value="{{ (float) ($sales->order_discount ?? 0) }}"
                        class="form-control text-end" id="discountTotalValue" step="1" min="0"
                        style="min-width: 80px;" />

                    <select name="order_discount_type" class="form-select" id="discountTotalType"
                        style="min-width: 70px;">
                        <option value="percentage"
                            {{ ($sales->order_discount_type ?? '') === 'percentage' ? 'selected' : '' }}>
                            %</option>
                        <option value="fixed" {{ ($sales->order_discount_type ?? '') === 'fixed' ? 'selected' : '' }}>
                            {{ __('messages.fixed') }}</option>
                    </select>
                </div>
                <div class="text-danger" id="orderDiscountTotal">
                    {{ \App\Helpers\CurrencyHelper::format($summary['orderDiscount']) }}
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between mb-2">
                <div>{{ __('messages.tax') }} ({{ $sales->tax_rate ?? 0 }}%):</div>
                <div class="text-muted" id="totalTax">
                    {{ \App\Helpers\CurrencyHelper::format($summary['taxAmount']) }}
                </div>
            </div>

        <hr>
        <div class="d-flex justify-content-between align-items-center">
            <div class="fs-5"><strong>{{ __('messages.grand_total') }}:</strong></div>
            <div class="fs-3 fw-bold text-primary" id="finalTotal">
                {{ \App\Helpers\CurrencyHelper::format($summary['finalTotal']) }}
            </div>
        </div>

        <!-- Hidden inputs for form submission -->
        <input type="hidden" id="grandTotalInput" name="total" value="{{ $summary['finalTotal'] }}">
        <input type="hidden" id="taxInput" name="tax_amount" value="{{ $summary['taxAmount'] }}">
        <input type="hidden" id="totalDiscountInput" name="total_discount" value="{{ $summary['orderDiscount'] }}">
        <input type="hidden" id="taxRateInput" name="tax_rate" value="{{ $sales->tax_rate ?? 0 }}">
    </div>
</div>
