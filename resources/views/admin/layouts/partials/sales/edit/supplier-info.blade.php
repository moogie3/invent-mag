<div class="card border-0 h-100">
    <div class="card-body p-3">
        <h4 class="card-title mb-3">
            <i class="ti ti-users me-2 text-primary"></i>Customer
        </h4>
        <h5 class="mb-2">{{ $sales->customer->name }}</h5>
        <div class="text-muted mb-1">
            <i class="ti ti-map-pin me-1"></i>
            {{ $sales->customer->address }}
        </div>
        <div class="text-muted mb-1">
            <i class="ti ti-phone me-1"></i>
            {{ $sales->customer->phone_number }}
        </div>
    </div>
</div>
