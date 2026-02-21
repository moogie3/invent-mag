<div class="tab-pane" id="tab-expiring" role="tabpanel">
    @if ($expiringNotifications->count() > 0)
        <div class="list-group list-group-flush list-group-hoverable">
            @foreach ($expiringNotifications as $item)
                <div class="list-group-item notification-item bg-white p-3 mb-2 rounded border shadow-sm transition-all" id="notif-{{ md5($item['id']) }}">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar {{ str_replace('text-', 'bg-', $item['status_badge']) }}-lt">
                                <i class="{{ $item['status_icon'] }} fs-2"></i>
                            </span>
                        </div>
                        <div class="col text-truncate">
                            <a href="{{ $item['route'] }}" class="text-reset d-block text-decoration-none">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold fs-3">{{ $item['title'] }}</span>
                                    <span class="badge {{ str_replace('text-', 'bg-', $item['status_badge']) }}-lt py-1 px-2">
                                        {{ $item['status_text'] }}
                                    </span>
                                </div>
                                <div class="text-secondary d-flex flex-wrap gap-3 mt-1">
                                    <span><i class="ti ti-calendar me-1"></i> {{ $item['description'] }}</span>
                                    <span><i class="ti ti-file-invoice me-1"></i> {{ __('messages.po_id') ?? 'PO ID' }}: <strong>#{{ $item['po_id'] }}</strong></span>
                                    <span><i class="ti ti-hash me-1"></i> {{ __('messages.quantity') ?? 'Quantity' }}: <strong>{{ $item['quantity'] }}</strong></span>
                                    <span class="text-{{ $item['days_remaining'] < 7 ? 'danger' : 'warning' }} fw-medium"><i class="ti ti-clock-stop me-1"></i> {{ __('messages.expiring_in') ?? 'Expiring in' }} {{ $item['days_remaining'] }} {{ __('messages.days') ?? 'days' }}</span>
                                </div>
                            </a>
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
            <div class="empty-img"><i class="ti ti-calendar-check text-muted opacity-50" style="font-size: 6rem;"></i></div>
            <p class="empty-title mt-3 fs-3">{{ __('messages.no_expiring_products') }}</p>
            <p class="empty-subtitle text-muted">No products are nearing expiration right now.</p>
        </div>
    @endif
</div>
