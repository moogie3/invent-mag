<div class="row mb-3">
    <div class="col-6">
        <div class="text-center">
            <div class="h2 mb-1">{{ $analytics['total' . ucfirst($type) . 's'] ?? 0 }}</div>
            <div class="small text-muted">{{ __('messages.total_items_count', ['type' => ucfirst($type) . 's']) }}</div>
        </div>
    </div>
    <div class="col-6">
        <div class="text-center">
            <div class="h2 mb-1">{{ $analytics['active' . ucfirst($type) . 's'] ?? 0 }}</div>
            <div class="small text-muted">{{ __('messages.active_this_month') }}</div>
        </div>
    </div>
</div>
