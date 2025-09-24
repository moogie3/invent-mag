<div class="card border">
    <div class="card-body p-3">
        <h5 class="mb-3 card-title">{{ __('messages.po_amount_summary_title') }}</h5>
        <div class="d-flex justify-content-between mb-2">
            <div>{{ __('messages.po_subtotal') }}</div>
            <div id="subtotal">
                {{ \App\Helpers\CurrencyHelper::format($summary['subtotal']) }}
            </div>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <div>
                <span>{{ __('messages.po_order_discount') }}</span>
            </div>
            <div class="d-flex align-items-center">
                <div class="input-group me-2" style="width: 200px;">
                    <input type="number" name="discount_total" value="{{ (float) $pos->discount_total }}"
                        class="form-control text-end" id="discountTotalValue" step="0" min="0"
                        style="min-width: 80px;" />

                    <select name="discount_total_type" class="form-select" id="discountTotalType"
                        style="min-width: 70px;">
                        <option value="percentage" {{ $pos->discount_total_type === 'percentage' ? 'selected' : '' }}>
                            {{ __('messages.percentage') }}</option>
                        <option value="fixed" {{ $pos->discount_total_type === 'fixed' ? 'selected' : '' }}>
                            {{ __('messages.po_fixed') }}</option>
                    </select>
                </div>
                <div class="text-danger" id="orderDiscountTotal">
                    {{ \App\Helpers\CurrencyHelper::format($summary['orderDiscount']) }}
                </div>
            </div>
        </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center">
            <div class="fs-5"><strong>{{ __('messages.po_grand_total') }}</strong></div>
            <div class="fs-3 fw-bold text-primary" id="finalTotal">
                {{ \App\Helpers\CurrencyHelper::format($summary['finalTotal']) }}
            </div>
        </div>
        <input type="hidden" id="totalDiscountInput" name="total_discount" value="{{ $summary['orderDiscount'] }}">
    </div>
</div>
