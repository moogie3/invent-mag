<span class="badge {{ $metric['badge_class'] }}">
    @if ($metric['trend_icon'])
        <i class="{{ $metric['trend_icon'] }} me-1"></i>
    @endif
    {{ $metric['trend_label'] }}
</span>
