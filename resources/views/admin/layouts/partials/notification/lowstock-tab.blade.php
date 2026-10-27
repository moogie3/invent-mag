{{-- Low Stock Tab --}}
<div class="tab-pane" id="tab-lowstock" role="tabpanel">
    @if ($lowStockNotifications->count() > 0)
        <div class="list-group list-group-flush list-group-hoverable">
            @foreach ($lowStockNotifications as $item)
                <div class="list-group-item notification-item bg-body p-3 mb-2 rounded border shadow-sm transition-all" id="notif-{{ md5($item['id']) }}">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar bg-red-lt">
                                <i class="ti ti-alert-triangle fs-2 text-danger"></i>
                            </span>
                        </div>
                        <div class="col text-truncate">
                            <a href="{{ $item['route'] }}" class="text-reset d-block text-decoration-none">
                                {{-- The title is a nowrap flex item: without
                                     text-truncate its automatic min-size is
                                     the FULL string width, so a long product
                                     name shoves the badge past the column's
                                     overflow clip and the badge vanishes.
                                     Truncate the text, pin the badge. Same
                                     for the meta row: description ellipsizes,
                                     threshold never gets pushed out. --}}
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                    <span class="fw-bold fs-3 text-truncate">{{ $item['title'] }}</span>
                                    <span class="badge bg-red-lt py-1 px-2 flex-shrink-0">
                                        {{ $item['status_text'] }}
                                    </span>
                                </div>
                                <div class="text-secondary d-flex align-items-center gap-3 mt-1">
                                    <span class="text-truncate"><i class="ti ti-box me-1"></i> {{ $item['description'] }}</span>
                                    <span class="flex-shrink-0"><i class="ti ti-settings me-1"></i> {{ __('messages.threshold') }}: <strong>{{ $item['threshold'] }}</strong></span>
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
            <div class="empty-img"><i class="ti ti-box text-muted opacity-50" style="font-size: 6rem;"></i></div>
            <p class="empty-title mt-3 fs-3">{{ __('messages.no_low_stock_products') }}</p>
            <p class="empty-subtitle text-muted">Your inventory levels are looking healthy.</p>
        </div>
    @endif
</div>
