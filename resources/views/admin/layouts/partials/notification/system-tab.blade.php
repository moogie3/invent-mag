<div class="tab-pane" id="tab-system" role="tabpanel">
    @if (isset($systemNotifications) && $systemNotifications->count() > 0)
        <div class="list-group list-group-flush list-group-hoverable">
            @foreach ($systemNotifications as $item)
                <div class="list-group-item notification-item bg-white p-3 mb-2 rounded border shadow-sm transition-all" id="notif-{{ md5($item['id']) }}">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar bg-{{ $item['color'] }}-lt">
                                <i class="{{ $item['icon'] }} fs-2"></i>
                            </span>
                        </div>
                        <div class="col text-truncate">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold fs-3 d-flex align-items-center gap-2">
                                    {{ $item['title'] }}
                                    @if ($item['urgency'] === 'critical')
                                        <span class="badge bg-danger p-1"></span>
                                    @elseif ($item['urgency'] === 'high')
                                        <span class="badge bg-warning p-1"></span>
                                    @endif
                                </span>
                                <span class="badge bg-{{ $item['color'] }}-lt py-1 px-2 text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                    {{ str_replace('_', ' ', $item['category'] ?? 'System') }}
                                </span>
                            </div>
                            <div class="text-secondary d-flex flex-wrap gap-3 mt-1 align-items-center">
                                <span class="text-wrap" style="max-width: 100%; white-space: normal;">{{ $item['description'] }}</span>
                                @if (isset($item['action_route']))
                                    <a href="{{ $item['action_route'] }}" class="btn btn-sm btn-{{ $item['color'] === 'warning' || $item['color'] === 'danger' ? $item['color'] . ' text-white' : $item['color'] }} rounded-pill ms-auto">
                                        {{ $item['action_label'] }} <i class="ti ti-arrow-right ms-1"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="col-auto z-index-2 position-relative">
                            <button type="button" class="btn btn-icon btn-outline-secondary btn-pill mark-as-read-btn" data-id="{{ $item['id'] }}" data-target="notif-{{ md5($item['id']) }}" title="Dismiss">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty py-5">
            <div class="empty-img"><i class="ti ti-circle-check text-success opacity-50" style="font-size: 6rem;"></i></div>
            <p class="empty-title mt-3 fs-3">{{ __('plan.notif_no_system') }}</p>
            <p class="empty-subtitle text-muted">You have no pending system alerts.</p>
        </div>
    @endif
</div>
