@unless ($metric['route'])
    <div class="text-muted d-flex justify-content-between align-items-center mt-2">
        @include('admin.partials.dashboard.trend-badge', ['metric' => $metric])
    </div>
@endunless
