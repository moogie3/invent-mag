<div class="h-100">
    <h5 class="card-title mb-3 text-secondary">
        <i class="ti ti-calendar-event me-2 text-muted"></i>{{ __('messages.po_order_information_title') }}
    </h5>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('messages.po_order_date') }}</label>
                <div class="form-control-plaintext">{{ $pos->order_date->format('d F Y') }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('messages.po_due_date') }}</label>
                <div class="form-control-plaintext">{{ $pos->due_date->format('d F Y') }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('messages.po_payment_type') }}</label>
                <div class="form-control-plaintext">{{ $pos->payment_type }}</div>
            </div>
            @if ($pos->status === 'Paid' && $pos->payments->isNotEmpty())
            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('messages.payment_date') }}</label>
                <div class="form-control-plaintext">{{ $pos->payments->last()->payment_date->format('d F Y H:i') }}</div>
            </div>
            @endif
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('messages.total_paid') }}</label>
                <div class="form-control-plaintext text-success">{{ \App\Helpers\CurrencyHelper::formatWithPosition($pos->total_paid) }}</div>
            </div>
        </div>
        <div class="col-md-6">
             <div class="mb-3">
                <label class="form-label fw-bold">{{ __('messages.balance') }}</label>
                <div class="form-control-plaintext text-danger">{{ \App\Helpers\CurrencyHelper::formatWithPosition($pos->balance) }}</div>
            </div>
        </div>
    </div>
</div>
