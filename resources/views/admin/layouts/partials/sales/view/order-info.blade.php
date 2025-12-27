<div class="card border-0 h-100">
    <div class="card-body p-3">
        <h4 class="card-title mb-3">
            <i class="ti ti-calendar-event me-2 text-info"></i>{{ __('messages.order_information') }}
        </h4>
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
            <div>{{ \App\Helpers\CurrencyHelper::formatWithPosition($sales->balance) }}</div>
        </div>
    </div>
</div>
