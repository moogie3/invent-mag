<div class="h-100 p-4 bg-primary-lt rounded-3">
    <h5 class="mb-3 card-title text-primary">{{ __('messages.po_amount_summary_title') }}</h5>
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
    <div class="d-flex justify-content-between mb-2 align-items-center">
        <div>{{ __('messages.tax') }}</div>
        <div class="d-flex align-items-center">
            <select class="form-select me-2" id="taxSelector" name="tax_id" style="width: auto;">
                @foreach($taxes as $taxOption)
                    <option value="{{ $taxOption->id }}"
                            data-rate="{{ $taxOption->rate }}"
                            data-type="{{ $taxOption->type }}"
                            {{ ($pos->tax_id ?? $defaultTax?->id) == $taxOption->id ? 'selected' : '' }}>
                        {{ $taxOption->name }} ({{ $taxOption->rate }}%{{ $taxOption->type !== 'none' ? ', ' . ucfirst($taxOption->type) : '' }})
                    </option>
                @endforeach
            </select>
            <div class="text-muted" id="totalTax">
                {{ \App\Helpers\CurrencyHelper::format($summary['taxAmount']) }}
            </div>
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
        $totalReturned = $pos->getTotalReturnedAmount();
        $adjustedBalance = $pos->balance;
    @endphp

    @if($totalReturned > 0)
    <div class="d-flex justify-content-between text-danger mb-2 border-bottom border-light pb-2">
        <div class="fs-5"><i class="ti ti-package-import me-1"></i>{{ __('messages.returned') }}</div>
        <div class="fs-5 fw-bold">- {{ \App\Helpers\CurrencyHelper::format($totalReturned) }}</div>
    </div>
    @endif

    <div class="d-flex justify-content-between text-success mb-2">
        <span><i class="ti ti-wallet me-1"></i>{{ __('messages.total_paid') }}</span>
        <span id="total-paid-amount" data-total-paid="{{ $pos->total_paid }}">{{ \App\Helpers\CurrencyHelper::format($pos->total_paid) }}</span>
    </div>
    <div class="d-flex justify-content-between text-danger fw-bold">
        <span><i class="ti ti-wallet me-1"></i>{{ __('messages.balance') }}</span>
        <span id="balance-amount" 
              data-balance="{{ $adjustedBalance }}" 
              data-returned="{{ $totalReturned }}"
              data-original-total="{{ $pos->total }}"
              data-total-paid="{{ $pos->total_paid }}">
            {{ \App\Helpers\CurrencyHelper::format($adjustedBalance) }}
        </span>
    </div>
    <input type="hidden" id="totalDiscountInput" name="total_discount" value="{{ $summary['orderDiscount'] }}">
    <input type="hidden" id="taxInput" name="tax_amount" value="{{ $summary['taxAmount'] }}">
    <input type="hidden" id="taxTypeInput" name="tax_type" value="{{ $pos->tax_type ?? ($defaultTax->type ?? 'none') }}">
    <input type="hidden" id="taxRateInput" name="tax_rate" value="{{ $pos->tax_rate ?? 0 }}">
</div>
