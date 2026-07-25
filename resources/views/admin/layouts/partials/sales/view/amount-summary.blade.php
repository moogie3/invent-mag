<div class="h-100 p-4 bg-primary-lt rounded-3">
    <h5 class="mb-3 card-title text-primary">{{ __('messages.amount_summary_title') }}</h5>
    
    @php
        $adjustedBalance = $sales->balance;
    @endphp

    <div class="d-flex justify-content-between mb-2">
        <div>{{ __('messages.subtotal') }}</div>
        <div>{{ \App\Helpers\CurrencyHelper::format($subtotal) }}</div>
    </div>
    <div class="d-flex justify-content-between mb-2">
        <div>
            {{ __('messages.order_discount') }}
            <small class="text-muted">
                ({{ ($sales->order_discount_type ?? 'fixed') === 'percentage' ? ($sales->order_discount ?? 0) . '%' : __('messages.fixed') }})
            </small>
        </div>
        <div class="text-danger">-
            {{ \App\Helpers\CurrencyHelper::format($orderDiscount) }}
        </div>
    </div>
    <div class="d-flex justify-content-between mb-2">
        <div>
            {{ __('messages.tax') }}
            <small class="text-muted">
                ({{ $sales->tax_rate ?? 0 }}%@if(($sales->tax_type ?? 'none') !== 'none'), {{ $sales->tax_type }}@endif)
            </small>
        </div>
        <div>
            {{ \App\Helpers\CurrencyHelper::format($taxAmount) }}
        </div>
    </div>
    <hr class="border-primary">

    @php
        $currentTenant = app('currentTenant');
        $showPpnLine = $currentTenant instanceof \App\Models\Tenant && $currentTenant->isIndonesian() && $currentTenant->hasFeature('ppn');
    @endphp
    @if($showPpnLine)
    @if($sales->ppn_enabled)
    <div class="d-flex justify-content-between mb-2 text-danger">
        <div>
            PPN
            <small class="text-muted">({{ $sales->ppn_rate ?? app('currentTenant')->ppn_rate ?? 11 }}%)</small>
        </div>
        <div>{{ \App\Helpers\CurrencyHelper::format($sales->ppn_amount) }}</div>
    </div>
    @else
    <div class="d-flex justify-content-between mb-2 text-muted">
        <div><small>PPN (belum diaktifkan)</small></div>
        <div><small>—</small></div>
    </div>
    @endif
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="fs-4"><strong>{{ __('messages.grand_total') }}</strong></div>
        <div class="fs-3 fw-bold text-primary">
            {{ \App\Helpers\CurrencyHelper::format($finalTotal) }}
        </div>
    </div>

    @php
        $totalReturned = $sales->getTotalReturnedAmount();
    @endphp

    @if($totalReturned > 0)
    <div class="d-flex justify-content-between text-muted mb-2 border-bottom border-light pb-2">
        <div class="fs-5"><i class="ti ti-package-export me-1"></i>{{ __('messages.returned') }} ({{ __('messages.sales_totals_adjusted') }})</div>
        <div class="fs-5">{{ \App\Helpers\CurrencyHelper::format($totalReturned) }}</div>
    </div>
    @endif

    @if(($sales['customer_credit_used'] ?? 0) > 0)
    <div class="d-flex justify-content-between text-info mb-1">
        <div class="fs-5"><i class="ti ti-credit-card me-1"></i>{{ __('messages.credit_used') }}</div>
        <div class="fs-5">{{ \App\Helpers\CurrencyHelper::format($sales['customer_credit_used'] ?? 0) }}</div>
    </div>
    @endif

    <div class="d-flex justify-content-between text-success mb-1">
        <div class="fs-4"><i class="ti ti-wallet me-1"></i>{{ __('messages.total_paid') }}</div>
        <div class="fs-4">{{ \App\Helpers\CurrencyHelper::format($sales->total_paid) }}</div>
    </div>
    <div class="d-flex justify-content-between text-danger">
        <div class="fs-4"><i class="ti ti-wallet me-1"></i>{{ __('messages.balance') }}</div>
        <div class="fs-4">{{ \App\Helpers\CurrencyHelper::format($adjustedBalance) }}</div>
    </div>
</div>
