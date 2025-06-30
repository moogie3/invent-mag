{{-- Low Stock Tab --}}
<div class="tab-pane" id="tab-lowstock" role="tabpanel">
    @if ($lowStockNotifications->count() > 0)
        <div class="list-group">
            @foreach ($lowStockNotifications as $item)
                <a href="{{ $item['route'] }}" class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="status-indicator {{ $item['status_badge'] }}"
                                style="width: 4px; height: 24px; border-radius: 2px; margin-right: 12px;">
                            </div>
                            <div>
                                <span class="fw-bold">{{ $item['title'] }}</span>
                                <small class="text-muted d-block">
                                    {{ $item['description'] }}
                                    <span class="ms-2 text-muted">
                                        (Threshold: {{ $item['threshold'] }})
                                    </span>
                                </small>
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
        <div class="text-muted text-center">No low stock products</div>
    @endif
</div>
