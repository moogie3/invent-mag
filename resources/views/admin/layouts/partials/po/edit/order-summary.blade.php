<div class="card border-0">
    <div class="card-body p-3">
        <h5 class="card-title mb-3">
            <i class="ti ti-info-circle me-2 text-primary"></i>Order Summary
        </h5>

        <div class="mb-2">
            <i class="ti ti-package me-1"></i> Total Items:
            <strong>{{ $summary['itemCount'] }}</strong>
        </div>
        <div class="mb-2">
            <i class="ti ti-receipt me-1"></i> Payment Type:
            <strong>{{ $pos->payment_type }}</strong>
        </div>
        @if (property_exists($pos, 'notes'))
            <div class="mt-3">
                <h6><i class="ti ti-notes me-1"></i> Notes:</h6>
                <textarea name="notes" class="form-control" rows="3">{{ $pos->notes ?? '' }}</textarea>
            </div>
        @endif
    </div>
</div>
