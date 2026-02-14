<div class="h-100 p-4 bg-primary-lt rounded-3">
    <h5 class="mb-3 card-title text-primary">{{ __('messages.po_amount_summary_title') }}</h5>
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
        <div class="fs-4">{{ __('messages.returned') }}</div>
        <div class="fs-4">- {{ \App\Helpers\CurrencyHelper::format($totalReturned) }}</div>
    </div>
    @endif

    <div class="d-flex justify-content-between text-success mb-1">
        <div class="fs-4">{{ __('messages.total_paid') }}</div>
        <div class="fs-4">{{ \App\Helpers\CurrencyHelper::format($pos->total_paid) }}</div>
    </div>
    <div class="d-flex justify-content-between text-danger">
        <div class="fs-4">{{ __('messages.balance') }}</div>
        <div class="fs-4">{{ \App\Helpers\CurrencyHelper::format($adjustedBalance) }}</div>
    </div>
</div>
