<div class="card border-0">
    <div class="card-body p-3">
        <h5 class="card-title mb-3">
            <i class="ti ti-info-circle me-2 text-primary"></i>{{ __('messages.po_order_summary_title') }}
        </h5>
        <div class="mb-2">
            <i class="ti ti-package me-1"></i> {{ __('messages.po_total_items') }}
            <strong>{{ $itemCount }}</strong>
        </div>
        <div class="mb-2">
            <i class="ti ti-receipt me-1"></i> {{ __('messages.po_payment_type') }}
            <strong>{{ $pos->payment_type }}</strong>
        </div>
        @if (property_exists($pos, 'notes') && $pos->notes)
            <div class="mt-3">
                <h6>{{ __('messages.po_notes') }}</h6>
                <p class="text-muted">{{ $pos->notes }}</p>
            </div>
        @endif
    </div>
</div>
