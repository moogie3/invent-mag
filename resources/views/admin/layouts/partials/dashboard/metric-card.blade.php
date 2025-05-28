<div class="col-md-6 col-lg-3">
    <div class="card h-100 border-1 rounded-2">
        <div class="card-body d-flex flex-column gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-secondary-lt"
                    style="width: 40px; height: 40px;">
                    <i class="ti {{ $metric['icon'] }} fs-2"></i>
                </div>
                <div>{{ $metric['title'] }}</div>
            </div>

            <div class="fs-3 fw-bold">
                {{ $metric['format'] === 'currency' ? \App\Helpers\CurrencyHelper::format($metric['value']) : $metric['value'] }}
            </div>

            @if ($metric['total'] !== null && $metric['bar_color'])
                @include('admin.layouts.partials.dashboard.metric-progress-bar', compact('metric'))
            @else
                @include('admin.layouts.partials.dashboard.metric-trend-badge', compact('metric'))
            @endif

            @if ($metric['route'])
                @include('admin.layouts.partials.dashboard.metric-view-details', compact('metric'))
            @endif
        </div>
    </div>
</div>
