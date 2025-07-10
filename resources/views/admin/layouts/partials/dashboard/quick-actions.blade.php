<div class="card shadow-sm border-1 mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="ti ti-rocket fs-3 me-2"></i> Quick Actions</h3>
    </div>
    <div class="card-body p-3" style="min-height: 280px;">
        <div class="row g-3">
            @foreach ([['route' => route('admin.sales.create'), 'icon' => 'ti-receipt', 'text' => 'New Sale'], ['route' => route('admin.po.create'), 'icon' => 'ti-shopping-cart', 'text' => 'New Purchase'], ['route' => route('admin.product.create'), 'icon' => 'ti-box', 'text' => 'Add Product'], ['route' => route('admin.notifications'), 'icon' => 'ti-notification', 'text' => 'Notifications'], ['route' => route('admin.pos'), 'icon' => 'ti-cash', 'text' => 'POS'], ['route' => route('admin.transactions'), 'icon' => 'ti-history', 'text' => 'Activity Log']] as $action)
                <div class="col-6">
                    <a href="{{ $action['route'] }}"
                        class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-2">
                        <i class="ti {{ $action['icon'] }} fs-2 mb-2"></i>
                        <span>{{ $action['text'] }}</span>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>
