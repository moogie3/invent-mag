<div class="row g-4 mb-4">
    @foreach ($keyMetrics as $metric)
        @include('admin.layouts.partials.dashboard.metric-card', compact('metric'))
    @endforeach
</div>
