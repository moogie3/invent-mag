<div class="card border-0">
    <div class="card-body p-3">
        <h5 class="card-title mb-3"><i class="ti ti-info-circle me-2 text-info"></i>Order Summary</h5>
        <div class="mb-2">
            <i class="ti ti-package me-1"></i> Total Items:
            <strong>{{ $itemCount }}</strong>
        </div>
        <div class="mb-2">
            <i class="ti ti-receipt me-1"></i> Payment Type:
            <strong>{{ $sales->payment_type ?? 'N/A' }}</strong>
        </div>
        @if (property_exists($sales, 'notes') && $sales->notes)
            <div class="mt-3">
                <h6>Notes:</h6>
                <p class="text-muted">{{ $sales->notes }}</p>
            </div>
        @endif
    </div>
</div>
