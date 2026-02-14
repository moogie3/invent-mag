<div class="h-100">
    <h5 class="card-title mb-3 text-secondary">
        <i class="ti ti-calendar-event me-2 text-muted"></i>{{ __('messages.po_order_information_title') }}
    </h5>
    <div class="d-flex justify-content-between mb-2">
        <div><strong>{{ __('messages.po_order_date') }}</strong></div>
        <div>{{ $pos->order_date->format('d F Y') }}</div>
    </div>
    <div class="d-flex justify-content-between mb-2">
        <div><strong>{{ __('messages.po_due_date') }}</strong></div>
        <div>{{ $pos->due_date->format('d F Y') }}</div>
    </div>
    <div class="d-flex justify-content-between mb-2">
        <div><strong>{{ __('messages.po_payment_type') }}</strong></div>
        <div>{{ $pos->payment_type }}</div>
    </div>
    @if ($pos->status === 'Paid' && $pos->payments->isNotEmpty())
        <div class="d-flex justify-content-between mb-2">
            <div><strong>{{ __('messages.payment_date') }}</strong></div>
            <div>
                {{ $pos->payments->last()->payment_date->format('d F Y H:i') }}
            </div>
        </div>
    @endif
    <div class="d-flex justify-content-between mb-2">
        <div><strong>{{ __('messages.total_paid') }}</strong></div>
        <div>{{ \App\Helpers\CurrencyHelper::formatWithPosition($pos->total_paid) }}</div>
    </div>
    <div class="d-flex justify-content-between mb-2">
        <div><strong>{{ __('messages.balance') }}</strong></div>
        <div class="text-danger">{{ \App\Helpers\CurrencyHelper::formatWithPosition($pos->balance) }}</div>
    </div>
</div>
