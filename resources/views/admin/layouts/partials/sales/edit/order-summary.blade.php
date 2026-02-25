<div class="card border-0">
    <div class="card-body p-3">
        <h5 class="card-title mb-3">
            <i class="ti ti-info-circle me-2 text-primary"></i>{{ __('messages.order_summary') }}
        </h5>

        <div class="mb-2">
            <i class="ti ti-package me-1"></i> {{ __('messages.total_items') }}:
            <strong>{{ $summary['itemCount'] }}</strong>
        </div>
        <div class="mb-2">
            <i class="ti ti-receipt me-1"></i> {{ __('messages.payment_type') }}:
            <strong>{{ $sales->payment_type }}</strong>
        </div>
        @if (property_exists($sales, 'notes') || isset($sales->notes))
            <div class="mt-3">
                <h6><i class="ti ti-notes me-1"></i> {{ __('messages.notes') }}:</h6>
                <textarea name="notes" class="form-control" rows="3">{{ $sales->notes ?? '' }}</textarea>
            </div>
        @endif
    </div>
</div>
