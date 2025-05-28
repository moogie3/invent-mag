<div class="row mb-3">
    <div class="col-6">
        <div class="text-center">
            <div class="h2 mb-1">{{ $analytics['total' . ucfirst($type) . 's'] ?? 0 }}</div>
            <div class="small text-muted">Total {{ ucfirst($type) }}s</div>
        </div>
    </div>
    <div class="col-6">
        <div class="text-center">
            <div class="h2 mb-1">{{ $analytics['active' . ucfirst($type) . 's'] ?? 0 }}</div>
            <div class="small text-muted">Active This Month</div>
        </div>
    </div>
</div>
