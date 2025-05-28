<div class="card shadow-sm border-1 mb-4">
    <div class="card-status-top bg-danger"></div>
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="ti ti-alert-circle fs-3 me-2 text-danger"></i> System Alerts</h3>
    </div>
    <div class="card-body p-2">
        <div class="row g-2">
            @foreach ([['type' => 'Low Stock', 'count' => $lowStockCount, 'color' => 'red', 'icon' => 'ti-alert-triangle', 'route' => route('admin.product', ['low_stock' => 1]), 'action' => 'Take action'], ['type' => 'Exp Soon', 'count' => $expiringSoonCount, 'color' => 'yellow', 'icon' => 'ti-calendar-event', 'route' => route('admin.product', ['expiring_soon' => 1]), 'action' => 'Review items']] as $alert)
                <div class="col-6">
                    <div class="card bg-{{ $alert['color'] }}-lt py-2 px-3">
                        <div class="d-flex align-items-center mb-1">
                            <div class="avatar avatar-sm bg-{{ $alert['color'] }}-lt text-{{ $alert['color'] }} me-2">
                                <i class="ti {{ $alert['icon'] }}"></i>
                            </div>
                            <div class="fw-semibold">{{ $alert['type'] }}</div>
                        </div>
                        <div class="h3 m-0 text-center">{{ $alert['count'] }}</div>
                        <div class="text-center">
                            <a href="{{ $alert['route'] }}"
                                class="small text-decoration-none">{{ $alert['action'] }}</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
