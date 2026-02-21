<div class="tab-pane active show" id="tab-financial" role="tabpanel">
    @if ($financialNotifications->count() > 0)
        <div class="list-group list-group-flush list-group-hoverable">
            @foreach ($financialNotifications as $item)
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
                                    <span class="fw-bold fs-3">{{ ucfirst($item['type']) }} - {{ $item['label'] }}</span>
                                    <span class="badge {{ str_replace('text-', 'bg-', $item['status_badge']) }}-lt py-1 px-2">
                                        {{ $item['status_text'] }}
                                    </span>
                                </div>
                                <div class="text-secondary">
                                    <i class="ti ti-calendar-event me-1"></i> {{ __('messages.due_on') }}
                                    <strong>{{ \Carbon\Carbon::parse($item['due_date'])->format('d M Y') }}</strong>
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
            <div class="empty-img"><i class="ti ti-receipt text-muted opacity-50" style="font-size: 6rem;"></i></div>
            <p class="empty-title mt-3 fs-3">{{ __('messages.no_financial_notifications') }}</p>
            <p class="empty-subtitle text-muted">You are all caught up on your invoices and purchase orders.</p>
        </div>
    @endif
</div>
