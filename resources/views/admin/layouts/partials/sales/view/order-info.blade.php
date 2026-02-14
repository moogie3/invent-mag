<div class="h-100">
    <h5 class="card-title mb-3 text-secondary">
        <i class="ti ti-calendar-event me-2 text-muted"></i>{{ __('messages.order_information') }}
    </h5>
    <div class="d-flex justify-content-between mb-2">
        <div><strong>{{ __('messages.order_date') }}:</strong></div>
        <div>{{ $sales->order_date->format('d F Y') }}</div>
    </div>
    <div class="d-flex justify-content-between mb-2">
        <div><strong>{{ __('messages.due_date') }}:</strong></div>
        <div>{{ $sales->due_date->format('d F Y') }}</div>
    </div>
    <div class="d-flex justify-content-between mb-2">
        <div><strong>{{ __('messages.payment_type') }}:</strong></div>
        <div>{{ $sales->payment_type ?? __('messages.not_available') }}</div>
    </div>
    @if ($sales->status === 'Paid' && $sales->payments->isNotEmpty())
        <div class="d-flex justify-content-between mb-2">
            <div><strong>{{ __('messages.payment_date') }}</strong></div>
            <div>
                {{ $sales->payments->last()->payment_date->format('d F Y H:i') }}
            </div>
        </div>
    @endif
    <div class="d-flex justify-content-between mb-2">
        <div><strong>{{ __('messages.total_paid') }}</strong></div>
        <div>{{ \App\Helpers\CurrencyHelper::formatWithPosition($sales->total_paid) }}</div>
    </div>
    <div class="d-flex justify-content-between mb-2">
        <div><strong>{{ __('messages.balance') }}</strong></div>
        <div class="text-danger">{{ \App\Helpers\CurrencyHelper::formatWithPosition($sales->balance) }}</div>
    </div>
</div>
