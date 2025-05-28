<div class="progress" style="height: 5px;">
    <div class="progress-bar {{ $metric['bar_color'] }}" style="width: {{ $metric['percentage'] }}%;"></div>
</div>
<div class="text-muted d-flex justify-content-between align-items-center">
    <span>
        of
        {{ $metric['format'] === 'currency' ? \App\Helpers\CurrencyHelper::format($metric['total']) : $metric['total'] }}
    </span>
    @unless ($metric['route'])
        @include('admin.layouts.partials.dashboard.trend-badge', ['metric' => $metric])
    @endunless
</div>
