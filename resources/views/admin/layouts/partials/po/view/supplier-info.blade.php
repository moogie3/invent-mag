<div class="h-100">
    <h5 class="card-title mb-3 text-secondary">
        <i class="ti ti-building-store me-2 text-muted"></i>{{ __('messages.supplier') }}
    </h5>
    <h4 class="mb-2 fw-bold">{{ $pos->supplier->name }}</h4>
    <div class="text-muted mb-1">
        <i class="ti ti-map-pin me-1"></i>
        {{ $pos->supplier->address }}
    </div>
    <div class="text-muted mb-1">
        <i class="ti ti-phone me-1"></i>
        {{ $pos->supplier->phone_number }}
    </div>
</div>
