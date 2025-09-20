<div class="card border-0 h-100">
    <div class="card-body p-3">
        <h4 class="card-title mb-3">
            <i class="ti ti-calendar-event me-2 text-primary"></i>{{ __('messages.po_order_information_title') }}
        </h4>
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
        @if ($pos->status === 'Paid')
            <div class="d-flex justify-content-between">
                <div><strong>{{ __('messages.po_payment_date') }}</strong></div>
                <div>
                    {{ $pos->payment_date->format('d F Y H:i') }}
                </div>
            </div>
        @endif
    </div>
</div>
