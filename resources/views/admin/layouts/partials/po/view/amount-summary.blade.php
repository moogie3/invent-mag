<div class="h-100 p-4 bg-primary-lt rounded-3">
    <h5 class="mb-3 card-title text-primary">{{ __('messages.po_amount_summary_title') }}</h5>
    <div class="d-flex justify-content-between mb-2">
        <div>{{ __('messages.po_subtotal') }}</div>
        <div>{{ \App\Helpers\CurrencyHelper::format($subtotal) }}</div>
    </div>
    <div class="d-flex justify-content-between mb-2">
        <div>
            <span>{{ __('messages.po_order_discount') }}</span>
            <small class="text-muted">
                ({{ $pos->discount_total_type === 'percentage' ? $pos->discount_total . '%' : __('messages.po_fixed') }})
            </small>:
        </div>
        <div class="text-danger">-
            {{ \App\Helpers\CurrencyHelper::format($orderDiscount) }}
        </div>
    </div>
    <hr class="border-primary">
    <div class="d-flex justify-content-between align-items-center">
        <div class="fs-4"><strong>{{ __('messages.po_grand_total') }}</strong></div>
        <div class="fs-3 fw-bold text-primary">
            {{ \App\Helpers\CurrencyHelper::format($finalTotal) }}
        </div>
    </div>

    @php
        // Calculate total completed returns
        $totalReturned = $pos->purchaseReturns->where('status', 'Completed')->sum('total_amount');
        $adjustedBalance = max(0, $pos->balance - $totalReturned);
    @endphp

    @if($totalReturned > 0)
    <div class="d-flex justify-content-between text-warning mb-2">
        <span>{{ __('messages.returned') }}</span>
        <span>- {{ \App\Helpers\CurrencyHelper::format($totalReturned) }}</span>
    </div>
    @endif

    <div class="d-flex justify-content-between text-success mb-2">
        <span>{{ __('messages.total_paid') }}</span>
        <span>{{ \App\Helpers\CurrencyHelper::format($pos->total_paid) }}</span>
    </div>
    <div class="d-flex justify-content-between text-danger fw-bold">
        <span>{{ __('messages.balance') }}</span>
        <span>{{ \App\Helpers\CurrencyHelper::format($adjustedBalance) }}</span>
    </div>
</div>
