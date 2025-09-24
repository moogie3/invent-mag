<div class="card border">
    <div class="card-body p-3">
        <h5 class="mb-3 card-title">{{ __('messages.amount_summary') }}</h5>
        <div class="d-flex justify-content-between mb-2">
            <div>{{ __('messages.subtotal') }}:</div>
            <div>{{ \App\Helpers\CurrencyHelper::format($subtotal) }}</div>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <div>
                {{ __('messages.order_discount') }}
                <small class="text-muted">
                    ({{ ($sales->order_discount_type ?? 'fixed') === 'percentage' ? ($sales->order_discount ?? 0) . '%' : __('messages.fixed') }})
                </small>:
            </div>
            <div class="text-danger">
                {{ \App\Helpers\CurrencyHelper::format($orderDiscount) }}
            </div>
        </div>
        <div class="d-flex justify-content-between mb-2">
                <div>
                    {{ __('messages.tax') }}
                    <small class="text-muted">
                        ({{ $sales->tax_rate ?? 0 }}%)
                    </small>:
                </div>
                <div>
                    {{ \App\Helpers\CurrencyHelper::format($taxAmount) }}
                </div>
            </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center">
            <div class="fs-5"><strong>{{ __('messages.grand_total') }}:</strong></div>
            <div class="fs-3 fw-bold text-primary">
                {{ \App\Helpers\CurrencyHelper::format($finalTotal) }}
            </div>
        </div>
    </div>
</div>
