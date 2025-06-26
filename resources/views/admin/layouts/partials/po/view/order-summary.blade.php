<div class="card border-0">
    <div class="card-body p-3">
        <h5 class="card-title mb-3">
            <i class="ti ti-info-circle me-2 text-primary"></i>Order Summary
        </h5>
        <div class="mb-2">
            <i class="ti ti-package me-1"></i> Total Items:
            <strong>{{ $itemCount }}</strong>
        </div>
        <div class="mb-2">
            <i class="ti ti-receipt me-1"></i> Payment Type:
            <strong>{{ $pos->payment_type }}</strong>
        </div>
        @if (property_exists($pos, 'notes') && $pos->notes)
            <div class="mt-3">
                <h6>Notes:</h6>
                <p class="text-muted">{{ $pos->notes }}</p>
            </div>
        @endif
    </div>
</div>
