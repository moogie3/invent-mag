<div class="card border-0 h-100">
    <div class="card-body p-3">
        <h4 class="card-title mb-3">
            <i class="ti ti-building-store me-2 text-primary"></i>Supplier
        </h4>
        <input type="hidden" name="supplier_id" value="{{ $pos->supplier->id }}">
        <h4 class="mb-2">{{ $pos->supplier->name }}</h4>
        <div class="text-muted mb-1">
            <i class="ti ti-map-pin me-1"></i>
            {{ $pos->supplier->address }}
        </div>
        <div class="text-muted mb-1">
            <i class="ti ti-phone me-1"></i>
            {{ $pos->supplier->phone_number }}
        </div>
    </div>
</div>
