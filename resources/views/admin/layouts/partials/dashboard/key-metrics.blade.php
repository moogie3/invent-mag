@foreach ($keyMetrics as $metric)
    @include('admin.layouts.partials.dashboard.metric-card', compact('metric'))
@endforeach
