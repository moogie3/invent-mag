<div class="card shadow-sm border-1 mb-4">
    <div class="card-status-top bg-{{ $color }}"></div>
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="ti {{ $icon }} fs-3 me-2 text-{{ $color }}"></i> {{ $title }}
        </h3>
        <a href="{{ $route }}" class="btn btn-sm btn-icon" title="View All {{ ucfirst($type) }}s">
            <i class="ti ti-external-link"></i>
        </a>
    </div>

    <!-- Analytics Section -->
    <div class="card-body p-3 border-bottom">
        @include('admin.partials.dashboard.analytics-stats', compact('analytics', 'type'))
        @include('admin.partials.dashboard.analytics-metrics', compact('analytics', 'type'))
    </div>

    <!-- Top Items Table Section -->
    <div class="card-body p-0">
        <div class="text-center py-2">
            <h5 class="text-muted">Top {{ ucfirst($type) }}s</h5>
        </div>
        @include('admin.partials.dashboard.analytics-table', compact('analytics', 'type'))
    </div>
</div>
