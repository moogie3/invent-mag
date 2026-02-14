<div class="h-100 p-4 bg-light rounded-3">
    <h5 class="card-title mb-3 text-primary">
        <i class="ti ti-info-circle me-2"></i>{{ __('messages.order_summary_title') }}
    </h5>
    <div class="mb-2">
        <i class="ti ti-package me-1"></i> {{ __('messages.total_items') }}
        <strong>{{ $itemCount }}</strong>
    </div>
    <div class="mb-2">
        <i class="ti ti-receipt me-1"></i> {{ __('messages.payment_type') }}
        <strong>{{ $pos->payment_type }}</strong>
    </div>
    @if (property_exists($pos, 'notes') && $pos->notes)
        <div class="mt-3">
            <h6 class="fw-bold"><i class="ti ti-notes me-1"></i>{{ __('messages.po_notes') }}</h6>
            <p class="text-muted">{{ $pos->notes }}</p>
        </div>
    @endif
</div>
