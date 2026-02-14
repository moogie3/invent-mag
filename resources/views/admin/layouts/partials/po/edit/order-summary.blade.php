<div class="h-100 p-4 bg-light rounded-3">
    <h5 class="card-title mb-3 text-primary">
        <i class="ti ti-info-circle me-2"></i>{{ __('messages.po_order_summary_title') }}
    </h5>

    <div class="mb-2">
        <i class="ti ti-package me-1"></i> {{ __('messages.po_total_items') }}
        <strong>{{ $summary['itemCount'] }}</strong>
    </div>
    <div class="mb-2">
        <i class="ti ti-receipt me-1"></i> {{ __('messages.po_payment_type') }}
        <strong>{{ $pos->payment_type }}</strong>
    </div>
    @if (property_exists($pos, 'notes'))
        <div class="mt-3">
            <label class="form-label fw-bold"><i class="ti ti-notes me-1"></i> {{ __('messages.po_notes') }}</label>
            <textarea name="notes" class="form-control" rows="3">{{ $pos->notes ?? '' }}</textarea>
        </div>
    @endif
</div>
