<div class="tab-pane active show" id="tab-financial" role="tabpanel">
    @if ($financialNotifications->count() > 0)
        <div class="list-group">
            @foreach ($financialNotifications as $item)
                <a href="{{ $item['route'] }}" class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="status-indicator {{ $item['status_badge'] }}"
                                style="width: 4px; height: 24px; border-radius: 2px; margin-right: 12px;">
                            </div>
                            <div>
                                <span class="fw-bold">{{ ucfirst($item['type']) }} - {{ $item['label'] }}</span>
                                <small class="text-muted d-block">Due on
                                    {{ \Carbon\Carbon::parse($item['due_date'])->format('d M Y') }}</small>
                            </div>
                        </div>
                        <span class="badge {{ str_replace('text-', 'bg-', $item['status_badge']) }}-lt">
                            <i class="{{ $item['status_icon'] }} me-1"></i>{{ $item['status_text'] }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="text-muted text-center">No financial notifications</div>
    @endif
</div>
