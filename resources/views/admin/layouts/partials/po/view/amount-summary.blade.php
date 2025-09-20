<div class="card border">
    <div class="card-body p-3">
        <h5 class="mb-3 card-title">{{ __('messages.po_amount_summary_title') }}</h5>
        <div class="d-flex justify-content-between mb-2">
            <div>{{ __('messages.po_subtotal') }}</div>
            <div>{{ \App\Helpers\CurrencyHelper::format($subtotal) }}</div>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <div>
                {{ __('messages.po_order_discount') }}
                <small class="text-muted">
                    ({{ $pos->discount_total_type === 'percentage' ? $pos->discount_total . '%' : __('messages.po_fixed') }})
                </small>:
            </div>
            <div class="text-danger">-
                {{ \App\Helpers\CurrencyHelper::format($orderDiscount) }}
            </div>
        </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center">
            <div class="fs-5"><strong>{{ __('messages.po_grand_total') }}</strong></div>
            <div class="fs-3 fw-bold text-primary">
                {{ \App\Helpers\CurrencyHelper::format($finalTotal) }}
            </div>
        </div>
    </div>
</div>
