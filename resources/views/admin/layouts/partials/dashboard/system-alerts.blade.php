<div class="card shadow-sm border-1">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <h3 class="card-title mb-0">
            <i class="ti ti-bell-ringing fs-3 me-2"></i> {{ __('messages.system_alerts') }}
        </h3>
        <a href="{{ route('admin.product') }}" class="btn btn-sm btn-ghost-secondary">{{ __('messages.view_all') }}</a>
    </div>

    <div class="card-body p-0">
        <ul class="nav nav-tabs nav-fill" id="alertTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active py-3" id="low-stock-tab" data-bs-toggle="tab"
                    data-bs-target="#low-stock-content" type="button" role="tab">
                    <span class="badge bg-danger-lt me-2">{{ $lowStockProducts->count() }}</span> {{ __('messages.low_stock') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3" id="expiring-soon-tab" data-bs-toggle="tab"
                    data-bs-target="#expiring-soon-content" type="button" role="tab">
                    <span class="badge bg-warning-lt me-2">{{ $expiringSoonItems->count() }}</span> {{ __('messages.expiring_soon') }}
                </button>
            </li>
        </ul>

        <div class="tab-content" id="alertTabContent" style="height: 380px; overflow-y: auto;">
            <!-- Low Stock Section -->
            <div class="tab-pane fade show active" id="low-stock-content" role="tabpanel">
                <div class="list-group list-group-flush">
                    @forelse ($lowStockProducts as $item)
                        @php $product = $item->product; @endphp
                        <a href="{{ route('admin.product.edit', $product->id) }}" class="list-group-item list-group-item-action py-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="avatar avatar-md rounded border bg-light flex-shrink-0" style="width: 48px; height: 48px;">
                                    @if (empty($product->image) || strtolower($product->image) === 'null' || strtolower($product->image) === 'undefined' || $product->image === asset('img/default_placeholder.png'))
                                        <i class="ti ti-photo text-muted fs-2"></i>
                                    @else
                                        <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" class="rounded" style="width: 100%; height: 100%; object-fit: cover;">
                                    @endif
                                </div>
                                <div class="flex-grow-1 min-width-0">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div class="fw-bold text-truncate" title="{{ $product->name }}">{{ $product->name }}</div>
                                        <div class="text-danger fw-bold ms-2">{{ (int)$item->quantity }}</div>
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="badge bg-secondary-lt" style="font-size: 10px;">{{ $item->warehouse->name ?? 'Unknown' }}</span>
                                        <span class="text-muted small">/ {{ __('messages.threshold') }}: {{ $product->low_stock_threshold ?? 10 }}</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="ti ti-check fs-1 opacity-20 d-block mb-2"></i>
                            {{ __('messages.no_low_stock_products') }}
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Expiring Soon Section -->
            <div class="tab-pane fade" id="expiring-soon-content" role="tabpanel">
                <div class="list-group list-group-flush">
                    @forelse ($expiringSoonItems as $item)
                        @php
                            $product = $item->product;
                            $daysLeft = (int)now()->diffInDays($item->expiry_date, false);
                            $badgeClass = 'bg-success-lt';
                            $statusText = $daysLeft . ' ' . __('messages.days');

                            if ($daysLeft <= 0) {
                                $badgeClass = 'bg-danger-lt';
                                $statusText = __('messages.expired');
                            } elseif ($daysLeft <= 7) {
                                $badgeClass = 'bg-danger-lt';
                            } elseif ($daysLeft <= 30) {
                                $badgeClass = 'bg-warning-lt';
                            }
                        @endphp
                        <a href="{{ route('admin.po.edit', $item->po_id) }}" class="list-group-item list-group-item-action py-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="avatar avatar-md rounded border bg-light flex-shrink-0" style="width: 48px; height: 48px;">
                                    @if (empty($product->image) || strtolower($product->image) === 'null' || strtolower($product->image) === 'undefined' || $product->image === asset('img/default_placeholder.png'))
                                        <i class="ti ti-photo text-muted fs-2"></i>
                                    @else
                                        <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" class="rounded" style="width: 100%; height: 100%; object-fit: cover;">
                                    @endif
                                </div>
                                <div class="flex-grow-1 min-width-0">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div class="fw-bold text-truncate" title="{{ $product->name }}">{{ $product->name }}</div>
                                        <div class="fw-semibold small text-nowrap ms-2">{{ $item->expiry_date->format('d M Y') }}</div>
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="badge bg-secondary-lt" style="font-size: 10px;">{{ $item->purchaseOrder->warehouse->name ?? 'N/A' }}</span>
                                        <span class="badge {{ $badgeClass }}" style="font-size: 10px;">{{ $statusText }}</span>
                                        <span class="text-muted small ms-auto">Qty: {{ (int)$item->quantity }}</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="ti ti-calendar-check fs-1 opacity-20 d-block mb-2"></i>
                            {{ __('messages.no_expiring_products') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
