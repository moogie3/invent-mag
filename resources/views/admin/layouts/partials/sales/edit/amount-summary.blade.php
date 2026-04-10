<div class="h-100 p-4 bg-primary-lt rounded-3">
    <h5 class="mb-3 card-title text-primary">{{ __('messages.amount_summary_title') }}</h5>
    <div class="d-flex justify-content-between mb-2">
        <div>{{ __('messages.subtotal') }}</div>
        <div id="subtotal">
            {{ \App\Helpers\CurrencyHelper::format($summary['subtotal']) }}
        </div>
    </div>
    <div class="d-flex justify-content-between mb-2">
        <div>
            <span>{{ __('messages.order_discount') }}</span>
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
                        {{ __('messages.percentage') }}</option>
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
        <div>{{ __('messages.tax') }} ({{ $sales->tax_rate ?? 0 }}%)</div>
        <div class="text-muted" id="totalTax">
            {{ \App\Helpers\CurrencyHelper::format($summary['taxAmount']) }}
        </div>
    </div>
    <hr class="border-primary">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="fs-4"><strong>{{ __('messages.original_total') }}</strong></div>
        <div class="fs-3 fw-bold text-primary" id="finalTotal">
            {{ \App\Helpers\CurrencyHelper::format($summary['finalTotal']) }}
        </div>
    </div>

    @php
        $totalReturned = $sales->getTotalReturnedAmount();
        $adjustedBalance = $sales->balance;
    @endphp

    @if($totalReturned > 0)
    <div class="d-flex justify-content-between text-danger mb-2 border-bottom border-light pb-2">
        <div class="fs-5"><i class="ti ti-package-export me-1"></i>{{ __('messages.returned') }} ({{ __('messages.sales_totals_adjusted') }})</div>
        <div class="fs-5 fw-bold">- {{ \App\Helpers\CurrencyHelper::format($totalReturned) }}</div>
    </div>
    @endif

    @if(($sales['customer_credit_used'] ?? 0) > 0)
    <div class="d-flex justify-content-between text-info mb-1">
        <span><i class="ti ti-credit-card me-1"></i>{{ __('messages.credit_used') }}</span>
        <span>{{ \App\Helpers\CurrencyHelper::format($sales['customer_credit_used'] ?? 0) }}</span>
    </div>
    @endif

    <div class="d-flex justify-content-between text-success mb-1">
        <span><i class="ti ti-wallet me-1"></i>{{ __('messages.total_paid') }}</span>
        <span id="total-paid-amount" data-total-paid="{{ $sales->total_paid }}">{{ \App\Helpers\CurrencyHelper::format($sales->total_paid) }}</span>
    </div>
    <div class="d-flex justify-content-between text-danger fw-bold">
        <span><i class="ti ti-wallet me-1"></i>{{ __('messages.balance') }}</span>
        <span id="balance-amount" 
              data-balance="{{ $adjustedBalance }}" 
              data-returned="{{ $totalReturned }}"
              data-original-total="{{ $sales->total }}"
              data-total-paid="{{ $sales->total_paid }}"
              data-credit-used="{{ $sales['customer_credit_used'] ?? 0 }}">
            {{ \App\Helpers\CurrencyHelper::format($adjustedBalance) }}
        </span>
    </div>
    <input type="hidden" id="grandTotalInput" name="total" value="{{ $summary['finalTotal'] }}">
    <input type="hidden" id="taxInput" name="tax_amount" value="{{ $summary['taxAmount'] }}">
    <input type="hidden" id="totalDiscountInput" name="total_discount" value="{{ $summary['orderDiscount'] }}">
    <input type="hidden" id="taxRateInput" name="tax_rate" value="{{ $sales->tax_rate ?? 0 }}">
</div>
