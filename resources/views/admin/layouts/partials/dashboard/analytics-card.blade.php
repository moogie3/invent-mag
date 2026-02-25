<div class="card shadow-sm border-1 mb-4">
    <div class="card-status-top bg-{{ $color }}"></div>
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="ti {{ $icon }} fs-3 me-2 text-{{ $color }}"></i> {{ $title }}
        </h3>
        <a href="{{ $route }}" class="btn btn-sm btn-icon" title="{{ __('messages.view_all_items', ['type' => ucfirst($type)]) }}">
            <i class="ti ti-external-link"></i>
        </a>
    </div>

    <!-- Analytics Section -->
    <div class="card-body p-3 border-bottom">
        @include('admin.layouts.partials.dashboard.analytics-stats', compact('analytics', 'type'))
        @include('admin.layouts.partials.dashboard.analytics-metrics', compact('analytics', 'type'))
    </div>

    <!-- Top Items Table Section -->
    <div class="card-body p-0" style="min-height: 240px;">
        <div class="text-center py-2">
            <h5 class="text-muted">{{ __('messages.top_items', ['type' => ucfirst($type)]) }}</h5>
        </div>
        @include('admin.layouts.partials.dashboard.analytics-table', compact('analytics', 'type'))
    </div>
</div>
