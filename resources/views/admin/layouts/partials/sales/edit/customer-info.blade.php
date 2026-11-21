<div class="h-100">
    <h5 class="card-title mb-3 text-secondary">
        <i class="ti ti-users me-2 text-muted"></i>{{ __('messages.customer') }}
    </h5>
    {{-- A walk-in POS sale carries no customer at all, so the field posts
         empty and the sale stays customer less through the edit. --}}
    <input type="hidden" name="customer_id" value="{{ $sales->customer?->id }}">
    @if ($sales->customer)
        <h4 class="mb-2 fw-bold">{{ $sales->customer->name }}</h4>
        <div class="text-muted mb-1">
            <i class="ti ti-map-pin me-2"></i>
            {{ $sales->customer->address }}
        </div>
        <div class="text-muted mb-1">
            <i class="ti ti-phone me-2"></i>
            {{ $sales->customer->phone_number }}
        </div>
    @else
        <h4 class="mb-2 fw-bold">{{ __('messages.walk_in_customer') }}</h4>
    @endif
</div>
