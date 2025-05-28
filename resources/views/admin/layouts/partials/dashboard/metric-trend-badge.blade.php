@unless ($metric['route'])
    <div class="text-muted d-flex justify-content-between align-items-center mt-2">
        @include('admin.layouts.partials.dashboard.trend-badge', ['metric' => $metric])
    </div>
@endunless
