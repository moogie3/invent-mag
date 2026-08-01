<div class="h-100 p-4 bg-primary-lt rounded-3">
    <h5 class="mb-3 card-title text-primary">{{ __('messages.po_amount_summary_title') }}</h5>
    
    @php
        $adjustedBalance = $pos->balance;
    @endphp

    <div class="d-flex justify-content-between mb-2">
        <div>{{ __('messages.po_subtotal') }}</div>
        <div>{{ \App\Helpers\CurrencyHelper::format($subtotal) }}</div>
    </div>
    <div class="d-flex justify-content-between mb-2">
        <div>
            {{ __('messages.po_order_discount') }}
            <small class="text-muted">
                ({{ $pos->discount_total_type === 'percentage' ? $pos->discount_total . '%' : __('messages.po_fixed') }})
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
                ({{ $pos->tax_rate ?? 0 }}%@if(($pos->tax_type ?? 'none') !== 'none'), {{ $pos->tax_type }}@endif)
            </small>
        </div>
        <div>
            {{ \App\Helpers\CurrencyHelper::format($taxAmount) }}
        </div>
    </div>
    @php
        $currentTenant = app('currentTenant');
        $showPph = $currentTenant instanceof \App\Models\Tenant && $currentTenant->isIndonesian() && $currentTenant->hasFeature('ppn');
    @endphp
    @if($showPph && $pos->isPph())
    <div class="d-flex justify-content-between mb-2">
        <span class="text-muted">{{ __('messages.withholding_tax') }} ({{ $pos->pph_rate }}%)</span>
        <span class="fw-bold text-danger">-{{ \App\Helpers\CurrencyHelper::format($pos->pph_amount) }}</span>
    </div>
    @endif
    <hr class="border-primary">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="fs-4"><strong>{{ __('messages.po_grand_total') }}</strong></div>
        <div class="fs-3 fw-bold text-primary">
            {{ \App\Helpers\CurrencyHelper::format($finalTotal) }}
        </div>
    </div>

    @php
        $totalReturned = $pos->getTotalReturnedAmount();
    @endphp

    @if($totalReturned > 0)
    <div class="d-flex justify-content-between text-muted mb-2 border-bottom border-light pb-2">
        <div class="fs-5"><i class="ti ti-package-import me-1"></i>{{ __('messages.returned') }} ({{ __('messages.po_totals_adjusted') }})</div>
        <div class="fs-5">{{ \App\Helpers\CurrencyHelper::format($totalReturned) }}</div>
    </div>
    @endif

    <div class="d-flex justify-content-between text-success mb-1">
        <div class="fs-4"><i class="ti ti-wallet me-1"></i>{{ __('messages.total_paid') }}</div>
        <div class="fs-4">{{ \App\Helpers\CurrencyHelper::format($pos->total_paid) }}</div>
    </div>
    <div class="d-flex justify-content-between text-danger">
        <div class="fs-4"><i class="ti ti-wallet me-1"></i>{{ __('messages.balance') }}</div>
        <div class="fs-4">{{ \App\Helpers\CurrencyHelper::format($adjustedBalance) }}</div>
    </div>
</div>
